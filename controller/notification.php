<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

if ($data['action'] === 'get') {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tasks WHERE assign_to = ? AND notification = 0");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $tasks = $result->fetch_assoc();

        echo json_encode([
            "success" => true,
            "data"   => (int)$tasks['total']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'getApprovals') {
    $user_email = $_SESSION['user_email'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM approvals WHERE email = ? AND notification = 0");
    $stmt->bind_param("s", $user_email);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $tasks = $result->fetch_assoc();

        echo json_encode([
            "success" => true,
            "data"   => (int)$tasks['total']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'update') {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE tasks SET notification = 1 WHERE assign_to = ? AND notification = 0");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'updateApprovals') {
    $user_email= $_SESSION['user_email'];

    $stmt = $conn->prepare("UPDATE approvals SET notification = 1 WHERE email = ? AND notification = 0");
    $stmt->bind_param("s", $user_email);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}