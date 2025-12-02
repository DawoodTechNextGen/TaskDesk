<?php
include '../include/connection.php';
header('Content-Type: application/json');
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// Function to generate offer letter PDF
function generateOfferLetterPDF($name, $startDate, $endDate, $tech_name)
{
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $bgImage = "https://i.ibb.co/7t0xSD60/offerletter.png";

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: Arial, sans-serif;
                    background-image: url('.$bgImage.');
                    background-size: cover;
                    background-repeat: no-repeat;
                }
                .content-box {
                    margin: 200px 80px 80px 80px;
                    padding: 20px 40px;
                    height: 700px;
                    background: rgba(255,255,255,0);
                    font-size: 13px;
                }
                .title { font-size: 22px; font-weight: bold; margin-bottom: 10px; }
                .section { margin-bottom: 15px; }
                .signature { margin-top: 50px; }
            </style>
        </head>
        <body>
            <div class="content-box">
                <div class="section" style="text-align:right;">
                    <strong>Date:</strong> '.htmlspecialchars($startDate).'
                </div>
                <div class="section">
                    <strong>To:</strong><br>
                    '.htmlspecialchars($name).'<br>
                    <strong>Designation:</strong> Intern – '.htmlspecialchars($tech_name).'<br>
                    DawoodTech NextGen
                </div>
                <div class="section title">
                    Internship Offer – '.htmlspecialchars($tech_name).'
                </div>
                <div class="section">
                    <p>Dear '.htmlspecialchars($name).',</p>
                    <p>We are pleased to offer you an internship opportunity from 
                    <strong>'.$startDate.' to '.$endDate.'</strong> at DawoodTech NextGen as a 
                    <strong>'.htmlspecialchars($tech_name).'</strong>.</p>
                    <p>This internship will allow you to enhance your skills, gain practical exposure, 
                    and contribute to real-world projects under professional guidance.</p>
                    <p>We look forward to your valuable contribution and growth during this program.</p>
                </div>
                <div class="signature">
                    <strong>Sincerely,</strong><br>
                    Qamar Naveed<br>
                    Founder<br>
                    DawoodTech NextGen
                </div>
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        return null;
    }
}

