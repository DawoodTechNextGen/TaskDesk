<?php
// Simulate POST request
$_POST['action'] = 'update_registration_status';
$_POST['id'] = 12; // candidate Sher Dad (status 'new' in registrations)
$_POST['status'] = 'contact';
$_POST['send_email'] = '1';
$_POST['email_message'] = 'Testing message content visibility';
$_POST['email'] = 'test@example.com';

// Mock session
session_start();
$_SESSION['user_role'] = 1; // Admin
$_SESSION['user_id'] = 1;

// Include controller
try {
    include __DIR__ . '/../controller/registrations.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
