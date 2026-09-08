<?php
session_start();
include '../include/config.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../include/connection.php';
require_once '../include/notification_helper.php';
require_once '../include/pdf_helper.php';
require_once '../include/capture_helper.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_role'] ?? 0) !== ROLE_CANDIDATE) {
    denyJson('Unauthorized access');
}

$userId = (int)$_SESSION['user_id'];
$action = requestedAction();

// The single row this candidate is currently working on (or has completed).
function getLatestCandidateAssessment($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT ca.*, a.title, a.duration_minutes, a.passing_percentage, a.technology_id, t.name technology_name,
               u.name candidate_name, u.email candidate_email, r.mbl_number candidate_mbl
        FROM candidate_assessments ca
        JOIN assessments a ON a.id = ca.assessment_id
        LEFT JOIN technologies t ON t.id = a.technology_id
        JOIN users u ON u.id = ca.user_id
        LEFT JOIN registrations r ON r.id = ca.registration_id
        WHERE ca.user_id = ?
        ORDER BY ca.id DESC LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

// Grades everything answered so far and finalizes the attempt. $reason is
// 'normal' (candidate clicked Submit), 'timeout' (time ran out), or
// 'proctoring_violation' (2nd tab-switch/fullscreen-exit - always fails
// regardless of score).
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

    if ($reason === 'proctoring_violation') {
        $status = 'fail';
    } else {
        $status = $percentage >= (int)$ca['passing_percentage'] ? 'pass' : 'fail';
    }

    $fin = $conn->prepare("UPDATE candidate_assessments SET status = ?, score = ?, total_marks = ?, percentage = ?, completed_at = NOW(), fail_reason = ? WHERE id = ?");
    $fin->bind_param('siidsi', $status, $score, $totalMarks, $percentage, $reason, $caId);
    $fin->execute();
    $fin->close();

    sendResultEmail($ca, $status, $percentage);
    sendAdminResultEmail($conn, $ca, $status, $percentage);

    return ['status' => $status, 'percentage' => $percentage, 'fail_reason' => $reason];
}

// Emails the candidate their Pass/Fail result (with a PDF report attached) the
// moment an attempt finalizes (normal submit, timeout, or a proctoring
// violation - all three end the attempt, so all three notify).
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

