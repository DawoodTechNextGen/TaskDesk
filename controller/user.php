<?php
include '../include/connection.php';
header('Content-Type: application/json');

// Allow both POST and GET for 'action'
$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

switch ($action) {

    // Get all supervisors
    case 'get_supervisors':
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.tech_id, u.email, u.plain_password, t.name AS tech_name 
            FROM users u 
            LEFT JOIN technologies t ON u.tech_id = t.id 
            WHERE u.user_role = 3 
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // Get all internees/students
    case 'get_internees':
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.tech_id, u.email,  u.plain_password, t.name AS tech_name 
            FROM users u 
            LEFT JOIN technologies t ON u.tech_id = t.id 
            WHERE u.user_role = 2 
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // Create new user (supervisor or student)
    case 'create':
        $name     = trim($_POST['name']);
        $password = $_POST['password'] ?? '';
        $role     = (int)$_POST['role']; // 'supervisor' or 'student'
        $tech_id  = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : null;
        $email = trim($_POST['email']);

        if (empty($name) || empty($password) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, password, user_role, tech_id,email,plain_password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssiss', $name, $hashed, $role, $tech_id, $email, $password);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => ($role == 2)? 'Internee'. ' created successfully!': 'Supervisor' . ' created successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        }
        break;

    // Update existing user
    case 'update':
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role     = (int)$_POST['role'];
        $tech_id  = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : null;
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($name) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        if (!empty($password)) {
            // Update with new password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, plain_password = ? , email = ?,password = ?, user_role = ?, tech_id = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('ssssssii', $name, $password, $email, $hashed, $role, $tech_id, $id, $role);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, user_role = ?, tech_id = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('ssssii', $name, $email, $role, $tech_id, $id, $role);
        }

        $success = $stmt->execute();
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Updated successfully!' : 'Update failed'
        ]);
        break;

    // Delete user
    case 'delete':
        $id   = (int)($_POST['id'] ?? 0);

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
