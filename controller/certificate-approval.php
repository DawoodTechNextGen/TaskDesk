<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

if ($_POST['action'] === 'approve') {
    $intern_id = $_POST['id'];
    $stmt = $conn->prepare("UPDATE certificate SET approve_status = 1 where intern_id = ?");
    $stmt->bind_param("i", $intern_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Certificate Approved Successfully']);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to insert time log"]);
    }
    $stmt->close();
}

$conn->close();
