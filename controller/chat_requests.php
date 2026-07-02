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

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
