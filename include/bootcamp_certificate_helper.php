<?php
/**
 * Bootcamp completion certificates. An enrollment marked `completed` on
 * bootcamp_registrations.php gets a PDF certificate (include/pdf_helper.php,
 * template assets/images/bootcamp_certificate.png) emailed to it, with a QR /
 * link to verify_bootcamp_certificate.php?code=<certificate_code>.
 *
 * Needs include/bootcamp_helper.php and include/notification_helper.php
 * (sendEmailPHPMailer) to be loaded first.
 */
require_once __DIR__ . '/pdf_helper.php';

if (!function_exists('generateBootcampCertificateCode')) {
    function generateBootcampCertificateCode($conn)
    {
        // DTN-BC- + 8 hex chars; retried on the (very unlikely) clash.
        for ($i = 0; $i < 5; $i++) {
            $code = 'DTN-BC-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $conn->prepare("SELECT id FROM " . BOOTCAMP_TABLE . " WHERE certificate_code = ?");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $taken = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$taken) {
                return $code;
            }
        }
        return 'DTN-BC-' . strtoupper(bin2hex(random_bytes(6)));
    }
}

if (!function_exists('bootcampCertificateVerifyUrl')) {
    function bootcampCertificateVerifyUrl($code)
    {
        return rtrim(BASE_URL, '/') . '/verify_bootcamp_certificate.php?code=' . urlencode($code);
    }
}

/**
 * Builds and emails the certificate for one completed enrollment, and records
 * the outcome in certificate_status / certificate_sent_at.
 *
 * @return array{sent:bool, message:string}
 */
