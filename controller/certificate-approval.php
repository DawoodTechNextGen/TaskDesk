<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

if ($_POST['action'] === 'approve') {
    $intern_id = $_POST['id'];

    // Validation: Check if internship duration is completed
    $check_stmt = $conn->prepare("SELECT created_at, internship_type FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $intern_id);
    $check_stmt->execute();
    $user_data = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($user_data) {
        $created_at = new DateTime($user_data['created_at']);
        $now = new DateTime();
        $duration_weeks = ($user_data['internship_type'] == 0) ? 4 : 12;
        $required_end_date = clone $created_at;
        $required_end_date->modify("+$duration_weeks weeks");

        if ($now < $required_end_date) {
            echo json_encode(['success' => false, 'message' => "Cannot approve. Internship duration ($duration_weeks weeks) not yet completed."]);
            exit;
        }
    }

    // Check if record exists in certificate table
    $stmt = $conn->prepare("SELECT id FROM certificate WHERE intern_id = ?");
    $stmt->bind_param("i", $intern_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE certificate SET approve_status = 1 WHERE intern_id = ?");
        $stmt->bind_param("i", $intern_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO certificate (intern_id, approve_status, created_at) VALUES (?, 1, NOW())");
        $stmt->bind_param("i", $intern_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Certificate Approved Successfully']);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update certificate status"]);
    }
    $stmt->close();
}

$conn->close();
