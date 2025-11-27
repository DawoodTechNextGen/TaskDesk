<?php
session_start();
require_once '../include/config.php';
include_once "../include/connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email) || empty($password)){
        $_SESSION['error'] = 'All fields are required';
    header('Location:'.BASE_URL.'/login.php');
    exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $stmtTech = $conn->prepare("SELECT t.name,c.approve_status as approve_status FROM technologies t LEFT JOIN certificate c on c.intern_id = ? WHERE t.id = ?");
            $stmtTech->bind_param('ii',$user['id'], $user['tech_id']);
            $stmtTech->execute();
            $resultTech = $stmtTech->get_result();
            $tech = $resultTech->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_password'] = $user['plain_password'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['tech'] = isset($tech) && isset($tech['name']) ? $tech['name'] : null;
$_SESSION['approval_status'] = isset($tech) && isset($tech['approve_status']) ? $tech['approve_status'] : null;
            header('Location:' . BASE_URL . '/index.php');
            exit;
        } else {
            $_SESSION['error'] = 'Invalid password';
            header('Location:'.BASE_URL.'/login.php');
            exit;
        }
    } else {
        $_SESSION['error'] = 'User not found';
        header('Location:'.BASE_URL.'/login.php');
        exit;
    }
}
