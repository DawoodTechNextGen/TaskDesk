<?php
session_start();
include '../include/connection.php';
header('Content-Type: application/json');


$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

switch ($action) {
    // Get all Managers
    case 'get_managers':
        $stmt = $conn->prepare("
            SELECT u.id, u.name,u.email, u.plain_password, u.commission_rate
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
                t.name AS tech_name,
                u.commission_rate
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
            u.internship_type,
            u.internship_duration,
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
            (
                SELECT ROUND((COUNT(DISTINCT ca.date) / (TIMESTAMPDIFF(DAY, u.created_at, NOW()) + 1)) * 100)
                FROM (
                    SELECT DATE(date) as date, user_id FROM attendance WHERE total_work_seconds >= 10800
                    UNION
                    SELECT DATE(completed_at) as date, assign_to as user_id FROM tasks WHERE status = 'complete'
                ) as ca
                WHERE ca.user_id = u.id
                  AND ca.date >= DATE(u.created_at)
                  AND ca.date <= CURDATE()
            ) as attendance_rate,
            DATEDIFF(DATE_ADD(u.created_at, INTERVAL (CASE 
                WHEN u.internship_duration = '4 weeks' THEN 4
                WHEN u.internship_duration = '8 weeks' THEN 8
                WHEN u.internship_duration = '12 weeks' THEN 12
                ELSE IF(u.internship_type = 0, 4, 12)
            END) WEEK), NOW()) as days_left,
            c.approve_status
        FROM users u
        LEFT JOIN technologies t ON u.tech_id = t.id
        LEFT JOIN certificate c ON c.intern_id = u.id
        LEFT JOIN users s ON u.supervisor_id = s.id
        WHERE u.user_role = 2
          AND u.freeze_status = 'active'
          AND u.status = 1
          AND DATE_ADD(u.created_at, INTERVAL (CASE 
                WHEN u.internship_duration = '4 weeks' THEN 4
                WHEN u.internship_duration = '8 weeks' THEN 8
                WHEN u.internship_duration = '12 weeks' THEN 12
                ELSE IF(u.internship_type = 0, 4, 12)
            END) WEEK) > NOW()
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

    case 'get_frozen_internees':
        $user_id   = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];

        $sql = "
        SELECT
            u.id, u.name, u.email, u.tech_id, t.name AS tech_name,
            s.id AS supervisor_id, s.name AS supervisor_name,
            u.freeze_start_date, u.freeze_end_date, u.freeze_reason,
            DATE(u.created_at) AS joining_date
        FROM users u
        LEFT JOIN technologies t ON u.tech_id = t.id
        LEFT JOIN users s ON u.supervisor_id = s.id
        WHERE u.user_role = 2 AND u.freeze_status = 'frozen' AND u.status = 1
        ";

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

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'get_completed_internees':
        $user_id   = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];

        $sql = "
        SELECT
            u.id, u.name, u.email, u.tech_id, u.internship_type, u.internship_duration, t.name AS tech_name,
            s.id AS supervisor_id, s.name AS supervisor_name,
            DATE(u.created_at) AS joining_date,
            DATE_ADD(u.created_at, INTERVAL (CASE 
                WHEN u.internship_duration = '4 weeks' THEN 4
                WHEN u.internship_duration = '8 weeks' THEN 8
                WHEN u.internship_duration = '12 weeks' THEN 12
                ELSE IF(u.internship_type = 0, 4, 12)
            END) WEEK) AS completion_date,
            (
                SELECT ROUND(
                    (COUNT(CASE WHEN status = 'complete' THEN 1 END) / COUNT(*)) * 100
                )
                FROM tasks
                WHERE assign_to = u.id
            ) AS completion_rate,
            TIMESTAMPDIFF(MONTH, u.created_at, NOW()) AS months_completed,
            DATEDIFF(DATE_ADD(u.created_at, INTERVAL (CASE 
                WHEN u.internship_duration = '4 weeks' THEN 4
                WHEN u.internship_duration = '8 weeks' THEN 8
                WHEN u.internship_duration = '12 weeks' THEN 12
                ELSE IF(u.internship_type = 0, 4, 12)
            END) WEEK), NOW()) as days_left,
            (
                SELECT ROUND((COUNT(DISTINCT ca.date) / (TIMESTAMPDIFF(DAY, u.created_at, DATE_ADD(u.created_at, INTERVAL (CASE 
                    WHEN u.internship_duration = '4 weeks' THEN 4
                    WHEN u.internship_duration = '8 weeks' THEN 8
                    WHEN u.internship_duration = '12 weeks' THEN 12
                    ELSE IF(u.internship_type = 0, 4, 12)
                END) WEEK)) + 1)) * 100)
                FROM (
                    SELECT DATE(date) as date, user_id FROM attendance WHERE total_work_seconds >= 10800
                    UNION
                    SELECT DATE(completed_at) as date, assign_to as user_id FROM tasks WHERE status = 'complete'
                ) as ca
                WHERE ca.user_id = u.id
                  AND ca.date >= DATE(u.created_at)
                  AND ca.date <= DATE_ADD(u.created_at, INTERVAL (CASE 
                        WHEN u.internship_duration = '4 weeks' THEN 4
                        WHEN u.internship_duration = '8 weeks' THEN 8
                        WHEN u.internship_duration = '12 weeks' THEN 12
                        ELSE IF(u.internship_type = 0, 4, 12)
                    END) WEEK)
            ) as attendance_rate,
            c.approve_status
        FROM users u
        LEFT JOIN technologies t ON u.tech_id = t.id
        LEFT JOIN users s ON u.supervisor_id = s.id
        LEFT JOIN certificate c ON c.intern_id = u.id
        WHERE u.user_role = 2
          AND u.status = 1
          AND DATE_ADD(u.created_at, INTERVAL (CASE 
                WHEN u.internship_duration = '4 weeks' THEN 4
                WHEN u.internship_duration = '8 weeks' THEN 8
                WHEN u.internship_duration = '12 weeks' THEN 12
                ELSE IF(u.internship_type = 0, 4, 12)
            END) WEEK) <= NOW()
        ";

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

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
        $name = trim($_POST['name']);
        $password = $_POST['password'] ?? '';
        $role = (int)$_POST['role'];
        $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : 0;
        $email = trim($_POST['email']);
        $supervisor_id = $_POST['supervisor_id'] ?? 0;
        $commission_rate = isset($_POST['commission_rate']) ? (int)$_POST['commission_rate'] : 1000;
        if (empty($name) || !in_array($role, ['3', '2', '4'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        // Generate a secure password if not provided
        if (empty($password)) {
            $password = generateStrictPassword(12);
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, password, user_role, tech_id, email, plain_password, supervisor_id, commission_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssissii', $name, $hashed, $role, $tech_id, $email, $password, $supervisor_id, $commission_rate);

        if ($stmt->execute()) {
            $user_role_label = ($role == 2) ? 'Intern' : (($role == 3) ? 'Supervisor' : 'Manager');
            logActivity('Create User', "Created $user_role_label: $name ($email)");
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
        $commission_rate = isset($_POST['commission_rate']) ? (int)$_POST['commission_rate'] : 1000;

        // Current user info
        $acting_user_id = $_SESSION['user_id'];
        $acting_user_role = $_SESSION['user_role'];

        if ($id <= 0 || empty($name) || !in_array($role, ['3', '2', '4'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        // Security check: Supervisors are not allowed to edit intern details
        if ($acting_user_role == 3) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Supervisors are not allowed to edit intern details']);
            exit;
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, plain_password = ? , email = ?, password = ?, user_role = ?, tech_id = ?, supervisor_id = ?, commission_rate = ? WHERE id = ?");
            $stmt->bind_param('ssssiiiii', $name, $password, $email, $hashed, $role, $tech_id, $supervisor_id, $commission_rate, $id);

            if ($stmt->execute()) {
                $user_role_label = ($role == 2) ? 'Intern' : (($role == 3) ? 'Supervisor' : 'Manager');
                logActivity('Update User', "Updated details for $user_role_label ID $id: $name ($email)");
                echo json_encode([
                    'success' => true,
                    'message' => 'Updated successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, user_role = ?, tech_id = ?, commission_rate = ? WHERE id = ?");
            $stmt->bind_param('sssiii', $name, $email, $role, $tech_id, $commission_rate, $id);
            $success = $stmt->execute();

            if ($success) {
                $user_role_label = ($role == 2) ? 'Intern' : (($role == 3) ? 'Supervisor' : 'Manager');
                logActivity('Update User', "Updated details for $user_role_label ID $id: $name ($email)");
            }

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
        if ($success) {
            logActivity('Delete User', "Deleted user ID $id");
        }

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Deleted successfully!' : 'Cannot delete user'
        ]);
        break;

    case 'refund':
        $id = (int)($_POST['id'] ?? 0);
        $acting_user_role = (int)$_SESSION['user_role'];
        if ($acting_user_role !== 1 && $acting_user_role !== 4) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Get the last commission record for this intern to know the supervisor and amount
        $comm_stmt = $conn->prepare("SELECT supervisor_id, amount FROM commissions WHERE intern_id = ? ORDER BY id DESC LIMIT 1");
        $comm_stmt->bind_param("i", $id);
        $comm_stmt->execute();
        $comm_res = $comm_stmt->get_result()->fetch_assoc();
        $comm_stmt->close();

        if (!$comm_res) {
            echo json_encode(['success' => false, 'message' => 'No commission record found for this intern']);
            exit;
        }

        $supervisor_id = (int)$comm_res['supervisor_id'];
        $original_amount = (int)$comm_res['amount'];
        $refund_amount = -$original_amount;

        $conn->begin_transaction();
        try {
            // 1. Insert negative/refund commission record
            $ins_stmt = $conn->prepare("INSERT INTO commissions (supervisor_id, intern_id, amount) VALUES (?, ?, ?)");
            $ins_stmt->bind_param("iii", $supervisor_id, $id, $refund_amount);
            if (!$ins_stmt->execute()) {
                throw new Exception('Failed to insert refund commission');
            }
            $ins_stmt->close();

            // 2. Deactivate the intern (status = 0)
            $update_stmt = $conn->prepare("UPDATE users SET status = 0 WHERE id = ?");
            $update_stmt->bind_param("i", $id);
            if (!$update_stmt->execute()) {
                throw new Exception('Failed to deactivate intern');
            }
            $update_stmt->close();

            $conn->commit();

            // Get intern name for details
            $intern_name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $intern_name_stmt->bind_param("i", $id);
            $intern_name_stmt->execute();
            $intern_name_res = $intern_name_stmt->get_result()->fetch_assoc();
            $intern_name = $intern_name_res['name'] ?? 'ID ' . $id;
            $intern_name_stmt->close();

            logActivity('Refund Intern', "Refunded & deactivated intern $intern_name (ID $id). Reversed commission of $original_amount PKR.");

            echo json_encode(['success' => true, 'message' => 'Intern refunded and deactivated successfully. Commission has been deducted.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_salary_breakdown':
        $supervisor_id = (int)($_GET['supervisor_id'] ?? 0);
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $acting_user_role = (int)$_SESSION['user_role'];
        if ($acting_user_role !== 1) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT c.id, c.amount, c.created_at, u.name as intern_name, u.email as intern_email, u.internship_duration
            FROM commissions c
            LEFT JOIN users u ON c.intern_id = u.id
            WHERE c.supervisor_id = ? AND MONTH(c.created_at) = ? AND YEAR(c.created_at) = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("iii", $supervisor_id, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $data]);
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
