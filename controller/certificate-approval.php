<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";
require_once __DIR__ . '/../include/certificate_helper.php';
ensureCertificateApprovedAtColumn($conn);


if ($_POST['action'] === 'get_cert_id') {
    $intern_id = (int)$_POST['id'];
    
    $stmt = $conn->prepare("SELECT id FROM certificate WHERE intern_id = ?");
    $stmt->bind_param("i", $intern_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cert_id = null;
    if ($row = $result->fetch_assoc()) {
        $cert_id = $row['id'];
    } else {
        $ins = $conn->prepare("INSERT INTO certificate (intern_id, approve_status, verified, approved_at, created_at) VALUES (?, 1, 1, NOW(), NOW())");
        $ins->bind_param("i", $intern_id);
        $ins->execute();
        $cert_id = $conn->insert_id;
        $ins->close();
    }
    $stmt->close();
    
    echo json_encode(["success" => true, "cert_id" => $cert_id]);
    exit;
}

if ($_POST['action'] === 'approve') {
    // Only Admin (1) and Manager (4) can approve certificates
    if (!isset($_SESSION['user_role']) || !in_array((int)$_SESSION['user_role'], [1, 4], true)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admins and Managers can approve certificates.']);
        exit;
    }
    $intern_id = $_POST['id'];

    /*
    // Validation: Check if internship duration is completed
    $check_stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $intern_id);
    $check_stmt->execute();
    $user_data = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($user_data) {
        $created_at = new DateTime($user_data['created_at']);
        $now = new DateTime();
        $duration_weeks = 12; // Default
        if (!empty($user_data['internship_duration'])) {
            if ($user_data['internship_duration'] === '4 weeks') $duration_weeks = 4;
            elseif ($user_data['internship_duration'] === '8 weeks') $duration_weeks = 8;
            elseif ($user_data['internship_duration'] === '12 weeks') $duration_weeks = 12;
        } else {
            $duration_weeks = ($user_data['internship_type'] == 0) ? 4 : 12;
        }
        $required_end_date = clone $created_at;
        $required_end_date->modify("+$duration_weeks weeks");

        if ($now < $required_end_date) {
            echo json_encode(['success' => false, 'message' => "Cannot approve. Internship duration ($duration_weeks weeks) not yet completed."]);
            exit;
        }
    }
    */

    // Check if record exists in certificate table
    $stmt = $conn->prepare("SELECT id FROM certificate WHERE intern_id = ?");
    $stmt->bind_param("i", $intern_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();

    $cert_id = null;
    if ($exists) {
        $id_stmt = $conn->prepare("SELECT id FROM certificate WHERE intern_id = ?");
        $id_stmt->bind_param("i", $intern_id);
        $id_stmt->execute();
        $id_stmt->bind_result($existing_id);
        if ($id_stmt->fetch()) {
            $cert_id = $existing_id;
        }
        $id_stmt->close();

        // Issuing a certificate also marks it QR verified, so it doesn't have to be
        // toggled by hand afterwards (it can still be unmarked from the list).
        $stmt = $conn->prepare("UPDATE certificate SET approve_status = 1, verified = 1, approved_at = COALESCE(approved_at, NOW()) WHERE intern_id = ?");
        $stmt->bind_param("i", $intern_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO certificate (intern_id, approve_status, verified, approved_at, created_at) VALUES (?, 1, 1, NOW(), NOW())");
        $stmt->bind_param("i", $intern_id);
    }

    if ($stmt->execute()) {
        if (!$exists) {
            $cert_id = $conn->insert_id;
        }

        // Fetch intern name for logging
        $log_user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $log_user_stmt->bind_param("i", $intern_id);
        $log_user_stmt->execute();
        $log_user_res = $log_user_stmt->get_result()->fetch_assoc();
        $internName = $log_user_res['name'] ?? 'ID ' . $intern_id;
        $log_user_stmt->close();
        logActivity('Approve Certificate', "Approved certificate for intern $internName (ID $intern_id)");

        // Fetch intern details for PDF and Email
        $user_stmt = $conn->prepare("
            SELECT u.name, u.email, u.created_at, u.internship_type, u.internship_duration, t.name AS tech_name 
            FROM users u 
            LEFT JOIN technologies t ON u.tech_id = t.id 
            WHERE u.id = ?
        ");
        $user_stmt->bind_param("i", $intern_id);
        $user_stmt->execute();
        $user_data = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        if ($user_data && !empty($user_data['email'])) {
            // Calculate dates
            $duration = $user_data['internship_duration'];
            if (empty($duration)) {
                $duration = ($user_data['internship_type'] == 0) ? '4 weeks' : '12 weeks';
            }
            $duration_str = '+' . $duration;
            
            $start_date = date('j F Y', strtotime($user_data['created_at']));
            $end_date = date('j F Y', strtotime($user_data['created_at'] . ' ' . $duration_str));
            $issue_date = $end_date;

            // Generate verification URL
            $verify_url = BASE_URL . 'verify_certificate.php?id=' . $cert_id;

            // Include helper files
            require_once '../include/pdf_helper.php';
            require_once '../include/notification_helper.php';

            // Generate PDF using helper
            $pdf_content = generateCertificateHelper(
                $user_data['name'],
                $start_date,
                $end_date,
                $user_data['tech_name'] ?: 'Technology',
                $issue_date,
                $user_data['internship_type'] ?: 1,
                $verify_url
            );

            if ($pdf_content) {
                // Email PDF to intern
                $subject = "Congratulations! Your Internship Certificate is Approved - DawoodTech NextGen";
                
                $current_year = date('Y');
                $email_name = htmlspecialchars($user_data['name']);
                $email_tech = htmlspecialchars($user_data['tech_name'] ?: 'Technology');
                $email_start = htmlspecialchars($start_date);
                $email_end = htmlspecialchars($end_date);
                $email_verify = htmlspecialchars($verify_url);

                $html_content = "
                <div style=\"background-color: #F8FAFC; padding: 40px 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100%;\">
                    <div style=\"max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0;\">
                        <div style=\"background-color: #FFFFFF; padding: 28px 32px; text-align: center; border-bottom: 4px solid #2563EB;\">
                            <img src=\"cid:logo_cid\" alt=\"DawoodTech NextGen\" style=\"max-height: 52px; width: auto; max-width: 100%; height: auto; display: inline-block;\">
                        </div>
                        <div style=\"padding: 40px 32px; color: #0F172A; line-height: 1.6; font-size: 16px;\">
                            <div style=\"margin-bottom: 24px;\">
                                <span style=\"background-color: #E0E7FF; color: #2563EB; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block;\">Internship Accomplishment</span>
                            </div>
                            <p style=\"margin-top: 0; font-weight: 700; font-size: 22px; color: #1E293B; letter-spacing: -0.3px;\">Dear {$email_name},</p>
                            <p style=\"color: #334155;\">Congratulations! We are delighted to inform you that your internship certificate has been approved and issued by our administration team.</p>
                            
                            <div style=\"background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px; margin: 24px 0;\">
                                <h3 style=\"margin-top: 0; font-size: 16px; color: #1E293B; font-weight: 600;\">Internship Details:</h3>
                                <table style=\"width: 100%; font-size: 14px; color: #475569; border-collapse: collapse;\">
                                    <tr>
                                        <td style=\"padding: 6px 0; font-weight: 600; width: 120px;\">Internship:</td>
                                        <td style=\"padding: 6px 0;\">{$email_tech}</td>
                                    </tr>
                                    <tr>
                                        <td style=\"padding: 6px 0; font-weight: 600;\">Start Date:</td>
                                        <td style=\"padding: 6px 0;\">{$email_start}</td>
                                    </tr>
                                    <tr>
                                        <td style=\"padding: 6px 0; font-weight: 600;\">End Date:</td>
                                        <td style=\"padding: 6px 0;\">{$email_end}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style=\"color: #334155;\">We sincerely appreciate your hard work, dedication, and valuable contributions to <strong>DawoodTech NextGen</strong> during your internship. We wish you the absolute best in your future academic and professional endeavors.</p>
                            
                            <div style=\"text-align: center; margin: 32px 0 16px 0;\">
                                <a href=\"{$email_verify}\" target=\"_blank\" style=\"background-color: #2563EB; color: #FFFFFF; padding: 12px 30px; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-block; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); text-align: center;\">
                                    Verify Your Certificate
                                </a>
                            </div>

                            <div style=\"margin-top: 32px; padding-top: 24px; border-top: 1px solid #E2E8F0; text-align: center;\">
                                <p style=\"margin: 0; font-size: 13px; color: #64748B;\">Please find your certificate of completion attached to this email.</p>
                            </div>
                        </div>
                        <div style=\"background-color: #1E293B; padding: 28px 24px; text-align: center; font-size: 12px; color: #94A3B8; border-top: 1px solid #E2E8F0;\">
                            <p style=\"margin: 0 0 8px 0; font-weight: 600; color: #FFFFFF; font-size: 13px;\">DawoodTech NextGen</p>
                            <p style=\"margin: 0; font-size: 11px;\">&copy; {$current_year} DawoodTech. All rights reserved.</p>
                        </div>
                    </div>
                </div>";

                $pdf_filename = "Certificate_" . preg_replace('/[^a-zA-Z0-9]/', '_', $user_data['name']) . ".pdf";

                // Check hourly limit for primary SMTP (30 emails per hour limit)
                $email_type = 'primary';
                if (isset($conn) && $conn) {
                    try {
                        $sender = MAIL_USERNAME;
                        $limit_stmt = $conn->prepare("SELECT COUNT(*) as total FROM email_sent_logs WHERE sender_email = ? AND sent_at > NOW() - INTERVAL 1 HOUR");
                        if ($limit_stmt) {
                            $limit_stmt->bind_param('s', $sender);
                            $limit_stmt->execute();
                            $limit_result = $limit_stmt->get_result()->fetch_assoc();
                            $sentCount = $limit_result['total'] ?? 0;
                            $limit_stmt->close();

                            if ($sentCount >= 30) {
                                // Limit reached, route directly to Gmail
                                $email_type = 'gmail';
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Database Exception checking hourly limit: " . $e->getMessage());
                    }
                }

                if ($email_type === 'primary') {
                    // Attempt primary send
                    $sent = sendEmailPHPMailer($user_data['email'], $user_data['name'], $subject, $html_content, $pdf_content, $pdf_filename, 'primary');
                    if (!$sent) {
                        // Fallback to gmail
                        $sent = sendEmailPHPMailer($user_data['email'], $user_data['name'], $subject, $html_content, $pdf_content, $pdf_filename, 'gmail');
                    }
                } else {
                    // Limit reached, send via Gmail SMTP fallback directly
                    $sent = sendEmailPHPMailer($user_data['email'], $user_data['name'], $subject, $html_content, $pdf_content, $pdf_filename, 'gmail');
                }

                if ($sent) {
                    echo json_encode(['success' => true, 'message' => 'Certificate Approved and Emailed Successfully!']);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Certificate Approved, but email sending failed. Please check SMTP settings.']);
                }
            } else {
                echo json_encode(['success' => true, 'message' => 'Certificate Approved, but PDF generation failed.']);
            }
        } else {
            echo json_encode(['success' => true, 'message' => 'Certificate Approved, but intern has no valid email address to send to.']);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update certificate status"]);
    }
    $stmt->close();
}

if ($_POST['action'] === 'toggle_verified') {
    // Only Admin (1) and Manager (4) can update verification status
    if (!isset($_SESSION['user_role']) || !in_array((int)$_SESSION['user_role'], [1, 4], true)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admins and Managers can update verification status.']);
        exit;
    }

    $intern_id = (int)$_POST['id'];
    $verified = !empty($_POST['verified']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE certificate SET verified = ? WHERE intern_id = ?");
    $stmt->bind_param("ii", $verified, $intern_id);

    if ($stmt->execute()) {
        $log_user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $log_user_stmt->bind_param("i", $intern_id);
        $log_user_stmt->execute();
        $log_user_res = $log_user_stmt->get_result()->fetch_assoc();
        $internName = $log_user_res['name'] ?? 'ID ' . $intern_id;
        $log_user_stmt->close();
        logActivity('Update Certificate Verification', ($verified ? "Marked" : "Unmarked") . " certificate as QR verified for intern $internName (ID $intern_id)");

        echo json_encode([
            'success' => true,
            'message' => $verified ? 'Certificate marked as Verified.' : 'Certificate marked as Not Verified.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update verification status']);
    }
    $stmt->close();
}

// Interns whose certificate was approved more than CERT_TASK_RETENTION_DAYS ago and
// who still have tasks on record - the ones "Clean Up Old Tasks" would clear.
function getInternsWithExpiredTaskRetention($conn)
{
    $days = (int)CERT_TASK_RETENTION_DAYS;
    $stmt = $conn->prepare("
        SELECT u.id, u.name, COUNT(t.id) AS task_count
        FROM certificate c
        JOIN users u ON u.id = c.intern_id AND u.user_role = 2
        JOIN tasks t ON t.assign_to = u.id
        WHERE c.approve_status = 1
          AND c.approved_at IS NOT NULL
          AND c.approved_at <= NOW() - INTERVAL ? DAY
        GROUP BY u.id, u.name
    ");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

if ($_POST['action'] === 'cleanup_preview' || $_POST['action'] === 'cleanup_old_tasks') {
    // Only Admin (1) and Manager (4) can remove completed interns' tasks
    if (!isset($_SESSION['user_role']) || !in_array((int)$_SESSION['user_role'], [1, 4], true)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admins and Managers can clean up tasks.']);
        exit;
    }

    $interns = getInternsWithExpiredTaskRetention($conn);
    $taskCount = array_sum(array_map(fn($r) => (int)$r['task_count'], $interns));

    if ($_POST['action'] === 'cleanup_preview') {
        // Every status is deleted (complete, rejected, expired, in progress...); the
        // breakdown is only shown so the admin can see that before confirming.
        $byStatus = [];
        if (!empty($interns)) {
            $idList = implode(',', array_map(fn($r) => (int)$r['id'], $interns));
            $res = $conn->query("SELECT status, COUNT(*) AS cnt FROM tasks WHERE assign_to IN ($idList) GROUP BY status");
            foreach ($res->fetch_all(MYSQLI_ASSOC) as $r) {
                $byStatus[$r['status'] === '' ? 'unknown' : $r['status']] = (int)$r['cnt'];
            }
        }

        echo json_encode([
            'success' => true,
            'intern_count' => count($interns),
            'task_count' => $taskCount,
            'by_status' => $byStatus,
            'retention_days' => (int)CERT_TASK_RETENTION_DAYS,
        ]);
        exit;
    }

    if (empty($interns)) {
        echo json_encode(['success' => true, 'message' => 'Nothing to clean up.']);
        exit;
    }

    $ids = array_map(fn($r) => (int)$r['id'], $interns);
    $idList = implode(',', $ids); // all cast to int above

    $conn->begin_transaction();
    try {
        // Child rows keyed by task_id. Not every environment has these tables.
        foreach (['time_logs', 'approvals'] as $childTable) {
            $exists = $conn->query("SHOW TABLES LIKE '$childTable'");
            if ($exists && $exists->num_rows > 0) {
                $conn->query("DELETE c FROM `$childTable` c JOIN tasks t ON c.task_id = t.id WHERE t.assign_to IN ($idList)");
            }
        }

        // Attendance rows are kept on purpose: they are the intern's attendance history
        // and still feed the attendance % shown for completed interns.
        $conn->query("DELETE FROM tasks WHERE assign_to IN ($idList)");
        $deleted = $conn->affected_rows;

        $conn->commit();
    } catch (\Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to clean up tasks: ' . $e->getMessage()]);
        exit;
    }

    $names = implode(', ', array_map(fn($r) => $r['name'] . ' (ID ' . $r['id'] . ')', $interns));
    logActivity('Clean Up Completed Interns Tasks', "Deleted $deleted task(s) of " . count($interns) . " intern(s) whose certificate was approved over " . CERT_TASK_RETENTION_DAYS . " days ago: $names");

    echo json_encode([
        'success' => true,
        'message' => "Deleted $deleted task(s) of " . count($interns) . " intern(s)."
    ]);
    exit;
}

$conn->close();
