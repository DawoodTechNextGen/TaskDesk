<?php
include '../include/connection.php';
header('Content-Type: application/json');


$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

switch ($action) {
    // Get all supervisors
    case 'get_supervisors':
        $stmt = $conn->prepare("
            SELECT u.id, u.name,u.email, u.plain_password
            FROM users u 
            WHERE u.user_role = 3 
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    
    case 'get_internees':
        $stmt = $conn->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.tech_id,
                u.plain_password,
                t.name AS tech_name,
                DATE(u.created_at) AS joining_date,
                (
                    SELECT ROUND(
                        (COUNT(CASE WHEN status = 'complete' THEN 1 END) / COUNT(*)) * 100
                    )
                    FROM tasks 
                    WHERE assign_to = u.id
                ) AS completion_rate,
                TIMESTAMPDIFF(MONTH, u.created_at, NOW()) AS months_completed,
                c.approve_status
            FROM users u
            LEFT JOIN technologies t ON u.tech_id = t.id
            LEFT JOIN certificate c ON c.intern_id = u.id
            WHERE u.user_role = 2
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
        $name = trim($_POST['name']);
        $password = $_POST['password'] ?? '';
        $role = (int)$_POST['role'];
        $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : 0;
        $email = trim($_POST['email']);

        if (empty($name) || empty($password) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        // Generate a secure password if not provided
        if (empty($password)) {
            $password = bin2hex(random_bytes(4)); // 8 character password
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, password, user_role, tech_id, email, plain_password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssiss', $name, $hashed, $role, $tech_id, $email, $password);

       if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        if ($role == 2) {
            // Insert certificate
            $stmt = $conn->prepare("INSERT INTO certificate (intern_id) VALUES (?)");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();

            // Get tech name
            $tech_name = '';
            if ($tech_id) {
                $t = $conn->prepare("SELECT name FROM technologies WHERE id = ?");
                $t->bind_param('i', $tech_id);
                $t->execute();
                $techResult = $t->get_result()->fetch_assoc();
                $tech_name = $techResult['name'] ?? 'Intern';
            }

            // QUEUE THE EMAIL (not send now)
            $data = json_encode([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'tech_name' => $tech_name,
                'tech_id' => $tech_id
            ]);

            $queueStmt = $conn->prepare("
                INSERT INTO email_queue (to_email, to_name, template, data) 
                VALUES (?, ?, 'welcome_offer', ?)
            ");
            $queueStmt->bind_param('sss', $email, $name, $data);
            $queueStmt->execute();
        }

        echo json_encode([
            'success' => true,
            'message' => ($role ==2)?'Internee':'Supervisor'.' created successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
    break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = (int)$_POST['role'];
        // $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : 0;
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($name) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, plain_password = ? , email = ?, password = ?, user_role = ?");
            $stmt->bind_param('sssssii', $name, $password, $email, $hashed, $role, $id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Updated successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, user_role = ?, tech_id = ? WHERE id = ?");
            $stmt->bind_param('sssii', $name, $email, $role, $tech_id, $id);
            $success = $stmt->execute();
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Updated successfully!' : 'Update failed'
            ]);
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Deleted successfully!' : 'Cannot delete user'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}