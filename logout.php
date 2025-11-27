<?php
session_start();
require_once './include/config.php'; // This should define $conn

// Check if $conn is defined, if not include connection.php
if (!isset($conn)) {
    include_once './include/connection.php';
}

// Clear remember me token if exists and user is logged in
if (isset($_SESSION['user_id']) && isset($conn)) {
    try {
        $clear_stmt = $conn->prepare("DELETE FROM user_remember_tokens WHERE user_id = ?");
        $clear_stmt->bind_param("i", $_SESSION['user_id']);
        $clear_stmt->execute();
        $clear_stmt->close();
    } catch (Exception $e) {
        // Log error but don't stop logout process
        error_log("Error clearing remember token: " . $e->getMessage());
    }
}

// Clear remember me cookie
setcookie('remember_me', '', time() - 3600, '/', '', false, true);

// Destroy session
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
?>