<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include_once '../include/connection.php';
include_once '../include/notification_helper.php';

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Check if this is OTP verification action
$action = $data['action'] ?? '';
if ($action === 'verify_otp') {
    $otp = isset($data['otp']) ? trim($data['otp']) : '';
    if (empty($otp)) {
        echo json_encode(['success' => false, 'error' => 'OTP code is required']);
        exit;
    }
    
    if (!isset($_SESSION['profile_otp']) || !isset($_SESSION['pending_profile_update'])) {
        echo json_encode(['success' => false, 'error' => 'No pending profile update request found. Please resubmit.']);
        exit;
    }
    
    if (time() > $_SESSION['profile_otp_expiry']) {
        echo json_encode(['success' => false, 'error' => 'OTP has expired. Please try again.']);
        exit;
    }
    
    if (strval($_SESSION['profile_otp']) !== strval($otp)) {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP code.']);
        exit;
    }
    
    // OTP is correct! Apply changes
    $pending = $_SESSION['pending_profile_update'];
    $newName = $pending['name'];
    $newEmail = $pending['email'];
    $oldEmail = $_SESSION['user_email'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newName, $newEmail, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Send security warning email to old/previous email address
        $subject = "Security Alert: TaskDesk Account Updated";
        $htmlContent = "
            <h2>Account Details Changed</h2>
            <p>Hello,</p>
            <p>This is a warning notification that your TaskDesk account profile details (Name and/or Email address) have been successfully updated.</p>
            <p><strong>New Name:</strong> {$newName}</p>
            <p><strong>New Email:</strong> {$newEmail}</p>
            <p>If you did not authorize this change, please contact your administrator or security team immediately.</p>
            <br>
            <p>Best regards,<br>TaskDesk Security Team</p>
        ";
        sendEmailPHPMailer($oldEmail, $_SESSION['user_name'], $subject, $htmlContent, null, '', 'primary');
        
        // Update session
        $_SESSION['user_name'] = $newName;
        $_SESSION['user_email'] = $newEmail;
        
        // Clear session OTP states
        unset($_SESSION['profile_otp']);
        unset($_SESSION['profile_otp_expiry']);
        unset($_SESSION['pending_profile_update']);
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
    exit;
}

// Normal update request flow
$name = isset($data['name']) ? trim($data['name']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name is required']);
    exit;
}
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

// Check if email has changed
$emailChanged = strtolower($email) !== strtolower($_SESSION['user_email']);

if ($emailChanged) {
    // Generate OTP
    $otp = strval(rand(100000, 999999));
    $_SESSION['profile_otp'] = $otp;
    $_SESSION['profile_otp_expiry'] = time() + 600; // 10 minutes
    $_SESSION['pending_profile_update'] = [
        'name' => $name,
        'email' => $email
    ];
    
    // Send OTP to the NEW email address to verify ownership
    $subject = "TaskDesk Profile Verification Code";
    $htmlContent = "
        <h2>Verify Your Email Update</h2>
        <p>Hello,</p>
        <p>You requested to update your email address on your TaskDesk account. Please use the following 6-digit One-Time Password (OTP) to complete verification:</p>
        <div style='background-color:#f3f4f6; padding:15px; text-align:center; font-size:24px; font-weight:bold; letter-spacing:5px; border-radius:8px; margin:20px 0;'>
            {$otp}
        </div>
        <p>This verification code is valid for 10 minutes. If you did not make this request, you can ignore this email.</p>
        <br>
        <p>Best regards,<br>TaskDesk Security Team</p>
    ";
    
    $sent = sendEmailPHPMailer($email, $name, $subject, $htmlContent, null, '', 'primary');
    if (!$sent) {
        // Try fallback
        $sent = sendEmailPHPMailer($email, $name, $subject, $htmlContent, null, '', 'gmail');
    }
    
    if ($sent) {
        echo json_encode(['success' => true, 'otp_required' => true, 'message' => 'Verification code sent to your new email.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send verification OTP.']);
    }
} else {
    // No email change, only name change. Save immediately!
    try {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $user_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['user_name'] = $name;
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
}
