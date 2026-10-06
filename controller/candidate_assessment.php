<?php
session_start();
include '../include/config.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../include/connection.php';
require_once '../include/notification_helper.php';
require_once '../include/pdf_helper.php';
require_once '../include/capture_helper.php';
require_once '../include/assessment_grading_helper.php';
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

        // The exam page only reaches in_progress via 'start' within the same page
        // load, so finding it in_progress here means the candidate left mid-attempt
        // (refresh, closed tab/browser, crash) - that's an automatic fail.
        if ($ca['status'] === 'in_progress') {
            gradeAndFinish($conn, $ca, 'abandoned');
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
    // PAGE UNLOAD BEACON (tab/browser closed, refresh, navigated away)
    // ===============================
    case 'abandon':
        // Sent via navigator.sendBeacon - the browser is gone, so finish grading
        // and emailing even though nobody reads the response.
        ignore_user_abort(true);
        $ca = getLatestCandidateAssessment($conn, $userId);
        if ($ca && $ca['status'] === 'in_progress') {
            gradeAndFinish($conn, $ca, 'abandoned');
        }
        echo json_encode(['success' => true]);
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
