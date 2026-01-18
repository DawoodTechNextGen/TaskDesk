<?php
session_start();
include '../include/connection.php';
header('Content-Type: application/json');


$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

switch ($action) {
    // Get all Managers
    case 'get_managers':
        $stmt = $conn->prepare("
            SELECT u.id, u.name,u.email, u.plain_password
            FROM users u 
            WHERE u.user_role = 4 
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;
    // Get all supervisors
    case 'get_supervisors':
        $stmt = $conn->prepare("
            SELECT 
                u.id, 
                u.name, 
                u.email, 
                u.plain_password, 
                u.tech_id,
                t.name AS tech_name
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


    case 'get_internees':

        $user_id   = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];

        $sql = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.tech_id,
            u.plain_password,
            t.name AS tech_name,
            s.id AS supervisor_id,
            s.name AS supervisor_name,
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
        LEFT JOIN users s ON u.supervisor_id = s.id
        WHERE u.user_role = 2
    ";

        // Apply supervisor condition ONLY if role == 3
        if ($user_role == 3) {
            $sql .= " AND u.supervisor_id = ? ";
        }

        $stmt = $conn->prepare($sql);

        if ($user_role == 3) {
            $stmt->bind_param('i', $user_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);

        break;

    case 'create':
        $name = trim($_POST['name']);
        $password = $_POST['password'] ?? '';
        $role = (int)$_POST['role'];
        $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : 0;
        $email = trim($_POST['email']);
        $supervisor_id = $_POST['supervisor_id'] ?? 0;
        if (empty($name) || !in_array($role, ['3', '2', '4'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        // Generate a secure password if not provided
        if (empty($password)) {
            $password = generateStrictPassword(12);
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, password, user_role, tech_id, email, plain_password, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssissi', $name, $hashed, $role, $tech_id, $email, $password, $supervisor_id);

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
                'message' => ($role == 2) ? 'Internee created successfully!' : (($role == 3) ? 'Supervisor created successfully!' : 'Manager created successfully!')
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
        $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : 0;
        $password = $_POST['password'] ?? '';
        $supervisor_id = $_POST['supervisor_id'] ?? 0;
        if ($id <= 0 || empty($name) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, plain_password = ? , email = ?, password = ?, user_role = ?, tech_id = ?, supervisor_id = ? WHERE id = ?");
            $stmt->bind_param('ssssiiii', $name, $password, $email, $hashed, $role, $tech_id, $supervisor_id, $id);

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
function generateStrictPassword($length = 12)
{
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()-_=+{}[]<>?';

    $all = $upper . $lower . $numbers . $symbols;

    // Ensure each category exists
    $password = '';
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];

    // Fill remaining characters
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    // Shuffle to avoid pattern
    return str_shuffle($password);
}