// Modern email function combining credentials and offer letter
function sendWelcomeEmailWithOfferLetter($toEmail, $name, $password, $role, $tech_name, $tech_id)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = '83769ecefdbd49';
        $mail->Password   = '57a469f363c058';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;

        $mail->setFrom('no-reply@dawoodtechnextgen.org', 'DawoodTech NextGen HR');
        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to DawoodTech NextGen - Your Internship Offer & Credentials';
        
        // Calculate dates for internship
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+2 months'));
        
        // Modern HTML email template
        $loginUrl = 'https://dawoodtech.org/taskdesk/login';
        
        $mailContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 700px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 40px 30px; background: #f9f9f9; }
                .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 20px 0; }
                .credentials { background: #f0f7ff; border-left: 4px solid #667eea; }
                .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600; margin: 15px 0; }
                .footer { text-align: center; padding: 30px; color: #666; font-size: 14px; border-top: 1px solid #eee; }
                .highlight { color: #667eea; font-weight: 600; }
                .icon { color: #667eea; margin-right: 10px; }
                ul { padding-left: 20px; }
                li { margin-bottom: 8px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 style="margin:0; font-size: 28px;">🎉 Welcome to DawoodTech NextGen!</h1>
                <p style="opacity: 0.9; font-size: 18px; margin-top: 10px;">Your Journey Begins Here</p>
            </div>
            
            <div class="content">
                <div class="card">
                    <h2 style="color: #667eea; margin-top: 0;">Dear ' . htmlspecialchars($name) . ',</h2>
                    <p>We are thrilled to welcome you to the DawoodTech NextGen family! Congratulations on being selected for the <span class="highlight">' . htmlspecialchars($tech_name) . '</span> internship program.</p>
                    
                    <div class="card credentials">
                        <h3 style="margin-top: 0;">🔐 Your Login Credentials</h3>
                        <p><strong>Email:</strong> ' . htmlspecialchars($toEmail) . '</p>
                        <p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
                        <a href="' . $loginUrl . '" class="btn-primary">Access Your Dashboard</a>
                        <p style="font-size: 14px; color: #666;"><em>Please change your password after first login for security.</em></p>
                    </div>
                    
                    <h3>📋 Internship Details</h3>
                    <ul>
                        <li><strong>Duration:</strong> ' . $startDate . ' to ' . $endDate . ' (2 Months)</li>
                        <li><strong>Technology:</strong> ' . htmlspecialchars($tech_name) . '</li>
                        <li><strong>Reporting Date:</strong> ' . $startDate . '</li>
                    </ul>
                    
                    <h3>🚀 What to Expect</h3>
                    <ul>
                        <li>Real-world project experience</li>
                        <li>Mentorship from industry experts</li>
                        <li>Skill development workshops</li>
                        <li>Certificate upon successful completion</li>
                    </ul>
                    
                    <p>Your offer letter is attached to this email. Please review it carefully.</p>
                    
                    <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                        <strong>Next Steps:</strong><br>
                        1. Login to the portal using credentials above<br>
                        2. Complete your profile<br>
                        3. Join the onboarding session (details will be shared soon)<br>
                        4. Start your first task!
                    </p>
                </div>
            </div>
            
            <div class="footer">
                <p>Need help? Contact us at: support@dawoodtechnextgen.org</p>
                <p>© ' . date('Y') . ' DawoodTech NextGen. All rights reserved.</p>
                <p style="font-size: 12px; color: #999;">This is an automated email, please do not reply.</p>
            </div>
        </body>
        </html>';

        $mail->Body = $mailContent;
        
        // Generate and attach offer letter PDF
        $pdfContent = generateOfferLetterPDF($name, $startDate, $endDate, $tech_name);
        if ($pdfContent) {
            $fileName = 'Offer_Letter_' . preg_replace('/\s+/', '_', $name) . '.pdf';
            $mail->addStringAttachment($pdfContent, $fileName, 'base64', 'application/pdf');
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_internees':
        $stmt = $conn->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.tech_id,
                t.name AS tech_name,
                DATE(u.created_at) AS joining_date,
                (
                    SELECT ROUND(
                        (COUNT(CASE WHEN status = 'complete' THEN 1 END) / COUNT(*)) * 100
                    )
                    FROM tasks 
                    WHERE assign_to = u.id
                ) AS completion_rate,
                TIMESTAMPDIFF(MONTH, u.created_at, NOW()) AS months_completed,
                c.approve_status
            FROM users u
            LEFT JOIN technologies t ON u.tech_id = t.id
            LEFT JOIN certificate c ON c.intern_id = u.id
            WHERE u.user_role = 2
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
        $name = trim($_POST['name']);
        $password = $_POST['password'] ?? '';
        $role = (int)$_POST['role'];
        $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : null;
        $email = trim($_POST['email']);

        if (empty($name) || empty($password) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        // Generate a secure password if not provided
        if (empty($password)) {
            $password = bin2hex(random_bytes(4)); // 8 character password
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, password, user_role, tech_id, email, plain_password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssiss', $name, $hashed, $role, $tech_id, $email, $password);

       if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        if ($role == 2) {
            // Insert certificate
            $stmt = $conn->prepare("INSERT INTO certificate (intern_id) VALUES (?)");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();

            // Get tech name
            $tech_name = '';
            if ($tech_id) {
                $t = $conn->prepare("SELECT name FROM technologies WHERE id = ?");
                $t->bind_param('i', $tech_id);
                $t->execute();
                $techResult = $t->get_result()->fetch_assoc();
                $tech_name = $techResult['name'] ?? 'Intern';
            }

            // QUEUE THE EMAIL (not send now)
            $data = json_encode([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'tech_name' => $tech_name,
                'tech_id' => $tech_id
            ]);

            $queueStmt = $conn->prepare("
                INSERT INTO email_queue (to_email, to_name, template, data) 
                VALUES (?, ?, 'welcome_offer', ?)
            ");
            $queueStmt->bind_param('sss', $email, $name, $data);
            $queueStmt->execute();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Internee created successfully! Welcome email queued.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
    break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = (int)$_POST['role'];
        $tech_id = !empty($_POST['tech_id']) ? (int)$_POST['tech_id'] : null;
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($name) || !in_array($role, ['3', '2'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, plain_password = ? , email = ?, password = ?, user_role = ?, tech_id = ? WHERE id = ?");
            $stmt->bind_param('sssssii', $name, $password, $email, $hashed, $role, $tech_id, $id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Updated successfully! Credentials emailed.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, user_role = ?, tech_id = ? WHERE id = ?");
            $stmt->bind_param('sssii', $name, $email, $role, $tech_id, $id);
            $success = $stmt->execute();
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Updated successfully!' : 'Update failed'
            ]);
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Deleted successfully!' : 'Cannot delete user'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}