switch ($action) {

    // ===============================
    // CURRENT STATE (drives the whole my_assessment.php page)
    // ===============================
    case 'get_state':
        $ca = getLatestCandidateAssessment($conn, $userId);
        if (!$ca) {
            echo json_encode(['success' => true, 'state' => 'none']);
            exit;
        }

        // Lazy time-out check: if the timer ran out since the last request, finalize now.
        if ($ca['status'] === 'in_progress' && $ca['expires_at'] !== null && strtotime($ca['expires_at']) <= time()) {
            gradeAndFinish($conn, $ca, 'timeout');
            $ca = getLatestCandidateAssessment($conn, $userId);
        }

        if ($ca['status'] === 'pending') {
            $countStmt = $conn->prepare("SELECT COUNT(*) c FROM assessment_questions WHERE assessment_id = ?");
            $countStmt->bind_param('i', $ca['assessment_id']);
            $countStmt->execute();
            $questionCount = (int)$countStmt->get_result()->fetch_assoc()['c'];
            $countStmt->close();

            echo json_encode([
                'success' => true,
                'state' => 'pending',
                'assessment' => [
                    'title' => $ca['title'],
                    'technology' => $ca['technology_name'],
                    'duration_minutes' => (int)$ca['duration_minutes'],
                    'passing_percentage' => (int)$ca['passing_percentage'],
                    'question_count' => $questionCount
                ]
            ]);
            exit;
        }

        if ($ca['status'] === 'in_progress') {
            echo json_encode(['success' => true] + buildInProgressPayload($conn, $ca));
            exit;
        }

        // pass / fail
        echo json_encode([
            'success' => true,
            'state' => $ca['status'],
            'percentage' => $ca['percentage'] !== null ? (float)$ca['percentage'] : null,
            'fail_reason' => $ca['fail_reason'],
            'completed_at' => $ca['completed_at']
        ]);
        break;

    // ===============================
    // START THE ATTEMPT
    // ===============================
    case 'start':
        $ca = getLatestCandidateAssessment($conn, $userId);
        if (!$ca) {
            echo json_encode(['success' => false, 'message' => 'No assessment assigned to your account.']);
            exit;
        }
        if ($ca['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'This assessment has already been started or completed.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE candidate_assessments SET status = 'in_progress', started_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
        $stmt->bind_param('ii', $ca['duration_minutes'], $ca['id']);
        $stmt->execute();
        $stmt->close();

        $ca = getLatestCandidateAssessment($conn, $userId);
        echo json_encode(['success' => true] + buildInProgressPayload($conn, $ca));
        break;

    // ===============================
    // AUTOSAVE ONE ANSWER
    // ===============================
    case 'save_answer':
        $ca = getLatestCandidateAssessment($conn, $userId);
        if (!$ca || $ca['status'] !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'No active attempt.']);
            exit;
        }
        if (strtotime($ca['expires_at']) <= time()) {
            gradeAndFinish($conn, $ca, 'timeout');
            echo json_encode(['success' => false, 'message' => 'Time is up.', 'expired' => true]);
            exit;
        }

        $questionId = (int)($_POST['question_id'] ?? 0);
        $selectedOptionId = (int)($_POST['selected_option_id'] ?? 0);

        $chk = $conn->prepare("SELECT id FROM assessment_questions WHERE id = ? AND assessment_id = ?");
        $chk->bind_param('ii', $questionId, $ca['assessment_id']);
        $chk->execute();
        $valid = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$valid) {
            echo json_encode(['success' => false, 'message' => 'Invalid question.']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO candidate_assessment_answers (candidate_assessment_id, question_id, selected_option_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id)
        ");
        $stmt->bind_param('iii', $ca['id'], $questionId, $selectedOptionId);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $ok]);
        break;

    // ===============================
    // FINISH THE ATTEMPT (candidate-initiated)
    // ===============================
    case 'submit':
        $ca = getLatestCandidateAssessment($conn, $userId);
        if (!$ca || $ca['status'] !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'No active attempt to submit.']);
            exit;
        }
        $reason = (strtotime($ca['expires_at']) <= time()) ? 'timeout' : 'normal';
        $result = gradeAndFinish($conn, $ca, $reason);
        echo json_encode(['success' => true] + $result);
        break;

    // ===============================
    // PROCTORING VIOLATION REPORT (tab switch / fullscreen exit)
    // ===============================
    case 'report_violation':
        $ca = getLatestCandidateAssessment($conn, $userId);
        if (!$ca || $ca['status'] !== 'in_progress') {
            echo json_encode(['success' => true, 'failed' => false, 'violation_count' => 0]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE candidate_assessments SET violation_count = violation_count + 1 WHERE id = ?");
        $stmt->bind_param('i', $ca['id']);
        $stmt->execute();
        $stmt->close();

        $ca = getLatestCandidateAssessment($conn, $userId);
        $violationCount = (int)$ca['violation_count'];

        if ($violationCount >= 2) {
            $result = gradeAndFinish($conn, $ca, 'proctoring_violation');
            echo json_encode(['success' => true, 'failed' => true, 'violation_count' => $violationCount] + $result);
            exit;
        }

        echo json_encode(['success' => true, 'failed' => false, 'violation_count' => $violationCount]);
        break;

    // ===============================
    // WEBCAM PROCTORING SNAPSHOT (periodic capture while in_progress)
    // ===============================
    case 'capture_snapshot':
        $ca = getLatestCandidateAssessment($conn, $userId);
        if (!$ca || $ca['status'] !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'No active attempt.']);
            exit;
        }

        $image = $_POST['image'] ?? '';
        if (empty($image)) {
            echo json_encode(['success' => false, 'message' => 'No image provided.']);
            exit;
        }

        $ok = saveAssessmentCapture($conn, $ca['id'], $ca['registration_id'], $image);
        echo json_encode(['success' => $ok]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Questions (without correct-answer flags) + this candidate's saved answers + remaining time.
function buildInProgressPayload($conn, $ca)
{
    $qStmt = $conn->prepare("SELECT id, question_html, points, order_index FROM assessment_questions WHERE assessment_id = ? ORDER BY order_index ASC, id ASC");
    $qStmt->bind_param('i', $ca['assessment_id']);
    $qStmt->execute();
    $questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $qStmt->close();

    foreach ($questions as &$q) {
        $oStmt = $conn->prepare("SELECT id, option_text, order_index FROM assessment_options WHERE question_id = ? ORDER BY order_index ASC, id ASC");
        $oStmt->bind_param('i', $q['id']);
        $oStmt->execute();
        $q['options'] = $oStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $oStmt->close();
    }
    unset($q);

    $ansStmt = $conn->prepare("SELECT question_id, selected_option_id FROM candidate_assessment_answers WHERE candidate_assessment_id = ?");
    $ansStmt->bind_param('i', $ca['id']);
    $ansStmt->execute();
    $ansResult = $ansStmt->get_result();
    $answers = [];
    while ($row = $ansResult->fetch_assoc()) {
        $answers[$row['question_id']] = $row['selected_option_id'];
    }
    $ansStmt->close();

    return [
        'state' => 'in_progress',
        'assessment' => [
            'title' => $ca['title'],
            'technology' => $ca['technology_name'],
            'duration_minutes' => (int)$ca['duration_minutes'],
            'passing_percentage' => (int)$ca['passing_percentage']
        ],
        'questions' => $questions,
        'answers' => $answers,
        'remaining_seconds' => max(0, strtotime($ca['expires_at']) - time()),
        'violation_count' => (int)$ca['violation_count']
    ];
}

if (isset($conn)) {
    mysqli_close($conn);
}
