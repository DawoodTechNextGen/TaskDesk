<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include_once '../include/connection.php';

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$new_password = isset($data['new_password']) ? trim($data['new_password']) : '';
$confirm_password = isset($data['confirm_password']) ? trim($data['confirm_password']) : '';

if (empty($new_password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password fields are required']);
    exit;
}

if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

// Hash password before storing
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update password']);
}
