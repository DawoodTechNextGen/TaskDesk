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
    
    if (!isset($_SESSION['password_otp']) || !isset($_SESSION['pending_password_update'])) {
        echo json_encode(['success' => false, 'error' => 'No pending password update request found. Please resubmit.']);
        exit;
    }
    
    if (time() > $_SESSION['password_otp_expiry']) {
        echo json_encode(['success' => false, 'error' => 'OTP has expired. Please try again.']);
        exit;
    }
    
    if (strval($_SESSION['password_otp']) !== strval($otp)) {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP code.']);
        exit;
    }
    
    // OTP is correct! Apply changes
    $new_password = $_SESSION['pending_password_update'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("UPDATE users SET plain_password = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_password, $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Send warning email to current admin email
        $subject = "Security Alert: TaskDesk Password Changed";
        $htmlContent = "
            <h2>Account Password Changed</h2>
            <p>Hello,</p>
            <p>This is a warning notification that your TaskDesk account password has been successfully updated.</p>
            <p>If you did not authorize this change, please contact your administrator or security team immediately.</p>
            <br>
            <p>Best regards,<br>TaskDesk Security Team</p>
        ";
        sendEmailPHPMailer($_SESSION['user_email'], $_SESSION['user_name'], $subject, $htmlContent, null, '', 'primary');
        
        // Clear session OTP states
        unset($_SESSION['password_otp']);
        unset($_SESSION['password_otp_expiry']);
        unset($_SESSION['pending_password_update']);
        
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
    }
    exit;
}

// Normal request flow
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

// Generate OTP
$otp = strval(rand(100000, 999999));
$_SESSION['password_otp'] = $otp;
$_SESSION['password_otp_expiry'] = time() + 600; // 10 minutes
$_SESSION['pending_password_update'] = $new_password;

// Send OTP to the current email address
$subject = "TaskDesk Password Reset Verification Code";
$htmlContent = "
    <h2>Verify Your Password Update</h2>
    <p>Hello,</p>
    <p>You requested to update your account password on TaskDesk. Please use the following 6-digit One-Time Password (OTP) to complete verification:</p>
    <div style='background-color:#f3f4f6; padding:15px; text-align:center; font-size:24px; font-weight:bold; letter-spacing:5px; border-radius:8px; margin:20px 0;'>
        {$otp}
    </div>
    <p>This verification code is valid for 10 minutes. If you did not make this request, you can ignore this email.</p>
    <br>
    <p>Best regards,<br>TaskDesk Security Team</p>
";

$sent = sendEmailPHPMailer($_SESSION['user_email'], $_SESSION['user_name'], $subject, $htmlContent, null, '', 'primary');
if (!$sent) {
    $sent = sendEmailPHPMailer($_SESSION['user_email'], $_SESSION['user_name'], $subject, $htmlContent, null, '', 'gmail');
}

if ($sent) {
    echo json_encode(['success' => true, 'otp_required' => true, 'message' => 'Verification code sent to your email.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send verification OTP.']);
}
