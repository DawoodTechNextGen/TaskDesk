<?php
session_start();
include '../include/connection.php';
include_once '../include/hackathon_helper.php';

enforceModuleAccess(MODULE_HACKATHONS, ['create', 'update', 'delete']);

header('Content-Type: application/json');

$action = requestedAction();

switch ($action) {
    case 'get':
        $hackathonId = (int)($_REQUEST['hackathon_id'] ?? 0);
        if ($hackathonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid hackathon.']);
            break;
        }

        $stmt = $conn->prepare("SELECT e.*, r.name AS registrant_name, r.team_name AS registrant_team_name
            FROM hackathon_leaderboard_entries e
            LEFT JOIN hackathon_registrations r ON r.id = e.registration_id
            WHERE e.hackathon_id = ?
            ORDER BY e.rank ASC");
        $stmt->bind_param('i', $hackathonId);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'search_registrations':
        $hackathonId = (int)($_REQUEST['hackathon_id'] ?? 0);
        $q = trim($_REQUEST['q'] ?? '');
        if ($hackathonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid hackathon.']);
            break;
        }

        $like = '%' . $q . '%';
        $stmt = $conn->prepare("SELECT id, registration_type, name, team_name, email
            FROM hackathon_registrations
            WHERE hackathon_id = ? AND (name LIKE ? OR team_name LIKE ? OR email LIKE ?)
            ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param('isss', $hackathonId, $like, $like, $like);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $hackathonId = (int)($_POST['hackathon_id'] ?? 0);
        $rank = (int)($_POST['rank'] ?? 0);
        $displayName = trim($_POST['display_name'] ?? '');
        $projectTitle = trim($_POST['project_title'] ?? '') ?: null;
        $scoreRaw = trim($_POST['score'] ?? '');
        $score = ($scoreRaw === '') ? null : (float)$scoreRaw;
        $prize = trim($_POST['prize'] ?? '') ?: null;
        $registrationId = (int)($_POST['registration_id'] ?? 0);
        $registrationId = $registrationId > 0 ? $registrationId : null;

        if ($hackathonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid hackathon.']);
            break;
        }
        if ($rank <= 0) {
            echo json_encode(['success' => false, 'message' => 'Rank must be a positive number.']);
            break;
        }
        if ($displayName === '' || mb_strlen($displayName) > 150) {
            echo json_encode(['success' => false, 'message' => 'Display name is required (max 150 characters).']);
            break;
        }

        // Rank must be unique per hackathon - checked up front for a clear message
        // instead of relying on the raw `uniq_leaderboard_hackathon_rank` DB error.
        $dupStmt = $conn->prepare("SELECT id FROM hackathon_leaderboard_entries WHERE hackathon_id = ? AND `rank` = ? AND id != ?");
        $dupStmt->bind_param('iii', $hackathonId, $rank, $id);
        $dupStmt->execute();
        if ($dupStmt->get_result()->fetch_assoc()) {
            $dupStmt->close();
            echo json_encode(['success' => false, 'message' => "Rank $rank is already used by another entry for this hackathon."]);
            break;
        }
        $dupStmt->close();

        if ($registrationId !== null) {
            $regCheck = $conn->prepare("SELECT id FROM hackathon_registrations WHERE id = ? AND hackathon_id = ?");
            $regCheck->bind_param('ii', $registrationId, $hackathonId);
            $regCheck->execute();
            if (!$regCheck->get_result()->fetch_assoc()) {
                $regCheck->close();
                echo json_encode(['success' => false, 'message' => 'The linked registration does not belong to this hackathon.']);
                break;
            }
            $regCheck->close();
        }

        try {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO hackathon_leaderboard_entries
                    (hackathon_id, `rank`, display_name, project_title, score, prize, registration_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('iissdsi', $hackathonId, $rank, $displayName, $projectTitle, $score, $prize, $registrationId);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'message' => $success ? 'Leaderboard entry added.' : 'Failed to add entry.']);
            } else {
                if ($id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid entry.']);
                    break;
                }
                $stmt = $conn->prepare("UPDATE hackathon_leaderboard_entries SET
                    `rank` = ?, display_name = ?, project_title = ?, score = ?, prize = ?, registration_id = ?
                    WHERE id = ? AND hackathon_id = ?");
                $stmt->bind_param('issdsiii', $rank, $displayName, $projectTitle, $score, $prize, $registrationId, $id, $hackathonId);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'message' => $success ? 'Leaderboard entry updated.' : 'Failed to update entry.']);
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                echo json_encode(['success' => false, 'message' => "Rank $rank is already used by another entry for this hackathon."]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save entry.']);
            }
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $hackathonId = (int)($_POST['hackathon_id'] ?? 0);
        if ($id <= 0 || $hackathonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid entry.']);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM hackathon_leaderboard_entries WHERE id = ? AND hackathon_id = ?");
        $stmt->bind_param('ii', $id, $hackathonId);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Entry deleted.' : 'Failed to delete entry.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
