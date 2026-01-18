<?php
session_start();
include '../include/connection.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Only Admin (1) and Manager (4) can access registrations
if (
    !isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], [1, 4], true)
) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}


switch ($action) {
    case 'get_registrations':
        /* Join with technologies to get human-friendly technology name and map codes to labels */
        $status = $_GET['status'] ?? $_POST['status'] ?? '';
        $allowedStatus = ['new', 'contact', 'hire', 'rejected'];

        $sql = "SELECT r.*, t.name AS technology_name FROM registrations r LEFT JOIN technologies t ON (t.id = r.technology_id)";
        $params = [];

        if (!empty($status) && in_array(strtolower($status), $allowedStatus)) {
            $sql .= " WHERE r.status = ?";
            $params[] = strtolower($status);
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query prepare failed']);
            exit;
        }

        if (count($params) === 1) {
            $stmt->bind_param('s', $params[0]);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // Mapping constants
        $internshipMap = [
            0 => 'Internship Only',
            1 => 'Full Training + Internship'
        ];

        $experienceMap = [
            0 => "I don't have any Experience",
            1 => '6 Months',
            2 => '1 Year',
            3 => '2 Years',
            4 => 'More than 2 Years'
        ];

        foreach ($rows as &$r) {
            // Ensure technology_id is populated from possible column names
            if (isset($r['tech_id']) && !isset($r['technology_id'])) {
                $r['technology_id'] = $r['tech_id'];
            }

            // Technology fallback (human-friendly name)
            $r['technology'] = $r['technology_name'] ?? '';
            // remove raw technology_name to avoid duplicate column (we keep `technology`)
            if (isset($r['technology_name'])) unset($r['technology_name']);

            // Normalize and map internship_type
            if (isset($r['internship_type'])) {
                if (is_numeric($r['internship_type'])) {
                    $r['internship_type'] = $internshipMap[(int)$r['internship_type']] ?? $r['internship_type'];
                } else {
                    // If stored as string label, leave it as-is
                    $r['internship_type'] = (string)$r['internship_type'];
                }
            }

            // Normalize and map experience
            if (isset($r['experience'])) {
                if (is_numeric($r['experience'])) {
                    $r['experience'] = $experienceMap[(int)$r['experience']] ?? $r['experience'];
                } else {
                    $r['experience'] = (string)$r['experience'];
                }
            }

            // Format created_at to date-only for readability (if present)
            if (!empty($r['created_at'])) {
                $dt = strtotime($r['created_at']);
                if ($dt !== false) {
                    $r['created_at'] = date('Y-m-d', $dt);
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'update_status':
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = strtolower(trim($_POST['status'] ?? ''));
        $allowed = ['new', 'contact', 'hire', 'rejected'];
        if ($id <= 0 || !in_array($newStatus, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            break;
        }
        $u = $conn->prepare("UPDATE registrations SET status = ? WHERE id = ?");
        $u->bind_param('si', $newStatus, $id);
        if ($u->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;
    case 'update_hire_status':
        $id = (int)($_POST['id'] ?? 0);
        $trainer = (int)($_POST['trainer'] ?? 0);
        $password = generateStrictPassword(12);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userRole = 2;
        $status = 1;
        $newStatus = 'hire';
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            break;
        }
        $u = $conn->prepare("UPDATE registrations SET status = ? WHERE id = ?");
        $u->bind_param('si', $newStatus, $id);
        if ($u->execute()) {
            $sqlSelect = $conn->prepare("SELECT r.*,t.name as tech_name FROM registrations r LEFT JOIN technologies t ON r.technology_id = t.id WHERE r.id = ?");
            $sqlSelect->bind_param('i', $id);
            if ($sqlSelect->execute()) {
                $result = $sqlSelect->get_result();
                $registration = $result->fetch_assoc();
                $insertHire = $conn->prepare("INSERT INTO users (name, email, plain_password, password, user_role, status, tech_id, supervisor_id ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertHire->bind_param('ssssiiii', $registration['name'], $registration['email'], $password, $hash, $userRole, $status, $registration['technology_id'], $trainer);
                if (!$insertHire->execute()) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create user']);
                    break;
                } else {
                    $tech_id = $conn->insert_id;
                    $stmt = $conn->prepare("INSERT INTO certificate (intern_id) VALUES (?)");
                    $stmt->bind_param('i', $tech_id);
                    if ($stmt->execute()) {
                        $data = json_encode([
                            'name' => $registration['name'],
                            'email' => $registration['email'],
                            'password' => $password,
                            'tech_name' => $registration['tech_name']
                        ]);
                        $queueStmt = $conn->prepare("
                        INSERT INTO email_queue (to_email, to_name, template, data) 
                        VALUES (?, ?, 'welcome_offer', ?)
                        ");
                        $queueStmt->bind_param('sss', $registration['email'], $registration['name'], $data);
                        $queueStmt->execute();
                        echo json_encode(['success' => true, 'message' => 'hired successfully!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Certificate creation failed']);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Fetch failed']);
                break;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
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
