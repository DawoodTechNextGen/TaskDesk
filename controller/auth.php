<?php
require_once '../include/config.php';
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";
include_once "../libs/functions.php";
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

/** ====================== SIGNUP ====================== **/
if ($data['action'] === 'signup') {
    $name = $conn->real_escape_string(trim($data['name']));
    $email = $conn->real_escape_string(trim($data['email']));
    $password = trim($data['password']);

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    // // ✅ Check email domain
    // if (!preg_match("/^[a-zA-Z0-9._%+-]+@bismillah\.com\.pk$/", $email)) {
    //     echo json_encode(["success" => false, "message" => "Please Provide a valid email address"]);
    //     exit;
    // }

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already registered"]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, plain_password, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $name, $email, $password, $hashedPassword);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "User registered successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Signup failed"]);
    }

    $stmt->close();
}


/** ====================== LOGIN ====================== **/
if ($data['action'] === 'login') {
    $email = $conn->real_escape_string(trim($data['email']));
    $password = trim($data['password']);
    // $remember = $data['remember'] ?? false;

    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "All Fields are required"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, name, plain_password,email,password,user_role FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_password'] = $user['plain_password'];
            $_SESSION['user_role'] = $user['user_role'];
            // if ($remember) {
            //     setcookie("user_id", $user['id'], time() + (7 * 24 * 60 * 60), "/");
            // }

            echo json_encode(["success" => true, "message" => "Login successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid password"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid Credential User not found"]);
    }

    $stmt->close();
}
if ($data['action'] === 'send-code') {
    $email = $conn->real_escape_string(trim($data['email']));
    $code = generateSixDigitCode();
    $stmt = $conn->prepare("UPDATE users SET otp =? where email = ?");
    $stmt->bind_param("is", $code, $email);
    if ($stmt->execute()) {
        // Send mail with the OTP
        $subject = "Your Verification Code";
        $message = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; margin:0; padding:0;">
  <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
    <tr>
      <td style="background:#4a90e2; color:#ffffff; text-align:center; padding:20px; border-top-left-radius:10px; border-top-right-radius:10px;">
        <h2 style="margin:0;">Verification Code</h2>
      </td>
    </tr>
    <tr>
      <td style="padding:30px; color:#333333; font-size:15px; line-height:1.6;">
        <p>Dear User,</p>
        <p>Thank you for using our service. Please use the verification code below:</p>
        <div style="text-align:center; margin:30px 0;">
          <span style="display:inline-block; font-size:24px; font-weight:bold; color:#4a90e2; background:#f0f7ff; padding:15px 30px; border-radius:8px; border:1px solid #d0e6ff;">
            ' . $code . '
          </span>
        </div>
        <p>This code will expire in <strong>10 minutes</strong>.</p>
        <p>If you didnt request this code, please ignore this email.</p>
      </td>
    </tr>
    <tr>
      <td style="background:#f4f6f8; text-align:center; padding:15px; font-size:12px; color:#777777; border-bottom-left-radius:10px; border-bottom-right-radius:10px;">
        ©' . date("Y") . ' BTL. All rights reserved.
      </td>
    </tr>
  </table>
</body>
</html>';

        $headers = $email;

        $status = Send_Email($subject, $message, $email);

        if (!$status["error"]) {
            echo json_encode(["success" => true, "message" => "Check Your Email. Verification Code sent to this email address"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to send email", "error" => $status["error_msg"]]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update OTP"]);
    }
}
function generateSixDigitCode()
{
    return mt_rand(100000, 999999);
}
$conn->close();
