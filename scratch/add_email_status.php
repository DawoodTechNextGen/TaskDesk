<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

global $conn;

// Check if column already exists
$checkSql = "SHOW COLUMNS FROM registrations LIKE 'email_status'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows > 0) {
    echo json_encode([
        'success' => true,
        'message' => "Column email_status already exists in registrations table."
    ]);
    exit;
}

// Alter table to add column
$alterSql = "ALTER TABLE registrations ADD COLUMN email_status TINYINT NOT NULL DEFAULT 0 AFTER status";
if ($conn->query($alterSql)) {
    echo json_encode([
        'success' => true,
        'message' => "Successfully added column email_status to registrations table."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "Failed to add column: " . $conn->error
    ]);
}
?>