if (!function_exists('issueBootcampCertificate')) {
    function issueBootcampCertificate($conn, $registrationId)
    {
        $stmt = $conn->prepare("SELECT b.id, b.name, b.email, b.status, b.completed_at, b.certificate_code,
                bc.title AS bootcamp_title, bc.start_date, bc.end_date
            FROM " . BOOTCAMP_TABLE . " b
            LEFT JOIN " . BOOTCAMPS_TABLE . " bc ON bc.id = b.bootcamp_id
            WHERE b.id = ?");
        $stmt->bind_param('i', $registrationId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return ['sent' => false, 'message' => 'Enrollment not found'];
        }
        if ($row['status'] !== 'completed') {
            return ['sent' => false, 'message' => 'Certificates are only sent for completed enrollments'];
        }
        if (empty($row['email'])) {
            return ['sent' => false, 'message' => 'Enrollee email not found'];
        }

        $code = $row['certificate_code'];
        if (empty($code)) {
            $code = generateBootcampCertificateCode($conn);
            $upd = $conn->prepare("UPDATE " . BOOTCAMP_TABLE . " SET certificate_code = ? WHERE id = ?");
            $upd->bind_param('si', $code, $registrationId);
            $upd->execute();
            $upd->close();
        }

        $bootcampTitle = $row['bootcamp_title'] ?: 'DawoodTech NextGen Bootcamp';
        $startDate = $row['start_date'] ? date('d F Y', strtotime($row['start_date'])) : null;
        $endDate = $row['end_date'] ? date('d F Y', strtotime($row['end_date'])) : null;
        $issueDate = date('d F Y', strtotime($row['completed_at'] ?: 'now'));
        $verifyUrl = bootcampCertificateVerifyUrl($code);

        $pdf = generateBootcampCertificateHelper($row['name'], $bootcampTitle, $startDate, $endDate, $issueDate, $verifyUrl);
        if (!$pdf) {
            bootcampCertificateSetStatus($conn, $registrationId, 2);
            return ['sent' => false, 'message' => 'Certificate PDF could not be generated'];
        }

        $current_year = date('Y');
        $e_name = htmlspecialchars($row['name']);
        $e_bootcamp = htmlspecialchars($bootcampTitle);
        $e_code = htmlspecialchars($code);
        $e_verify = htmlspecialchars($verifyUrl);
        $periodRow = ($startDate && $endDate)
            ? "<tr><td style=\"padding: 6px 0; font-weight: 600;\">Duration:</td><td style=\"padding: 6px 0;\">" . htmlspecialchars($startDate) . " – " . htmlspecialchars($endDate) . "</td></tr>"
            : '';

        $html = "
        <div style=\"background-color: #F8FAFC; padding: 40px 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100%;\">
            <div style=\"max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0;\">
                <div style=\"background-color: #FFFFFF; padding: 28px 32px; text-align: center; border-bottom: 4px solid #2563EB;\">
                    <img src=\"cid:logo_cid\" alt=\"DawoodTech NextGen\" style=\"max-height: 52px; width: auto; max-width: 100%; height: auto; display: inline-block;\">
                </div>
                <div style=\"padding: 40px 32px; color: #0F172A; line-height: 1.6; font-size: 16px;\">
                    <div style=\"margin-bottom: 24px;\">
                        <span style=\"background-color: #E0E7FF; color: #2563EB; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block;\">Bootcamp Completed</span>
                    </div>
                    <p style=\"margin-top: 0; font-weight: 700; font-size: 22px; color: #1E293B; letter-spacing: -0.3px;\">Dear {$e_name},</p>
                    <p style=\"color: #334155;\">Congratulations on successfully completing the <strong>{$e_bootcamp}</strong>! Your certificate of completion is attached to this email.</p>

                    <div style=\"background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px; margin: 24px 0;\">
                        <table style=\"width: 100%; font-size: 14px; color: #475569; border-collapse: collapse;\">
                            <tr><td style=\"padding: 6px 0; font-weight: 600; width: 130px;\">Bootcamp:</td><td style=\"padding: 6px 0;\">{$e_bootcamp}</td></tr>
                            {$periodRow}
                            <tr><td style=\"padding: 6px 0; font-weight: 600;\">Certificate ID:</td><td style=\"padding: 6px 0;\">{$e_code}</td></tr>
                        </table>
                    </div>

                    <p style=\"color: #334155;\">Add it to your LinkedIn profile and CV - anyone can confirm it is genuine with the verification link below.</p>

                    <div style=\"text-align: center; margin: 32px 0 16px 0;\">
                        <a href=\"{$e_verify}\" target=\"_blank\" style=\"background-color: #2563EB; color: #FFFFFF; padding: 12px 30px; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-block; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);\">Verify Your Certificate</a>
                    </div>
                </div>
                <div style=\"background-color: #1E293B; padding: 28px 24px; text-align: center; font-size: 12px; color: #94A3B8; border-top: 1px solid #E2E8F0;\">
                    <p style=\"margin: 0 0 8px 0; font-weight: 600; color: #FFFFFF; font-size: 13px;\">DawoodTech NextGen</p>
                    <p style=\"margin: 0; font-size: 11px;\">&copy; {$current_year} DawoodTech. All rights reserved.</p>
                </div>
            </div>
        </div>";

        $subject = 'Your Bootcamp Certificate - ' . $bootcampTitle . ' - DawoodTech NextGen';
        $filename = 'Bootcamp_Certificate_' . preg_replace('/[^a-zA-Z0-9]/', '_', $row['name']) . '.pdf';

        $sent = sendEmailPHPMailer($row['email'], $row['name'], $subject, $html, $pdf, $filename, 'primary');
        if (!$sent) {
            $sent = sendEmailPHPMailer($row['email'], $row['name'], $subject, $html, $pdf, $filename, 'gmail');
        }

        bootcampCertificateSetStatus($conn, $registrationId, $sent ? 1 : 2);

        return $sent
            ? ['sent' => true, 'message' => 'Certificate emailed to ' . $row['email']]
            : ['sent' => false, 'message' => 'Certificate email failed. Please check SMTP settings and use Resend.'];
    }
}

if (!function_exists('bootcampCertificateSetStatus')) {
    function bootcampCertificateSetStatus($conn, $registrationId, $status)
    {
        $stmt = $conn->prepare("UPDATE " . BOOTCAMP_TABLE . " SET certificate_status = ?, certificate_sent_at = IF(? = 1, NOW(), certificate_sent_at) WHERE id = ?");
        $stmt->bind_param('iii', $status, $status, $registrationId);
        $stmt->execute();
        $stmt->close();
    }
}
