<?php
session_start();
include '../include/connection.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Only Admin (1) and Manager (4) can access registrations
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], [1, 4], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Status Mapping
$statusMap = [
    'new' => 'new',
    'contact' => 'contact',
    'interview' => 'interview',
    'rejected' => 'rejected'
];

switch ($action) {

    // ===============================
    // REGISTRATIONS LIST (BY STATUS)
    // ===============================
    case 'new':
    case 'contact':
    case 'interview':
    case 'rejected':
    case 'get_registrations':

        // Determine status from action
        $status = $statusMap[$action] ?? ($_GET['status'] ?? '');

        // DataTables params
        $start  = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 10);
        $searchValue = trim($_GET['search']['value'] ?? '');
        $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 1);
        $orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $columns = [
            1 => 'r.id',
            2 => 'r.name',
            3 => 'r.mbl_number',
            4 => 't.name',
            5 => 'r.internship_type',
            6 => 'r.experience',
            7 => 'r.status',
            8 => 'r.created_at'
        ];

        $orderBy = $columns[$orderColumnIndex] ?? 'r.created_at';

        $sqlBase = "FROM registrations r 
                    LEFT JOIN technologies t ON t.id = r.technology_id";

        $where = [];
        $params = [];
        $types = '';

        // Status filter (ONLY from action)
        if (!empty($status)) {
            $where[] = "r.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        // Global search
        if ($searchValue !== '') {
            $where[] = "(r.name LIKE ? OR r.email LIKE ? OR r.mbl_number LIKE ? OR r.cnic LIKE ? OR t.name LIKE ?)";
            for ($i = 0; $i < 5; $i++) {
                $params[] = "%{$searchValue}%";
                $types .= 's';
            }
        }

        $whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Total records
        $totalRecords = $conn->query("SELECT COUNT(*) total FROM registrations")->fetch_assoc()['total'];

        // Filtered count
        $countSql = "SELECT COUNT(*) total $sqlBase $whereClause";
        $stmt = $conn->prepare($countSql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $recordsFiltered = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Data query
        $dataSql = "
            SELECT r.id, r.name, r.email, r.mbl_number, r.status,
                   r.internship_type, r.experience, r.city, r.country,
                   r.cnic, DATE(r.created_at) created_at,
                   t.name technology
            $sqlBase
            $whereClause
            ORDER BY $orderBy $orderDir
            LIMIT ?, ?
        ";

        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $conn->prepare($dataSql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'draw' => (int)($_GET['draw'] ?? 0),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
        exit;

        // ===============================
        // INTERVIEWS LIST
        // ===============================
    case 'interview':
        $sql = "
            SELECT i.*, r.name, r.email, t.name technology
            FROM interviews i
            JOIN registrations r ON r.id = i.registration_id
            LEFT JOIN technologies t ON t.id = r.technology_id
            WHERE i.status IN ('scheduled','rescheduled')
            ORDER BY i.interview_date DESC, i.start_time DESC
        ";
        $result = $conn->query($sql);
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
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

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
