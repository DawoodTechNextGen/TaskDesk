<?php
session_start();
include_once '../include/connection.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
if (!$user_id || !in_array($user_role, [1, 4], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

// Ensure the chat_requests table exists in this database.
$conn->query("CREATE TABLE IF NOT EXISTS `chat_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_request` (`sender_id`, `receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create chat_rules table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `chat_rules` (
  `rule_key` VARCHAR(50) PRIMARY KEY,
  `rule_value` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Seed default rules if table is empty
$countRes = $conn->query("SELECT COUNT(*) as count FROM chat_rules");
if ($countRes) {
    $row = $countRes->fetch_assoc();
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO chat_rules (rule_key, rule_value) VALUES 
            ('admin_to_all', 1),
            ('supervisor_to_supervisor', 1),
            ('intern_to_intern', 1),
            ('intern_to_supervisor', 1),
            ('supervisor_to_intern', 1)");
    }
}

switch ($action) {
    case 'get_requests':
        $sql = "SELECT cr.id, cr.sender_id, cr.receiver_id, cr.status, cr.created_at, cr.updated_at,
                       s.name AS sender_name, s.email AS sender_email,
                       r.name AS receiver_name, r.email AS receiver_email
                FROM chat_requests cr
                JOIN users s ON s.id = cr.sender_id
                JOIN users r ON r.id = cr.receiver_id
                WHERE cr.status = 'pending'
                ORDER BY cr.updated_at DESC";
        $result = $conn->query($sql);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch chat requests']);
            exit;
        }

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $requests]);
        break;

    case 'approve':
    case 'reject':
        $requestId = isset($data['request_id']) ? (int)$data['request_id'] : 0;
        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request id']);
            exit;
        }

        $status = $action === 'approve' ? 'accepted' : 'rejected';
        $stmt = $conn->prepare("UPDATE chat_requests SET status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement']);
            exit;
        }
        $stmt->bind_param('si', $status, $requestId);
        $executed = $stmt->execute();
        $stmt->close();

        if (!$executed) {
            echo json_encode(['success' => false, 'message' => 'Failed to update chat request status']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Chat request has been ' . ($status === 'accepted' ? 'approved' : 'rejected') . '.']);
        break;

    case 'get_monitored_chats':
        $sql = "SELECT cr.id, cr.sender_id, cr.receiver_id, cr.status, cr.created_at, cr.updated_at,
                       s.name AS sender_name, s.email AS sender_email,
                       r.name AS receiver_name, r.email AS receiver_email
                FROM chat_requests cr
                JOIN users s ON s.id = cr.sender_id
                JOIN users r ON r.id = cr.receiver_id
                WHERE cr.status = 'accepted'
                  AND (s.user_role = 'intern' OR s.user_role = '2')
                  AND (r.user_role = 'intern' OR r.user_role = '2')
                ORDER BY cr.updated_at DESC";
        $result = $conn->query($sql);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch monitored chats']);
            exit;
        }

        $chats = [];
        while ($row = $result->fetch_assoc()) {
            $chats[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $chats]);
        break;

    case 'get_chat_logs':
        $senderId = isset($data['sender_id']) ? (int)$data['sender_id'] : 0;
        $receiverId = isset($data['receiver_id']) ? (int)$data['receiver_id'] : 0;
        if ($senderId <= 0 || $receiverId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid users for logs']);
            exit;
        }

        $stmt = $conn->prepare("SELECT m.id, m.sender_id, m.receiver_id, m.message, m.message_type, m.file_path, m.created_at,
                                      u.name AS sender_name
                               FROM chat_messages m
                               JOIN users u ON u.id = m.sender_id
                               WHERE (m.sender_id = ? AND m.receiver_id = ?)
                                  OR (m.sender_id = ? AND m.receiver_id = ?)
                               ORDER BY m.created_at ASC");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare logs statement']);
            exit;
        }

        $stmt->bind_param('iiii', $senderId, $receiverId, $receiverId, $senderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $logs]);
        break;

    case 'get_chat_rules':
        $result = $conn->query("SELECT * FROM chat_rules");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch chat rules']);
            exit;
        }
        $rules = [];
        while ($row = $result->fetch_assoc()) {
            $rules[$row['rule_key']] = (int)$row['rule_value'];
        }
        echo json_encode(['success' => true, 'data' => $rules]);
        break;

    case 'update_chat_rules':
        $rules = $data['rules'] ?? [];
        foreach ($rules as $key => $val) {
            $val = $val ? 1 : 0;
            $stmt = $conn->prepare("UPDATE chat_rules SET rule_value = ? WHERE rule_key = ?");
            if ($stmt) {
                $stmt->bind_param('is', $val, $key);
                $stmt->execute();
                $stmt->close();
            }
        }
        echo json_encode(['success' => true, 'message' => 'Chat access rules updated successfully.']);
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
