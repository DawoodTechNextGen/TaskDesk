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
        $draw   = intval($_POST['draw'] ?? 1);
        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $status = strtolower(trim($_POST['status'] ?? ''));

        $allowedStatus = ['new', 'contact', 'hire', 'rejected'];

        /* ---------------- TOTAL RECORDS ---------------- */
        $totalQuery = $conn->query("SELECT COUNT(*) AS total FROM registrations");
        $totalRecords = (int)$totalQuery->fetch_assoc()['total'];

        /* ---------------- FILTERED QUERY ---------------- */
        $where = '';
        $params = [];
        $types = '';

        if ($status && in_array($status, $allowedStatus, true)) {
            $where = " WHERE r.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        /* ---------------- FILTERED COUNT ---------------- */
        $countSql = "SELECT COUNT(*) AS total
             FROM registrations r
             $where";
        $countStmt = $conn->prepare($countSql);

        if ($types) {
            $countStmt->bind_param($types, ...$params);
        }

        $countStmt->execute();
        $filteredRecords = (int)$countStmt->get_result()->fetch_assoc()['total'];

        /* ---------------- MAIN DATA QUERY ---------------- */
        $sql = "
    SELECT
        r.id,
        r.name,
        r.email,
        r.mbl_number,
        r.status,
        r.internship_type,
        r.experience,
        r.city,
        r.country,
        r.cnic,
        DATE(r.created_at) AS created_at,
        t.name AS technology
    FROM registrations r
    LEFT JOIN technologies t ON t.id = r.technology_id
    $where
    ORDER BY r.created_at DESC
    LIMIT ?, ?
    ";

        $stmt = $conn->prepare($sql);

        // Add pagination parameters to the params array
        $params[] = $start;
        $params[] = $length;

        if ($types) {
            $stmt->bind_param($types . 'ii', ...$params);
        } else {
            $stmt->bind_param('ii', $start, $length);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['internship_type'] = [
                'Internship Only',
                'Full Training + Internship'
            ][$row['internship_type']] ?? $row['internship_type'];

            $row['experience'] = [
                "I don't have any Experience",
                '6 Months',
                '1 Year',
                '2 Years',
                'More than 2 Years'
            ][$row['experience']] ?? $row['experience'];

            $rows[] = $row;
        }

        echo json_encode([
            "success"          => true,
            "draw"            => $draw,
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $filteredRecords,
            "data"            => $rows
        ]);
        exit;
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
    // In controller/registrations.php

    case 'get_counts':
        // Build where clause if filter is provided
        $whereClause = "";
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $status = mysqli_real_escape_string($conn, $_GET['status']);
            $whereClause = " WHERE status = '$status'";
        }

        // Fetch counts
        $countQuery = "SELECT 
        SUM(CASE WHEN status = 'contact' THEN 1 ELSE 0 END) as total_contact,
        SUM(CASE WHEN status = 'hire' THEN 1 ELSE 0 END) as total_hire,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected
    FROM registrations" . $whereClause;

        $countResult = mysqli_query($conn, $countQuery);
        $counts = mysqli_fetch_assoc($countResult);

        echo json_encode([
            'success' => true,
            'total_contact' => $counts['total_contact'] ?? 0,
            'total_hire' => $counts['total_hire'] ?? 0,
            'total_rejected' => $counts['total_rejected'] ?? 0
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
