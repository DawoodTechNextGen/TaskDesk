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

switch ($action) {
    case 'get_registrations':
        // DataTables parameters
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
        $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';
        $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
        
        // Allowed columns map for sorting
        // 0: empty, 1: id, 2: name, 3: mbl_number, 4: technology, 
        // 5: internship_type, 6: experience, 7: status, 8: actions
        $columns = [
            1 => 'r.id',
            2 => 'r.name',
            3 => 'r.mbl_number',
            4 => 't.name', 
            5 => 'r.internship_type',
            6 => 'r.experience',
            7 => 'r.status',
            8 => 'r.created_at' // Default sort if needed
        ];
        
        $orderBy = $columns[$orderColumnIndex] ?? 'r.created_at';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
        
        // Base query
        $sqlBase = "FROM registrations r LEFT JOIN technologies t ON t.id = r.technology_id";
        
        // Filtering
        $where = [];
        $params = [];
        $types = "";
        
        // Status filter
        if ($status && in_array($status, ['new', 'contact', 'hire', 'rejected'])) {
            $where[] = "r.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        // Global search
        if (!empty($searchValue)) {
            $searchQuery = "(r.name LIKE ? OR r.email LIKE ? OR r.mbl_number LIKE ? OR r.cnic LIKE ? OR t.name LIKE ?)";
            $where[] = $searchQuery;
            $searchParam = "%{$searchValue}%";
            for ($i = 0; $i < 5; $i++) {
                $params[] = $searchParam;
                $types .= "s";
            }
        }
        
        $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
        
        // Count total records (without filtering)
        $totalSql = "SELECT COUNT(*) as total FROM registrations r";
        $totalResult = $conn->query($totalSql);
        $totalRecords = $totalResult->fetch_assoc()['total'];
        
        // Count filtered records
        $filteredSql = "SELECT COUNT(*) as total $sqlBase $whereClause";
        if (!empty($params)) {
            $stmt = $conn->prepare($filteredSql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $filteredResult = $stmt->get_result();
            $recordsFiltered = $filteredResult->fetch_assoc()['total'];
            $stmt->close();
        } else {
            $filteredResult = $conn->query($filteredSql);
            $recordsFiltered = $filteredResult->fetch_assoc()['total'];
        }
        
        // Fetch data
        $sql = "SELECT r.id, r.name, r.email, r.mbl_number, r.status, r.internship_type, r.experience, r.city, r.country, r.cnic, DATE(r.created_at) AS created_at, t.name AS technology $sqlBase $whereClause ORDER BY $orderBy $orderDir LIMIT ?, ?";
        
        $params[] = $start;
        $params[] = $length;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Formatter helpers
        $internshipTypes = [
            0 => 'Only Internship',
            1 => 'Supervised Internship'
        ];
        
        $experiences = [
            0 => "No Experience",
            1 => '6 Months',
            2 => '1 Year', // User specified "2 means 1 year"
            3 => 'More than 2 Years'
        ];
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Format data as needed
              // Format data efficiently
            if (isset($row['internship_type']) && isset($internshipTypes[$row['internship_type']])) {
                $row['internship_type'] = $internshipTypes[$row['internship_type']];
            }
            
            if (isset($row['experience'])) {
                // Handle potential multi-mapping or strictly follow schema
                if (isset($experiences[$row['experience']])) {
                     $row['experience'] = $experiences[$row['experience']];
                } else if ($row['experience'] == 2) {
                     // Fallback if 2 means 2 years was intended separately but index duplicated
                     $row['experience'] = '1 Year';
                }
            }
            $data[] = $row;
        }
        $stmt->close();
        
        echo json_encode([
            "draw" => isset($_GET['draw']) ? (int)$_GET['draw'] : 0,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
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
        $u->close();
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
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update registration status
            $u = $conn->prepare("UPDATE registrations SET status = ? WHERE id = ?");
            $u->bind_param('si', $newStatus, $id);
            
            if (!$u->execute()) {
                throw new Exception('Failed to update registration status');
            }
            $u->close();
            
            // Get registration details
            $sqlSelect = $conn->prepare("SELECT r.*, t.name as tech_name FROM registrations r LEFT JOIN technologies t ON r.technology_id = t.id WHERE r.id = ?");
            $sqlSelect->bind_param('i', $id);
            
            if (!$sqlSelect->execute()) {
                throw new Exception('Failed to fetch registration details');
            }
            
            $result = $sqlSelect->get_result();
            $registration = $result->fetch_assoc();
            $sqlSelect->close();
            
            if (!$registration) {
                throw new Exception('Registration not found');
            }
            
            // Create user record
            $insertHire = $conn->prepare("INSERT INTO users (name, email, plain_password, password, user_role, status, tech_id, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertHire->bind_param('ssssiiii', $registration['name'], $registration['email'], $password, $hash, $userRole, $status, $registration['technology_id'], $trainer);
            
            if (!$insertHire->execute()) {
                throw new Exception('Failed to create user');
            }
            
            $tech_id = $conn->insert_id;
            $insertHire->close();
            
            // Create certificate record
            $stmt = $conn->prepare("INSERT INTO certificate (intern_id) VALUES (?)");
            $stmt->bind_param('i', $tech_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create certificate');
            }
            $stmt->close();
            
            // Add to email queue
            $data = json_encode([
                'name' => $registration['name'],
                'email' => $registration['email'],
                'password' => $password,
                'tech_name' => $registration['tech_name']
            ]);
            
            $queueStmt = $conn->prepare("INSERT INTO email_queue (to_email, to_name, template, data) VALUES (?, ?, 'welcome_offer', ?)");
            $queueStmt->bind_param('sss', $registration['email'], $registration['name'], $data);
            
            if (!$queueStmt->execute()) {
                throw new Exception('Failed to add to email queue');
            }
            $queueStmt->close();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => 'Hired successfully!']);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'get_counts':
        // Build where clause if filter is provided
        $whereClause = "";
        $params = [];
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $status = mysqli_real_escape_string($conn, $_GET['status']);
            $whereClause = " WHERE status = ?";
            $params[] = $status;
        }
        
        // Use prepared statement for security
        $countQuery = "SELECT 
            SUM(CASE WHEN status = 'contact' THEN 1 ELSE 0 END) as total_contact,
            SUM(CASE WHEN status = 'hire' THEN 1 ELSE 0 END) as total_hire,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected
        FROM registrations" . $whereClause;
        
        if (!empty($params)) {
            $stmt = $conn->prepare($countQuery);
            $stmt->bind_param('s', $params[0]);
            $stmt->execute();
            $countResult = $stmt->get_result();
            $counts = $countResult->fetch_assoc();
            $stmt->close();
        } else {
            $countResult = mysqli_query($conn, $countQuery);
            $counts = mysqli_fetch_assoc($countResult);
        }
        
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

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>