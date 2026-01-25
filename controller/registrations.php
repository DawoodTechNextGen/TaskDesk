<?php
session_start();
include '../include/config.php';
include '../include/connection.php';
require_once '../include/pdf_helper.php';
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
    'rejected' => 'rejected'
];

switch ($action) {

    // ===============================
    // REGISTRATIONS LIST (BY STATUS)
    // ===============================
    case 'new':
    case 'contact':
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
               r.cnic, DATE(r.created_at) created_at, r.remarks,
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
            // Format internship_type
            if (isset($row['internship_type'])) {
                $row['internship_type_text'] = $row['internship_type'] == 0
                    ? 'Only Internship'
                    : 'Supervised Internship';
            }

            // Format experience
            if (isset($row['experience'])) {
                switch ($row['experience']) {
                    case 0:
                        $row['experience_text'] = 'No Experience';
                        break;
                    case 1:
                        $row['experience_text'] = '6 Months';
                        break;
                    case 2:
                        $row['experience_text'] = '1 Year';
                        break;
                    case 3:
                        $row['experience_text'] = '2 Years';
                        break;
                    case 4:
                        $row['experience_text'] = 'More Than 2 Years';
                        break;
                    default:
                        $row['experience_text'] = '-';
                        break;
                }
            }

            // Format remarks for tooltip
            if (isset($row['remarks'])) {
                $remarks = trim($row['remarks']);
                if (empty($remarks)) {
                    $row['remarks_text'] = 'No remarks';
                    $row['has_remarks'] = false;
                } else {
                    $row['remarks_text'] = htmlspecialchars($remarks);
                    $row['has_remarks'] = true;
                }
            } else {
                $row['remarks_text'] = 'No remarks';
                $row['has_remarks'] = false;
            }

            $data[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'draw' => (int)($_GET['draw'] ?? 0),
            'recordsTotal' => count($data),
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
        exit;

        // ===============================
        // SCHEDULE INTERVIEW WITH CONFLICT CHECK
        // ===============================
    case 'check_conflict':
        // Get form data
        $candidate_id = (int)$_POST['id'];
        $interview_start = $_POST['interview_start'];
        $interview_end = $_POST['interview_end'];

        // Validate required fields
        if (empty($candidate_id) || empty($interview_start) || empty($interview_end)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required',
                'has_conflict' => true
            ]);
            exit;
        }

        // Validate time format
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $interview_start) ||
            !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $interview_end)
        ) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid datetime format',
                'has_conflict' => true
            ]);
            exit;
        }

        // Check if end time is after start time
        if (strtotime($interview_end) <= strtotime($interview_start)) {
            echo json_encode([
                'success' => false,
                'message' => 'End time must be after start time',
                'has_conflict' => true
            ]);
            exit;
        }

        // Check for scheduling conflicts
        $conflict_sql = "SELECT r.id, r.name, r.interview_start, r.interview_end 
                     FROM registrations r 
                     WHERE r.status = 'interview' 
                     AND r.id != ? 
                     AND r.interview_start < ? 
                     AND r.interview_end > ?";

        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param(
            'iss',
            $candidate_id,
            $interview_end,
            $interview_start
        );
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result();

        if ($conflict_result->num_rows > 0) {
            $conflicts = [];
            while ($row = $conflict_result->fetch_assoc()) {
                $conflicts[] = [
                    'name' => $row['name'],
                    'start' => date('M d, Y h:i A', strtotime($row['interview_start'])),
                    'end' => date('M d, Y h:i A', strtotime($row['interview_end']))
                ];
            }

            $conflict_stmt->close();

            echo json_encode([
                'success' => false,
                'message' => 'Time slot already booked. Please choose a different time.',
                'conflicts' => $conflicts,
                'type' => 'conflict',
                'has_conflict' => true
            ]);
            exit;
        }
        $conflict_stmt->close();

        // Check if this candidate already has a scheduled interview
        $existing_sql = "SELECT id, interview_start, interview_end 
                     FROM registrations 
                     WHERE id = ? 
                     AND status = 'interview' 
                     AND interview_start IS NOT NULL";
        $existing_stmt = $conn->prepare($existing_sql);
        $existing_stmt->bind_param('i', $candidate_id);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result();

        if ($existing_result->num_rows > 0) {
            $row = $existing_result->fetch_assoc();
            $existing_stmt->close();

            echo json_encode([
                'success' => false,
                'message' => 'This candidate already has a scheduled interview at ' .
                    date('M d, Y h:i A', strtotime($row['interview_start'])) .
                    ' to ' . date('h:i A', strtotime($row['interview_end'])),
                'type' => 'already_scheduled',
                'has_conflict' => true
            ]);
            exit;
        }
        $existing_stmt->close();

        // If no conflicts
        echo json_encode([
            'success' => true,
            'message' => 'Time slot is available',
            'has_conflict' => false
        ]);
        break;

    case 'get_booked_slots':
        // Get date parameter
        $date = $_GET['date'] ?? date('Y-m-d');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format',
                'slots' => []
            ]);
            exit;
        }

        // Get all booked slots for the given date
        $sql = "SELECT r.id, r.name, r.interview_start, r.interview_end 
            FROM registrations r 
            WHERE r.status = 'interview' 
            AND DATE(r.interview_start) = ? 
            ORDER BY r.interview_start";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();

        $slots = [];
        while ($row = $result->fetch_assoc()) {
            $slots[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'start' => date('H:i', strtotime($row['interview_start'])),
                'end' => date('H:i', strtotime($row['interview_end'])),
                'formatted_start' => date('h:i A', strtotime($row['interview_start'])),
                'formatted_end' => date('h:i A', strtotime($row['interview_end']))
            ];
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'date' => $date,
            'slots' => $slots
        ]);
        break;

    case 'schedule_interview':
        // Get form data
        $candidate_id = (int)$_POST['id'];
        $interview_start = $_POST['interview_start'];
        $interview_end = $_POST['interview_end'];
        $platform = $_POST['platform'];
        $mbl_number = $_POST['contact'];
        $name = $_POST['name'];
        // Validate required fields
        if (empty($candidate_id) || empty($interview_start) || empty($interview_end)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]);
            exit;
        }

        // Validate time format
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $interview_start) ||
            !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $interview_end)
        ) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid datetime format'
            ]);
            exit;
        }

        // Check if end time is after start time
        if (strtotime($interview_end) <= strtotime($interview_start)) {
            echo json_encode([
                'success' => false,
                'message' => 'End time must be after start time'
            ]);
            exit;
        }

        // Check for scheduling conflicts
        $conflict_sql = "SELECT r.id, r.name, r.interview_start, r.interview_end 
                     FROM registrations r 
                     WHERE r.status = 'interview' 
                     AND r.id != ? 
                     AND r.interview_start < ? 
                     AND r.interview_end > ?";

        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param(
            'iss',
            $candidate_id,
            $interview_end,
            $interview_start
        );
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result();

        if ($conflict_result->num_rows > 0) {
            $conflicts = [];
            while ($row = $conflict_result->fetch_assoc()) {
                $conflicts[] = [
                    'name' => $row['name'],
                    'start' => date('M d, Y h:i A', strtotime($row['interview_start'])),
                    'end' => date('M d, Y h:i A', strtotime($row['interview_end']))
                ];
            }

            $conflict_stmt->close();

            echo json_encode([
                'success' => false,
                'message' => 'Time slot conflict found',
                'conflicts' => $conflicts,
                'type' => 'conflict'
            ]);
            exit;
        }
        $conflict_stmt->close();

        // Also check if this candidate already has a scheduled interview
        $existing_sql = "SELECT id FROM registrations WHERE id = ? AND status = 'interview' 
                     AND interview_start IS NOT NULL";
        $existing_stmt = $conn->prepare($existing_sql);
        $existing_stmt->bind_param('i', $candidate_id);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result();

        if ($existing_result->num_rows > 0) {
            $existing_stmt->close();

            echo json_encode([
                'success' => false,
                'message' => 'This candidate already has a scheduled interview. Please update instead.',
                'type' => 'already_scheduled'
            ]);
            exit;
        }
        $existing_stmt->close();

        // If no conflicts, schedule the interview
        $sql = "UPDATE registrations 
            SET interview_start = ?, interview_end = ?, status = 'interview', platform = ?
            WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $interview_start, $interview_end, $platform, $candidate_id);
        $message = "Assalam-o-Alaikum $name,\n\n"
            . "📋 *Interview Scheduled - Dawood Tech NextGen*\n\n"
            . "🎯 *Position:* Internship\n"
            . "📅 *Date:* " . date('d M, Y', strtotime($interview_start)) . "\n"
            . "⏰ *Time:* " . date('h:i A', strtotime($interview_start)) . " to " . date('h:i A', strtotime($interview_end)) . "\n"
            . "🌐 *Platform:* $platform\n\n"
            . "📌 *Instructions:*\n"
            . "• We sent meeting Link 5 minutes before the scheduled time\n"
            . "• Ensure stable internet connection\n"
            . "• Prepare to discuss your skills and experience\n\n"
            . "If you have any questions or need to reschedule, please contact us \n\n"
            . "We wish you the best of luck!\n\n"
            . "Best regards,\n"
            . "HR Department\n"
            . "*Dawood Tech NextGen*\n"
            . "🚀 Kickstart Your Tech Career with DawoodTech";

            if ($stmt->execute()) {
            $whatsapp_result = whatsappApi($mbl_number, $message);
            echo json_encode([
                'success' => true,
                'message' => 'Interview scheduled successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to schedule interview: ' . $conn->error
            ]);
        }
        $stmt->close();
        break;

    // ===============================
    // GET INTERVIEW REGISTRATIONS
    // ===============================
    case 'interview':
        $columns = [
            'r.id',
            'r.name',
            'r.email',
            'r.mbl_number',
            't.name as technology',
            'r.internship_type',
            'r.experience',
            'r.status',
            'r.interview_start',
            'r.interview_end',
            'r.platform',
            'r.remarks',
            'r.cnic',
            'r.city',
            'r.country',
            'r.created_at'
        ];

        $sql = "SELECT " . implode(', ', $columns) . " 
            FROM registrations r 
            LEFT JOIN technologies t ON r.technology_id = t.id
            WHERE r.status = 'interview'";

        // Apply filters
        $filter = $_GET['filter'] ?? 'all';
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;

        if ($filter === 'today') {
            $sql .= " AND DATE(r.interview_start) = CURDATE()";
        } elseif ($filter === 'upcoming') {
            $sql .= " AND r.interview_start > NOW()";
        } elseif ($filter === 'past') {
            $sql .= " AND r.interview_start < NOW()";
        }

        if ($date_from && $date_to) {
            $sql .= " AND DATE(r.interview_start) BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        } elseif ($date_from) {
            $sql .= " AND DATE(r.interview_start) >= ?";
            $params[] = $date_from;
        } elseif ($date_to) {
            $sql .= " AND DATE(r.interview_start) <= ?";
            $params[] = $date_to;
        }

        // Search
        if (!empty($_GET['search']['value'])) {
            $search = $_GET['search']['value'];
            $sql .= " AND (r.name LIKE ? OR r.email LIKE ? OR r.mbl_number LIKE ? OR t.name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        // Order
        $orderColumn = $columns[$_GET['order'][0]['column'] ?? 1];
        $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
        $sql .= " ORDER BY $orderColumn $orderDir";

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM registrations r 
                 WHERE r.status IN ('interview', 'hired', 'rejected', 'no_show') 
                 AND r.interview_start IS NOT NULL";

        // Pagination
        if (isset($_GET['start']) && isset($_GET['length'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$_GET['length'];
            $params[] = (int)$_GET['start'];
        }

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Format internship_type
            if (isset($row['internship_type'])) {
                $row['internship_type_text'] = $row['internship_type'] == 0
                    ? 'Only Internship'
                    : 'Supervised Internship';
            }

            // Format experience
            if (isset($row['experience'])) {
                switch ($row['experience']) {
                    case 0:
                        $row['experience_text'] = 'No Experience';
                        break;
                    case 1:
                        $row['experience_text'] = '6 Months';
                        break;
                    case 2:
                        $row['experience_text'] = '1 Year';
                        break;
                    case 3:
                        $row['experience_text'] = '2 Years';
                        break;
                    case 4:
                        $row['experience_text'] = 'More Than 2 Years';
                        break;
                    default:
                        $row['experience_text'] = '-';
                        break;
                }
            }

            $data[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
        ]);
        break;

    case 'update_interview_notes':
        $id = (int)$_POST['id'];
        $notes = $_POST['notes'];

        $sql = "UPDATE registrations SET remarks = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $notes, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notes updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update notes']);
        }
        $stmt->close();
        break;

    case 'reschedule_interview':
        $registration_id = (int)$_POST['id'];
        $interview_start = $_POST['interview_start'];
        $interview_end = $_POST['interview_end'];
        $platform = $_POST['platform'];

        // Validate datetime
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $interview_start) ||
            !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $interview_end)
        ) {
            echo json_encode(['success' => false, 'message' => 'Invalid datetime format']);
            exit;
        }

        // Check if new time is in the future
        if (strtotime($interview_start) <= time()) {
            echo json_encode(['success' => false, 'message' => 'Cannot schedule interview in the past']);
            exit;
        }

        if (strtotime($interview_end) <= strtotime($interview_start)) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }

        // Check for conflicts
        $conflict_sql = "SELECT id, name, interview_start, interview_end 
                     FROM registrations 
                     WHERE status = 'interview' 
                     AND id != ? 
                     AND interview_start < ? 
                     AND interview_end > ?";

        $conflict_stmt = $conn->prepare($conflict_sql);
        $conflict_stmt->bind_param('iss', $registration_id, $interview_end, $interview_start);
        $conflict_stmt->execute();
        $conflict_result = $conflict_stmt->get_result();

        if ($conflict_result->num_rows > 0) {
            $conflict_stmt->close();
            echo json_encode([
                'success' => false,
                'message' => 'Time slot conflict. Please choose a different time.',
                'type' => 'conflict'
            ]);
            exit;
        }
        $conflict_stmt->close();

        // Update interview time and details
        $update_sql = "UPDATE registrations 
                   SET interview_start = ?, interview_end = ?, platform = ?,
                       status = 'interview' 
                   WHERE id = ?";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('sssi', $interview_start, $interview_end, $platform, $registration_id);

        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Interview rescheduled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reschedule interview']);
        }
        $update_stmt->close();
        break;

    case 'update_hire_status':
        $id = (int)($_POST['id'] ?? 0);
        $trainer = (int)($_POST['hireTrainer'] ?? 0);
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

            // WhatsApp Notification with Offer Letter
            $whatsappMsg = "Assalam-o-Alaikum " . $registration['name'] . ",\n\n"
                . "🎉 *Congratulations!* You have been hired as a *MERN Stack Intern* at DawoodTech NextGen.\n\n"
                . "🔐 *Your Login Credentials:*\n"
                . "📧 *Email:* " . $registration['email'] . "\n"
                . "🔑 *Password:* " . $password . "\n"
                . "🌐 *TaskDesk:* https://dawoodtechnextgen.org/taskdesk/\n\n"
                . "Your official offer letter is following this message. Please change your password after your first login.\n\n"
                . "Best regards,\n"
                . "HR Department\n"
                . "*DawoodTech NextGen*";

            // Generate and send Offer Letter via WhatsApp
            $startDate = date('d-M-Y');
            $endDate = date('d-M-Y', strtotime('+2 months'));
            $pdfContent = generateOfferLetterHelper($registration['name'], $startDate, $endDate, $registration['tech_name']);

            if ($pdfContent) {
                $tempFile = '../temp/Offer_Letter_' . $id . '_' . time() . '.pdf';
                if (!is_dir('../temp')) mkdir('../temp', 0777, true);
                file_put_contents($tempFile, $pdfContent);

                // Use public URL for WhatsApp API to fetch the file
                $publicFileUrl = BASE_URL . 'temp/' . basename($tempFile);
                whatsappFileApi($registration['mbl_number'], $publicFileUrl, 'Offer_Letter.pdf', $whatsappMsg);
            }

            // Commit transaction
            $conn->commit();

            echo json_encode(['success' => true, 'message' => 'Hired successfully!']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'reject_candidate':
        $id = (int)$_POST['id'];
        $notes = $_POST['notes'] ?? '';

        $sql = "UPDATE registrations 
            SET status = 'rejected', remarks = ?
            WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $notes, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Candidate rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject candidate']);
        }
        $stmt->close();
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
function checkWhatsAppResponse($response)
{
    // Check if response is empty
    if (empty($response)) {
        return [
            'success' => false,
            'message' => 'Empty response from WhatsApp API',
            'raw' => $response
        ];
    }

    // If response is JSON string, decode it
    if (is_string($response)) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to see if it's HTML/other format
            if (str_contains($response, '<html') || str_contains($response, '<!DOCTYPE')) {
                return [
                    'success' => false,
                    'message' => 'Received HTML instead of JSON (possible server error)',
                    'raw' => substr($response, 0, 200) . '...'
                ];
            }
            return [
                'success' => false,
                'message' => 'Invalid JSON response: ' . json_last_error_msg(),
                'error' => json_last_error_msg(),
                'raw' => substr($response, 0, 200) . '...'
            ];
        }
    } else {
        $data = $response;
    }

    // Check for error response
    if (isset($data['code'])) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Unknown error',
            'error_code' => $data['code'],
            'status_code' => $data['data']['status'] ?? 500,
            'details' => $data['data']['details'] ?? [],
            'full_response' => $data
        ];
    }

    // Check for specific WhatsApp API error patterns
    if (isset($data['error'])) {
        return [
            'success' => false,
            'message' => $data['error'] . (isset($data['message']) ? ': ' . $data['message'] : ''),
            'error_code' => $data['error'] ?? 'unknown',
            'full_response' => $data
        ];
    }

    // Check for success response
    if (isset($data['id']) && strpos($data['id'], 'true_') === 0) {
        $result = [
            'success' => true,
            'message' => 'Message processed successfully',
            'message_id' => $data['id']
        ];

        // Add more details if available
        if (isset($data['_data'])) {
            $result['timestamp'] = $data['_data']['Info']['Timestamp'] ?? null;
            $result['chat'] = $data['_data']['Info']['Chat'] ?? null;
            $result['is_from_me'] = $data['_data']['Info']['IsFromMe'] ?? null;
            $result['data'] = $data['_data'];
        }

        $result['full_response'] = $data;

        return $result;
    }

    // Check for alternative success format (some APIs use different structure)
    if (isset($data['sent']) && $data['sent'] === true) {
        return [
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $data['id'] ?? $data['messageId'] ?? null,
            'full_response' => $data
        ];
    }

    // Unknown response format
    return [
        'success' => false,
        'message' => 'Unexpected response format from WhatsApp API',
        'raw_response' => $data
    ];
}
function whatsappApi($chatId, $message)
{
    // Format chat ID properly (remove spaces, ensure 92 format)
    $chatId = preg_replace('/[^0-9]/', '', $chatId);
    if (!str_starts_with($chatId, '92')) {
        $chatId = '92' . ltrim($chatId, '0');
    }

    $params = [
        'instance_id'   => WHATSAPP_INSTANCE_ID,
        'access_token'  => WHATSAPP_ACCESS_TOKEN,
        'chatId'        => $chatId,
        'message'       => $message,
    ];

    $url = WHATSAPP_API_URL . '?' . http_build_query($params);

    // Log the request for debugging
    error_log("WhatsApp API Request: " . json_encode([
        'url' => $url,
        'chatId' => $chatId,
        'message_length' => strlen($message)
    ]));

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false, // For testing, remove in production
        CURLOPT_SSL_VERIFYHOST => false, // For testing, remove in production
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    curl_close($curl);

    // Log raw response for debugging
    error_log("WhatsApp API Raw Response (HTTP $httpCode): " . $response);
    if ($error) {
        error_log("WhatsApp API cURL Error: " . $error);
    }

    $check = checkWhatsAppResponse($response);

    if ($check['success']) {
        error_log("WhatsApp Message Sent Successfully - ID: " . $check['message_id']);
        return [
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $check['message_id'],
            'http_code' => $httpCode
        ];
    } else {
        error_log("WhatsApp API Error: " . $check['message'] . " (Code: " . ($check['error_code'] ?? 'N/A') . ")");
        return [
            'success' => false,
            'message' => "Error: " . $check['message'],
            'error_code' => $check['error_code'] ?? null,
            'http_code' => $httpCode,
            'response' => $response // Return raw response for debugging
        ];
    }
}

