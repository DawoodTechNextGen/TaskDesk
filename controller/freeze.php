<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

date_default_timezone_set('Asia/Karachi');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

/* =========================
   REQUEST FREEZE (Intern)
========================= */
if ($data['action'] === 'request_freeze') {
    $user_id = $_SESSION['user_id'];
    $freeze_start_date = $data['freeze_start_date'] ?? '';
    $freeze_end_date = $data['freeze_end_date'] ?? '';
    $freeze_reason = trim($data['freeze_reason'] ?? '');
    
    // Validate inputs
    if (empty($freeze_start_date) || empty($freeze_end_date) || empty($freeze_reason)) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }
    
    // Validate dates
    $start = new DateTime($freeze_start_date);
    $end = new DateTime($freeze_end_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    // Check if internship is already complete
    $stmt = $conn->prepare("SELECT created_at, internship_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_created_at, $internship_type);
    $stmt->fetch();
    $stmt->close();
    
    $duration_weeks = ($internship_type == 0) ? 4 : 12;
    $internship_end_date = new DateTime($user_created_at);
    $internship_end_date->modify("+{$duration_weeks} weeks");
    
    if ($today >= $internship_end_date) {
        echo json_encode(["success" => false, "message" => "Cannot request freeze - your internship has already been completed"]);
        exit;
    }
    
    if ($start < $today) {
        echo json_encode(["success" => false, "message" => "Start date cannot be in the past"]);
        exit;
    }
    
    if ($end <= $start) {
        echo json_encode(["success" => false, "message" => "End date must be after start date"]);
        exit;
    }
    
    // Check max duration (30 days)
    $duration = $start->diff($end)->days;
    if ($duration > 30) {
        echo json_encode(["success" => false, "message" => "Freeze period cannot exceed 30 days"]);
        exit;
    }
    
    // Check current freeze status
    $stmt = $conn->prepare("SELECT freeze_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();
    
    if ($current_status !== 'active') {
        echo json_encode(["success" => false, "message" => "You already have a pending or active freeze request"]);
        exit;
    }
    
    // Create freeze request
    $stmt = $conn->prepare("
        UPDATE users 
        SET freeze_status = 'freeze_requested',
            freeze_start_date = ?,
            freeze_end_date = ?,
            freeze_reason = ?,
            freeze_requested_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $freeze_start_date, $freeze_end_date, $freeze_reason, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Freeze request submitted successfully. Waiting for supervisor approval."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to submit freeze request"]);
    }
    $stmt->close();
    exit;
}

/* =========================
   GET FREEZE REQUESTS (Supervisor/Admin)
========================= */
if ($data['action'] === 'get_freeze_requests') {
    $supervisor_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Admin sees all, supervisor sees only their interns
    if ($user_role == 1 || $user_role == 4) { // Admin or Manager
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.freeze_start_date, u.freeze_end_date, 
                   u.freeze_reason, u.freeze_requested_at, t.name as technology
            FROM users u
            LEFT JOIN technologies t ON u.tech_id = t.id
            WHERE u.freeze_status = 'freeze_requested'
            ORDER BY u.freeze_requested_at DESC
        ");
        $stmt->execute();
    } else { // Supervisor
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.freeze_start_date, u.freeze_end_date, 
                   u.freeze_reason, u.freeze_requested_at, t.name as technology
            FROM users u
            LEFT JOIN technologies t ON u.tech_id = t.id
            WHERE u.freeze_status = 'freeze_requested' AND u.supervisor_id = ?
            ORDER BY u.freeze_requested_at DESC
        ");
        $stmt->bind_param("i", $supervisor_id);
        $stmt->execute();
    }
    
    $result = $stmt->get_result();
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    echo json_encode(["success" => true, "data" => $requests]);
    $stmt->close();
    exit;
}

