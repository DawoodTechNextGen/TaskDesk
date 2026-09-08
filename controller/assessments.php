<?php
session_start();
include '../include/config.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../include/connection.php';
header('Content-Type: application/json');

// Building/editing the question bank (save/delete assessment or question) is
// Admin-only per spec. Reading the list (technology selector, "Send Assessment"
// dropdown from the registrations pipeline) is open to anyone who can already
// see the registrations module - Admin, Manager, and read-only Collaborators.
$currentRole = (int)($_SESSION['user_role'] ?? 0);
if (!isset($_SESSION['user_id']) || !in_array($currentRole, [ROLE_ADMIN, ROLE_MANAGER, ROLE_COLLABORATOR], true)) {
    denyJson('Unauthorized access');
}
// A Collaborator only gets to read this (e.g. the "Send Assessment" dropdown)
// if they have at least read access to the Registrations module.
if ($currentRole === ROLE_COLLABORATOR && !canViewModule(MODULE_REGISTRATIONS)) {
    denyJson('You do not have access to this module.');
}

$action = requestedAction();

$writeActions = ['save_assessment', 'delete_assessment', 'save_question', 'delete_question', 'import_questions'];
if (in_array($action, $writeActions, true)) {
    requireAdminAction();
}

switch ($action) {

    // ===============================
    // ASSESSMENTS FOR ONE TECHNOLOGY (builder list)
    // ===============================
    case 'list_assessments':
        $techId = (int)($_GET['technology_id'] ?? 0);
        if ($techId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Technology is required']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT a.*, (SELECT COUNT(*) FROM assessment_questions q WHERE q.assessment_id = a.id) question_count,
                   (SELECT COUNT(*) FROM candidate_assessments ca WHERE ca.assessment_id = a.id) attempt_count
            FROM assessments a
            WHERE a.technology_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->bind_param('i', $techId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ===============================
    // ACTIVE ASSESSMENTS FOR A TECHNOLOGY (send-assessment dropdown)
    // ===============================
    case 'active_by_technology':
        $techId = (int)($_GET['technology_id'] ?? 0);
        if ($techId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Technology is required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id, title, difficulty, duration_minutes, passing_percentage,
                (SELECT COUNT(*) FROM assessment_questions q WHERE q.assessment_id = a.id) question_count
            FROM assessments a WHERE technology_id = ? AND status = 1 ORDER BY title ASC");
        $stmt->bind_param('i', $techId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ===============================
    // CREATE / UPDATE AN ASSESSMENT
    // ===============================
    case 'save_assessment':
        $id = (int)($_POST['id'] ?? 0);
        $techId = (int)($_POST['technology_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $durationMinutes = (int)($_POST['duration_minutes'] ?? 30);
        $passingPercentage = (int)($_POST['passing_percentage'] ?? 60);

        if ($techId <= 0 || $title === '') {
            echo json_encode(['success' => false, 'message' => 'Technology and title are required']);
            exit;
        }
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }
        $durationMinutes = max(1, $durationMinutes);
        $passingPercentage = min(100, max(1, $passingPercentage));

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE assessments SET technology_id = ?, title = ?, difficulty = ?, duration_minutes = ?, passing_percentage = ? WHERE id = ?");
            $stmt->bind_param('issiii', $techId, $title, $difficulty, $durationMinutes, $passingPercentage, $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Assessment updated' : 'Failed to update assessment', 'id' => $id]);
        } else {
            $createdBy = (int)$_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO assessments (technology_id, title, difficulty, duration_minutes, passing_percentage, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issiii', $techId, $title, $difficulty, $durationMinutes, $passingPercentage, $createdBy);
            $ok = $stmt->execute();
            $newId = $conn->insert_id;
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Assessment created' : 'Failed to create assessment', 'id' => $newId]);
        }
        break;

    // ===============================
    // DELETE (OR DEACTIVATE IF IT HAS ATTEMPTS) AN ASSESSMENT
    // ===============================
    case 'delete_assessment':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid assessment']);
            exit;
        }
        $chk = $conn->prepare("SELECT COUNT(*) c FROM candidate_assessments WHERE assessment_id = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $hasAttempts = (int)$chk->get_result()->fetch_assoc()['c'] > 0;
        $chk->close();

        if ($hasAttempts) {
            $stmt = $conn->prepare("UPDATE assessments SET status = 0 WHERE id = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'This assessment already has candidate attempts, so it was deactivated instead of deleted.' : 'Failed to deactivate assessment']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM assessments WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Assessment deleted' : 'Failed to delete assessment']);
        break;

    // ===============================
    // QUESTIONS (+ OPTIONS) FOR ONE ASSESSMENT
    // ===============================
    case 'get_questions':
        $assessmentId = (int)($_GET['assessment_id'] ?? 0);
        if ($assessmentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Assessment is required']);
            exit;
        }
        $qStmt = $conn->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY order_index ASC, id ASC");
        $qStmt->bind_param('i', $assessmentId);
        $qStmt->execute();
        $questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $qStmt->close();

        foreach ($questions as &$q) {
            $oStmt = $conn->prepare("SELECT * FROM assessment_options WHERE question_id = ? ORDER BY order_index ASC, id ASC");
            $oStmt->bind_param('i', $q['id']);
            $oStmt->execute();
            $q['options'] = $oStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $oStmt->close();
        }
        unset($q);

        echo json_encode(['success' => true, 'data' => $questions]);
        break;

    // ===============================
    // CREATE / UPDATE A QUESTION (+ replace its options)
    // ===============================
    case 'save_question':
        $id = (int)($_POST['id'] ?? 0);
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $questionHtml = trim($_POST['question_html'] ?? '');
        $points = max(1, (int)($_POST['points'] ?? 1));
        $optionsJson = $_POST['options_json'] ?? '[]';
        $options = json_decode($optionsJson, true);

        if ($assessmentId <= 0 || $questionHtml === '' || !is_array($options) || count($options) < 2) {
            echo json_encode(['success' => false, 'message' => 'A question needs assessment, text, and at least 2 options']);
            exit;
        }
        $hasCorrect = false;
        foreach ($options as $opt) {
            if (!empty($opt['is_correct'])) {
                $hasCorrect = true;
                break;
            }
        }
        if (!$hasCorrect) {
            echo json_encode(['success' => false, 'message' => 'Mark exactly one option as the correct answer']);
            exit;
        }

        $conn->begin_transaction();
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE assessment_questions SET question_html = ?, points = ? WHERE id = ?");
                $stmt->bind_param('sii', $questionHtml, $points, $id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update question');
                }
                $stmt->close();
                $questionId = $id;

                $del = $conn->prepare("DELETE FROM assessment_options WHERE question_id = ?");
                $del->bind_param('i', $questionId);
                $del->execute();
                $del->close();
            } else {
                $orderStmt = $conn->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 next_order FROM assessment_questions WHERE assessment_id = ?");
                $orderStmt->bind_param('i', $assessmentId);
                $orderStmt->execute();
                $orderIndex = (int)$orderStmt->get_result()->fetch_assoc()['next_order'];
                $orderStmt->close();

                $stmt = $conn->prepare("INSERT INTO assessment_questions (assessment_id, question_html, points, order_index) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isii', $assessmentId, $questionHtml, $points, $orderIndex);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create question');
                }
                $questionId = $conn->insert_id;
                $stmt->close();
            }

            $optStmt = $conn->prepare("INSERT INTO assessment_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)");
            $orderIdx = 0;
            foreach ($options as $opt) {
                $text = trim($opt['text'] ?? '');
                if ($text === '') continue;
                $isCorrect = !empty($opt['is_correct']) ? 1 : 0;
                $optStmt->bind_param('isii', $questionId, $text, $isCorrect, $orderIdx);
                if (!$optStmt->execute()) {
                    throw new Exception('Failed to save an option');
                }
                $orderIdx++;
            }
            $optStmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Question saved', 'id' => $questionId]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===============================
    // DELETE A QUESTION
    // ===============================
    case 'delete_question':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid question']);
            exit;
        }
        $chk = $conn->prepare("SELECT COUNT(*) c FROM candidate_assessment_answers WHERE question_id = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $hasAnswers = (int)$chk->get_result()->fetch_assoc()['c'] > 0;
        $chk->close();

        if ($hasAnswers) {
            echo json_encode(['success' => false, 'message' => 'This question has already been answered by a candidate and cannot be deleted. Deactivate the whole assessment instead if it needs retiring.']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM assessment_questions WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Question deleted' : 'Failed to delete question']);
        break;

    // ===============================
    // BULK-IMPORT QUESTIONS FROM A JSON FILE (appends to the assessment,
    // does not touch existing questions)
    // ===============================
    case 'import_questions':
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);

        if ($assessmentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Assessment is required']);
            exit;
        }
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please upload a valid JSON file.']);
            exit;
        }

        $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
        $items = json_decode($jsonContent, true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON: expected a non-empty array of questions.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $orderStmt = $conn->prepare("SELECT COALESCE(MAX(order_index), 0) next_order FROM assessment_questions WHERE assessment_id = ?");
            $orderStmt->bind_param('i', $assessmentId);
            $orderStmt->execute();
            $orderIndex = (int)$orderStmt->get_result()->fetch_assoc()['next_order'];
            $orderStmt->close();

            $qStmt = $conn->prepare("INSERT INTO assessment_questions (assessment_id, question_html, points, order_index) VALUES (?, ?, ?, ?)");
            $optStmt = $conn->prepare("INSERT INTO assessment_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)");

            $imported = 0;
            foreach ($items as $i => $item) {
                $questionHtml = trim($item['question_html'] ?? $item['question'] ?? '');
                $points = max(1, (int)($item['points'] ?? 1));
                $options = $item['options'] ?? [];

                if ($questionHtml === '') {
                    throw new Exception("Question #" . ($i + 1) . ": 'question' text is required.");
                }
                if (!is_array($options) || count($options) < 2) {
                    throw new Exception("Question #" . ($i + 1) . ": needs at least 2 options.");
                }

                $hasCorrect = false;
                foreach ($options as $opt) {
                    if (!empty($opt['is_correct'])) {
                        $hasCorrect = true;
                        break;
                    }
                }
                if (!$hasCorrect) {
                    throw new Exception("Question #" . ($i + 1) . ": exactly one option must have \"is_correct\": true.");
                }

                $orderIndex++;
                $qStmt->bind_param('isii', $assessmentId, $questionHtml, $points, $orderIndex);
                if (!$qStmt->execute()) {
                    throw new Exception("Question #" . ($i + 1) . ": failed to save.");
                }
                $questionId = $conn->insert_id;

                $optOrder = 0;
                foreach ($options as $opt) {
                    $optText = trim($opt['text'] ?? '');
                    if ($optText === '') continue;
                    $isCorrect = !empty($opt['is_correct']) ? 1 : 0;
                    $optStmt->bind_param('isii', $questionId, $optText, $isCorrect, $optOrder);
                    if (!$optStmt->execute()) {
                        throw new Exception("Question #" . ($i + 1) . ": failed to save an option.");
                    }
                    $optOrder++;
                }
                $imported++;
            }
            $qStmt->close();
            $optStmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Imported {$imported} question(s) successfully."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

if (isset($conn)) {
    mysqli_close($conn);
}