function whatsappFileApi($chatId, $fileUrl, $fileName, $caption = "")
{
    $chatId = preg_replace('/[^0-9]/', '', $chatId);
    if (!str_starts_with($chatId, '92')) {
        $chatId = '92' . ltrim($chatId, '0');
    }

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $mimetype = ($ext == 'pdf') ? 'application/pdf' : 'application/octet-stream';

    $params = [
        'instance_id'  => WHATSAPP_INSTANCE_ID,
        'access_token' => WHATSAPP_ACCESS_TOKEN,
        'chatId'       => $chatId,
        'file' => [
            'url'      => $fileUrl,
            'filename' => $fileName,
            'mimetype' => $mimetype
        ],
        'caption'      => $caption
    ];

    $apiUrl = str_replace('/send', '/sendFile', WHATSAPP_API_URL);
    $url = $apiUrl . '?' . http_build_query($params);

    // Log the request for debugging
    error_log("WhatsApp File API Request: " . $url);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Accept: application/json']
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    // Log response
    error_log("WhatsApp File API Response (HTTP $httpCode): " . $response);
    if ($error) {
        error_log("WhatsApp File API cURL Error: " . $error);
    }

    return [
        'success' => ($httpCode == 200),
        'response' => $response,
        'http_code' => $httpCode
    ];
}
