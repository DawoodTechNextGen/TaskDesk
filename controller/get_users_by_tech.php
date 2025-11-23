<?php
session_start();
include_once "../include/connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tech_id = (int)($input['tech_id'] ?? 0);

if ($tech_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid technology']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM users WHERE tech_id = ? AND user_role = '2' ORDER BY name");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    'success' => true,
    'users' => $users
]);