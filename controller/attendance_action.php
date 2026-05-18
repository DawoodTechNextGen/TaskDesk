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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

if ($data['action'] === 'status') {
    // Check if internship is complete
    $stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_created_at, $internship_type, $internship_duration);
    $stmt->fetch();
    $stmt->close();

    $start_date = new DateTime($user_created_at);
    $current_date = new DateTime($today);
    
    $duration_weeks = 12;
    if (!empty($internship_duration)) {
        if ($internship_duration === '4 weeks') $duration_weeks = 4;
        elseif ($internship_duration === '8 weeks') $duration_weeks = 8;
        elseif ($internship_duration === '12 weeks') $duration_weeks = 12;
    } else {
        $duration_weeks = ($internship_type == 0) ? 4 : 12;
    }
    $end_date = clone $start_date;
    $end_date->modify("+$duration_weeks weeks");

    $isInternshipComplete = false;
    if ($current_date > $end_date) {
        $isInternshipComplete = true;
    }

    $stmt = $conn->prepare("SELECT id FROM attendance_dtls WHERE user_id = ? AND date = ? AND check_out_time IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $isCheckedIn = $result ? true : false;
    $hasCompletedToday = false;

    echo json_encode([
        "success" => true, 
        "isCheckedIn" => $isCheckedIn,
        "hasCompletedToday" => $hasCompletedToday,
        "isInternshipComplete" => $isInternshipComplete
    ]);
    exit;
}

if ($data['action'] === 'check_in') {
    // Check if internship is complete before allowing check-in
    $stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_created_at, $internship_type, $internship_duration);
    $stmt->fetch();
    $stmt->close();

    $start_date = new DateTime($user_created_at);
    $current_date = new DateTime($today);
    
    $duration_weeks = 12;
    if (!empty($internship_duration)) {
        if ($internship_duration === '4 weeks') $duration_weeks = 4;
        elseif ($internship_duration === '8 weeks') $duration_weeks = 8;
        elseif ($internship_duration === '12 weeks') $duration_weeks = 12;
    } else {
        $duration_weeks = ($internship_type == 0) ? 4 : 12;
    }
    $end_date = clone $start_date;
    $end_date->modify("+$duration_weeks weeks");

    if ($current_date > $end_date) {
        echo json_encode(["success" => false, "message" => "Internship duration ($duration_weeks weeks) completed. Attendance marked automatically."]);
        exit;
    }
    $stmt = $conn->prepare("SELECT id FROM attendance_dtls WHERE user_id = ? AND date = ? AND check_out_time IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        echo json_encode(["success" => false, "message" => "Already checked in"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO attendance_dtls (user_id, date, check_in_time) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $today, $now);
    
    if ($stmt->execute()) {
        // Ensure a master attendance record exists
        $master_stmt = $conn->prepare("INSERT IGNORE INTO attendance (user_id, date, check_in_time, status, attendance_type, total_work_seconds) VALUES (?, ?, ?, 'absent', 1, 0)");
        $master_stmt->bind_param("iss", $user_id, $today, $now);
        $master_stmt->execute();
        $master_stmt->close();

        // Update master check_in_time just in case it's their first check-in
        $master_update = $conn->prepare("UPDATE attendance SET check_in_time = ? WHERE user_id = ? AND date = ? AND check_in_time IS NULL");
        $master_update->bind_param("sis", $now, $user_id, $today);
        $master_update->execute();
        $master_update->close();

        echo json_encode(["success" => true, "message" => "Checked in successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to check in"]);
    }
    $stmt->close();
    exit;
}

if ($data['action'] === 'check_out') {
    $stmt = $conn->prepare("SELECT id, check_in_time FROM attendance_dtls WHERE user_id = ? AND date = ? AND check_out_time IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        echo json_encode(["success" => false, "message" => "Not checked in"]);
        exit;
    }

    $dtls_id = $result['id'];
    $check_in_time = $result['check_in_time'];
    $duration = strtotime($now) - strtotime($check_in_time);

    $stmt = $conn->prepare("UPDATE attendance_dtls SET check_out_time = ?, duration = ? WHERE id = ?");
    $stmt->bind_param("sii", $now, $duration, $dtls_id);
    
    if ($stmt->execute()) {
        // Update master attendance table
        $master_stmt = $conn->prepare("UPDATE attendance SET check_out_time = ?, total_work_seconds = total_work_seconds + ?, status = CASE WHEN (total_work_seconds + ?) >= 10800 THEN 'present' ELSE 'absent' END WHERE user_id = ? AND date = ?");
        $master_stmt->bind_param("siiis", $now, $duration, $duration, $user_id, $today);
        $master_stmt->execute();
        $master_stmt->close();

        echo json_encode(["success" => true, "message" => "Checked out successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to check out"]);
    }
    $stmt->close();
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
$conn->close();
