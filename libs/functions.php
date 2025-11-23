<?php
require 'PHPMailer.php';
require 'SMTP.php';
require 'Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function Send_Email($subject, $mail_text, $mail_to)
{
    $email_status = [
        "error" => false,
        "error_msg" => null
    ];

    try {
        date_default_timezone_set("Asia/Karachi");
        $mail   = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = '172.16.1.5';
        $mail->Port       = 25;
        $mail->SMTPSecure = false;
        $mail->SMTPAuth   = true;
        $mail->Username = 'alerts@bismillah.com.pk';
        $mail->Password = '*num*369';
        $mail->setFrom('alerts@bismillah.com.pk', 'BTL Alerts');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $mail_text;
        $mail->addAddress($mail_to);

        $mail->send();
    } catch (Exception $e) {
        $email_status["error"] = true;
        $email_status["error_msg"] = $e->getMessage(); // fixed
    }

    return $email_status;
}
