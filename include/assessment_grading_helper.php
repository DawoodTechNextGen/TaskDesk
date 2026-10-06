<?php
// Grading + result notifications for candidate assessments. Shared by the
// candidate's own controller (submit / timeout / violation / abandon) and the
// admin Assessment Pipeline (which finalizes attempts abandoned without ever
// returning). Callers must already have included notification_helper.php,
// pdf_helper.php and capture_helper.php.

// Loads one candidate_assessments row with everything grading/emails need.
function getCandidateAssessmentForGrading($conn, $caId)
{
    $stmt = $conn->prepare("
        SELECT ca.*, a.title, a.duration_minutes, a.passing_percentage, a.technology_id, t.name technology_name,
               COALESCE(u.name, r.name) candidate_name, COALESCE(u.email, r.email) candidate_email, r.mbl_number candidate_mbl
        FROM candidate_assessments ca
        JOIN assessments a ON a.id = ca.assessment_id
        LEFT JOIN technologies t ON t.id = a.technology_id
        LEFT JOIN users u ON u.id = ca.user_id
        LEFT JOIN registrations r ON r.id = ca.registration_id
        WHERE ca.id = ?
    ");
    $stmt->bind_param('i', $caId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

// Grades everything answered so far and finalizes the attempt. $reason is
// 'normal' (candidate clicked Submit), 'timeout' (time ran out),
// 'proctoring_violation' (2nd tab-switch/fullscreen-exit) or 'abandoned'
// (page closed/refreshed/browser shut mid-attempt). The last two always fail
// regardless of score. Only the request that actually flips the row out of
// in_progress sends the result emails, so racing finalizers (e.g. the unload
// beacon and a violation report) can't double-notify.
function gradeAndFinish($conn, $ca, $reason)
{
    $caId = (int)$ca['id'];

    $upd = $conn->prepare("
        UPDATE candidate_assessment_answers caa
        JOIN assessment_options ao ON ao.id = caa.selected_option_id
        SET caa.is_correct = ao.is_correct
        WHERE caa.candidate_assessment_id = ?
    ");
    $upd->bind_param('i', $caId);
    $upd->execute();
    $upd->close();

    $scoreStmt = $conn->prepare("
        SELECT COALESCE(SUM(q.points), 0) total_marks,
               COALESCE(SUM(CASE WHEN caa.is_correct = 1 THEN q.points ELSE 0 END), 0) score
        FROM assessment_questions q
        LEFT JOIN candidate_assessment_answers caa ON caa.question_id = q.id AND caa.candidate_assessment_id = ?
        WHERE q.assessment_id = ?
    ");
    $scoreStmt->bind_param('ii', $caId, $ca['assessment_id']);
    $scoreStmt->execute();
    $scoreRow = $scoreStmt->get_result()->fetch_assoc();
    $scoreStmt->close();

    $totalMarks = (int)$scoreRow['total_marks'];
    $score = (int)$scoreRow['score'];
    $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;

    if (in_array($reason, ['proctoring_violation', 'abandoned'], true)) {
        $status = 'fail';
    } else {
        $status = $percentage >= (int)$ca['passing_percentage'] ? 'pass' : 'fail';
    }

    $fin = $conn->prepare("UPDATE candidate_assessments SET status = ?, score = ?, total_marks = ?, percentage = ?, completed_at = NOW(), fail_reason = ? WHERE id = ? AND status = 'in_progress'");
    $fin->bind_param('siidsi', $status, $score, $totalMarks, $percentage, $reason, $caId);
    $fin->execute();
    $finalized = $fin->affected_rows > 0;
    $fin->close();

    if (!$finalized) {
        // Someone else already finalized it - report what was recorded.
        $current = getCandidateAssessmentForGrading($conn, $caId);
        return [
            'status' => $current['status'],
            'percentage' => $current['percentage'] !== null ? (float)$current['percentage'] : null,
            'fail_reason' => $current['fail_reason']
        ];
    }

    sendResultEmail($ca, $status, $percentage);
    sendAdminResultEmail($conn, $ca, $status, $percentage);

    return ['status' => $status, 'percentage' => $percentage, 'fail_reason' => $reason];
}

// Fails every attempt still sitting in in_progress well after its timer ran
// out - the candidate left (closed the browser, lost power, etc.) and the
// unload beacon never reached us, and they haven't come back since. Skips
// candidates no longer in the Assessment stage (e.g. already rejected) so
// they don't get a result email after their rejection.
function finalizeAbandonedAssessments($conn)
{
    $res = $conn->query("SELECT ca.id FROM candidate_assessments ca
        JOIN registrations r ON r.id = ca.registration_id
        WHERE ca.status = 'in_progress' AND r.status = 'assessment'
        AND ca.expires_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
    if (!$res) {
        return;
    }
    while ($row = $res->fetch_assoc()) {
        $ca = getCandidateAssessmentForGrading($conn, (int)$row['id']);
        if ($ca && $ca['status'] === 'in_progress') {
            gradeAndFinish($conn, $ca, 'abandoned');
        }
    }
}

// Emails the candidate their Pass/Fail result (with a PDF report attached) the
// moment an attempt finalizes (normal submit, timeout, a proctoring violation
// or abandonment - all of them end the attempt, so all of them notify).
function sendResultEmail($ca, $status, $percentage)
{
    $name = $ca['candidate_name'];
    $email = $ca['candidate_email'];
    if (empty($email)) {
        return;
    }

    $pass = $status === 'pass';
    $statusLabel = $pass ? 'Passed' : 'Not Cleared';
    $statusColor = $pass ? '#16a34a' : '#dc2626';
    $completedAt = date('Y-m-d H:i:s');
    $technology = $ca['technology_name'] ?? '';

    $whatsappMsg = "Assalam-o-Alaikum *{$name}*,\n\n"
        . "📝 *Assessment Result - Dawood Tech NextGen*\n\n"
        . "🎯 *Assessment:* " . $ca['title'] . "\n"
        . "📊 *Result:* {$statusLabel}\n\n"
        . "Your detailed result report is attached as a PDF.\n\n"
        . "Thank you for completing the assessment. Our HR department will contact you soon regarding the next steps.\n\n"
        . "Best regards,\nHR Department\n*DawoodTech NextGen*";

    $htmlContent = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
        <h2 style='color: #2563eb; text-align: center;'>Assessment Result</h2>
        <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
        <p>Thank you for completing the <strong>" . htmlspecialchars($ca['title']) . "</strong> assessment.</p>
        <div style='background: #f3f4f6; padding: 18px; border-radius: 8px; margin: 20px 0; text-align: center;'>
            <p style='font-size: 22px; font-weight: bold; color: {$statusColor}; margin: 0;'>{$statusLabel}</p>
        </div>
        <p>Your detailed result report is attached to this email as a PDF.</p>
        <p>Our HR department will contact you soon regarding the next steps.</p>
        <p>Best regards,<br><strong>HR Department</strong><br>DawoodTech NextGen</p>
    </div>";

    $pdfContent = generateAssessmentResultHelper($name, $ca['title'], $technology, $status, $percentage, $completedAt, $ca['started_at'] ?? null);

    sendNotificationFallback([
        'email' => $email,
        'name' => $name,
        'mbl_number' => $ca['candidate_mbl'] ?? '',
        'subject' => 'Your Assessment Result - DawoodTech NextGen',
        'html_content' => $htmlContent,
        'whatsapp_msg' => $whatsappMsg,
        'pdf_content' => $pdfContent,
        'pdf_filename' => 'Assessment_Result_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $name) . '.pdf'
    ]);
}

// Emails a detailed, question-by-question PDF report (each question, the
// candidate's selected answer, and whether it was correct) the moment an
// attempt finalizes - to every Admin/Manager plus any User-Management
// Collaborator with access to the Registrations module - so they can review
// exact answers without opening the assessment builder.
function sendAdminResultEmail($conn, $ca, $status, $percentage)
{
    $caId = (int)$ca['id'];
    $name = $ca['candidate_name'];
    $technology = $ca['technology_name'] ?? '';
    $completedAt = date('Y-m-d H:i:s');

    $qStmt = $conn->prepare("
        SELECT q.id question_id, q.question_html, q.points,
               caa.selected_option_id, caa.is_correct
        FROM assessment_questions q
        LEFT JOIN candidate_assessment_answers caa
            ON caa.question_id = q.id AND caa.candidate_assessment_id = ?
        WHERE q.assessment_id = ?
        ORDER BY q.order_index ASC, q.id ASC
    ");
    $qStmt->bind_param('ii', $caId, $ca['assessment_id']);
    $qStmt->execute();
    $questionRows = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $qStmt->close();

    if (!$questionRows) {
        return;
    }

    $optStmt = $conn->prepare("SELECT id, option_text, is_correct FROM assessment_options WHERE question_id = ? ORDER BY order_index ASC, id ASC");
    $questions = [];
    foreach ($questionRows as $q) {
        $optStmt->bind_param('i', $q['question_id']);
        $optStmt->execute();
        $q['options'] = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $questions[] = $q;
    }
    $optStmt->close();

    $captures = getAssessmentCapturesForReport($conn, $caId);

    $pdfContent = generateDetailedAssessmentReportHelper($name, $ca['title'], $technology, $status, $percentage, $completedAt, $questions, $ca['started_at'] ?? null, $captures);
    if (!$pdfContent) {
        return;
    }

    // Same audience as the Assessment Pipeline page itself: Admin, Manager, and
    // any User-Management Collaborator who was granted read/write access to the
    // Registrations module (not every Collaborator - only ones with that access).
    $recipientStmt = $conn->prepare("
        SELECT DISTINCT u.name, u.email
        FROM users u
        LEFT JOIN module_permissions mp ON mp.user_id = u.id AND mp.module = ?
        WHERE u.status = 1
        AND u.email IS NOT NULL AND u.email <> ''
        AND (
            u.user_role IN (?, ?)
            OR (u.user_role = ? AND mp.access IN ('read', 'write'))
        )
    ");
    $module = MODULE_REGISTRATIONS;
    $adminRole = ROLE_ADMIN;
    $managerRole = ROLE_MANAGER;
    $collaboratorRole = ROLE_COLLABORATOR;
    $recipientStmt->bind_param('siii', $module, $adminRole, $managerRole, $collaboratorRole);
    $recipientStmt->execute();
    $recipients = $recipientStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recipientStmt->close();

    if (!$recipients) {
        return;
    }

    $pass = $status === 'pass';
    $statusLabel = $pass ? 'Passed' : 'Not Cleared';
    $statusColor = $pass ? '#16a34a' : '#dc2626';

    $htmlContent = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
        <h2 style='color: #2563eb; text-align: center;'>Candidate Assessment Completed</h2>
        <p><strong>" . htmlspecialchars($name) . "</strong> has completed the <strong>" . htmlspecialchars($ca['title']) . "</strong> assessment.</p>
        <div style='background: #f3f4f6; padding: 18px; border-radius: 8px; margin: 20px 0; text-align: center;'>
            <p style='font-size: 22px; font-weight: bold; color: {$statusColor}; margin: 0;'>{$statusLabel}</p>
            <p style='font-size: 13px; color: #64748b; margin: 4px 0 0;'>Score: " . htmlspecialchars((string)$percentage) . "%</p>
        </div>
        <p>The full question-by-question breakdown (correct/incorrect answers for each question) is attached as a PDF.</p>
    </div>";

    $pdfFilename = 'Assessment_Detailed_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $name) . '.pdf';

    foreach ($recipients as $recipient) {
        if (empty($recipient['email'])) {
            continue;
        }
        sendNotificationFallback([
            'email' => $recipient['email'],
            'name' => $recipient['name'],
            'subject' => 'Candidate Assessment Completed - ' . $name . ' (' . $statusLabel . ')',
            'html_content' => $htmlContent,
            'pdf_content' => $pdfContent,
            'pdf_filename' => $pdfFilename
        ]);
    }
}