/* =========================
   APPROVE FREEZE (Supervisor/Admin)
========================= */
if ($data['action'] === 'approve_freeze') {
    $approver_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $intern_id = (int)($data['user_id'] ?? 0);
    
    if ($intern_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid user ID"]);
        exit;
    }
    
    // Verify permission
    if ($user_role == 3) { // Supervisor
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND supervisor_id = ?");
        $stmt->bind_param("ii", $intern_id, $approver_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            exit;
        }
        $stmt->close();
    } elseif ($user_role != 1 && $user_role != 4) { // Not admin or manager
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
    
    // Approve freeze
    $stmt = $conn->prepare("
        UPDATE users 
        SET freeze_status = 'frozen',
            freeze_approved_by = ?,
            freeze_approved_at = NOW()
        WHERE id = ? AND freeze_status = 'freeze_requested'
    ");
    $stmt->bind_param("ii", $approver_id, $intern_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Get intern details for notification
        $notify_stmt = $conn->prepare("
            SELECT u.name, u.email, r.mbl_number, u.freeze_start_date, u.freeze_end_date, u.freeze_reason
            FROM users u left join registrations r on u.email = r.email  WHERE u.id = ?
        ");
        $notify_stmt->bind_param("i", $intern_id);
        $notify_stmt->execute();
        $notify_result = $notify_stmt->get_result();
        
        if ($intern_data = $notify_result->fetch_assoc()) {
            require_once __DIR__ . '/../include/notification_helper.php';
            
            $whatsappMsg = "Assalam-o-Alaikum " . $intern_data['name'] . ",\n\n"
                . "✅ *Freeze Request Approved*\n\n"
                . "Your internship freeze request has been approved.\n\n"
                . "📅 *Freeze Period:*\n"
                . "Start: " . date('d M Y', strtotime($intern_data['freeze_start_date'])) . "\n"
                . "End: " . date('d M Y', strtotime($intern_data['freeze_end_date'])) . "\n\n"
                . "You cannot access the task system during this period.\n\n"
                . "Best regards,\n*DawoodTech NextGen*";
            
            $htmlContent = '<!DOCTYPE html><html><head><style>
                body{font-family:"Segoe UI",Tahoma;line-height:1.6;color:#333;max-width:800px;margin:auto;}
                .header{background:linear-gradient(135deg,#10b981,#059669);padding:40px;text-align:center;border-radius:10px 10px 0 0;}
                .content{padding:40px;background:#f9f9f9;}
                .card{background:white;padding:30px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}
                .info{background:#f0fdf4;border-left:4px solid #10b981;padding:15px;margin:15px 0;}
                </style></head><body>
                <div class="header"><h1 style="color:white;margin:0;">✅ Freeze Approved</h1></div>
                <div class="content"><div class="card">
                <h2 style="color:#10b981;">Dear ' . htmlspecialchars($intern_data['name']) . ',</h2>
                <p>Your freeze request has been <strong>approved</strong>.</p>
                <div class="info">
                <p><strong>Start:</strong> ' . date('d M Y', strtotime($intern_data['freeze_start_date'])) . '</p>
                <p><strong>End:</strong> ' . date('d M Y', strtotime($intern_data['freeze_end_date'])) . '</p>
                <p><strong>Reason:</strong> ' . htmlspecialchars($intern_data['freeze_reason']) . '</p>
                </div></div></div></body></html>';
            
            sendNotificationFallback([
                'email' => $intern_data['email'],
                'name' => $intern_data['name'],
                'mbl_number' => $intern_data['mbl_number'] ?? '',
                'subject' => 'Freeze Approved - DawoodTech NextGen',
                'html_content' => $htmlContent,
                'whatsapp_msg' => $whatsappMsg
            ]);
        }
        $notify_stmt->close();
        
        echo json_encode(["success" => true, "message" => "Freeze request approved"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to approve freeze request"]);
    }
    $stmt->close();
    exit;
}

/* =========================
   REJECT FREEZE (Supervisor/Admin)
========================= */
if ($data['action'] === 'reject_freeze') {
    $approver_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $intern_id = (int)($data['user_id'] ?? 0);
    
    if ($intern_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid user ID"]);
        exit;
    }
    
    // Verify permission
    if ($user_role == 3) { // Supervisor
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND supervisor_id = ?");
        $stmt->bind_param("ii", $intern_id, $approver_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            exit;
        }
        $stmt->close();
    } elseif ($user_role != 1 && $user_role != 4) { // Not admin or manager
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
    
    // Reject freeze - reset to active
    $stmt = $conn->prepare("
        UPDATE users 
        SET freeze_status = 'active',
            freeze_start_date = NULL,
            freeze_end_date = NULL,
            freeze_reason = NULL,
            freeze_requested_at = NULL
        WHERE id = ? AND freeze_status = 'freeze_requested'
    ");
    $stmt->bind_param("i", $intern_id);
    
    // Get intern details BEFORE resetting
    $notify_stmt = $conn->prepare("
        SELECT u.name, u.email, r.mbl_number, u.freeze_start_date, u.freeze_end_date, u.freeze_reason
        FROM users u left join registrations r on u.email = r.email WHERE u.id = ?
    ");
    $notify_stmt->bind_param("i", $intern_id);
    $notify_stmt->execute();
    $notify_result = $notify_stmt->get_result();
    $intern_data = $notify_result->fetch_assoc();
    $notify_stmt->close();
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        if ($intern_data) {
            require_once __DIR__ . '/../include/notification_helper.php';
            
            $whatsappMsg = "Assalam-o-Alaikum " . $intern_data['name'] . ",\n\n"
                . "❌ *Freeze Request Rejected*\n\n"
                . "Your internship freeze request has been rejected.\n\n"
                . "📅 *Requested Period:*\n"
                . "Start: " . date('d M Y', strtotime($intern_data['freeze_start_date'])) . "\n"
                . "End: " . date('d M Y', strtotime($intern_data['freeze_end_date'])) . "\n\n"
                . "Contact your supervisor for more details.\n\n"
                . "Best regards,\n*DawoodTech NextGen*";
            
            $htmlContent = '<!DOCTYPE html><html><head><style>
                body{font-family:"Segoe UI",Tahoma;line-height:1.6;color:#333;max-width:800px;margin:auto;}
                .header{background:linear-gradient(135deg,#ef4444,#dc2626);padding:40px;text-align:center;border-radius:10px 10px 0 0;}
                .content{padding:40px;background:#f9f9f9;}
                .card{background:white;padding:30px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}
                .info{background:#fef2f2;border-left:4px solid #ef4444;padding:15px;margin:15px 0;}
                </style></head><body>
                <div class="header"><h1 style="color:white;margin:0;">❌ Freeze Rejected</h1></div>
                <div class="content"><div class="card">
                <h2 style="color:#ef4444;">Dear ' . htmlspecialchars($intern_data['name']) . ',</h2>
                <p>Your freeze request has been <strong>rejected</strong>.</p>
                <div class="info">
                <p><strong>Requested Start:</strong> ' . date('d M Y', strtotime($intern_data['freeze_start_date'])) . '</p>
                <p><strong>Requested End:</strong> ' . date('d M Y', strtotime($intern_data['freeze_end_date'])) . '</p>
                </div></div></div></body></html>';
            
            sendNotificationFallback([
                'email' => $intern_data['email'],
                'name' => $intern_data['name'],
                'mbl_number' => $intern_data['mbl_number'] ?? '',
                'subject' => 'Freeze Rejected - DawoodTech NextGen',
                'html_content' => $htmlContent,
                'whatsapp_msg' => $whatsappMsg
            ]);
        }
        
        echo json_encode(["success" => true, "message" => "Freeze request rejected"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to reject freeze request"]);
    }
    $stmt->close();
    exit;
}

/* =========================
   GET USER FREEZE STATUS
========================= */
if ($data['action'] === 'get_freeze_status') {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT freeze_status, freeze_start_date, freeze_end_date, 
               freeze_reason, freeze_requested_at, created_at, internship_type
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if internship is complete
        $duration_weeks = ($row['internship_type'] == 0) ? 4 : 12;
        $internship_end_date = new DateTime($row['created_at']);
        $internship_end_date->modify("+{$duration_weeks} weeks");
        $today = new DateTime();
        
        $row['is_internship_complete'] = ($today >= $internship_end_date);
        
        echo json_encode(["success" => true, "data" => $row]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
    $stmt->close();
    exit;
}

$conn->close();
