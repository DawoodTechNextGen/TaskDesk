<?php
session_start();
include '../include/connection.php';
include_once '../include/hackathon_helper.php';

enforceModuleAccess(MODULE_HACKATHONS, ['create', 'update', 'delete']);

header('Content-Type: application/json');

$action = requestedAction();

switch ($action) {
    case 'get':
        $stmt = $conn->query("SELECT h.*,
                (SELECT COUNT(*) FROM hackathon_registrations r WHERE r.hackathon_id = h.id) AS registration_count
            FROM hackathons h
            ORDER BY h.created_at DESC");
        $data = $stmt->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'get_one':
        $id = (int)($_REQUEST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM hackathons WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Hackathon not found']);
            break;
        }
        echo json_encode(['success' => true, 'data' => $row]);
        break;

    case 'create':
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $mode = $_POST['mode'] ?? 'online';
        $status = $_POST['status'] ?? 'draft';
        $allowIndividual = !empty($_POST['allow_individual']) ? 1 : 0;
        $allowTeam = !empty($_POST['allow_team']) ? 1 : 0;
        $minTeamSize = (int)($_POST['min_team_size'] ?? 1);
        $maxTeamSize = (int)($_POST['max_team_size'] ?? 4);
        $registrationStart = trim($_POST['registration_start'] ?? '') ?: null;
        $registrationEnd = trim($_POST['registration_end'] ?? '') ?: null;
        $eventStart = trim($_POST['event_start'] ?? '') ?: null;
        $eventEnd = trim($_POST['event_end'] ?? '') ?: null;
        $venue = trim($_POST['venue'] ?? '') ?: null;

        // ---- Validation (mirrors the public Node.js API's own rules) ----
        if (mb_strlen($title) < 2 || mb_strlen($title) > 150) {
            echo json_encode(['success' => false, 'message' => 'Title must be between 2 and 150 characters.']);
            break;
        }
        if (!isValidHackathonSlug($slug)) {
            echo json_encode(['success' => false, 'message' => 'Slug must be lowercase letters/numbers separated by single hyphens (e.g. "summer-hackathon-2026").']);
            break;
        }
        if (!in_array($mode, ['online', 'onsite', 'hybrid'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid mode.']);
            break;
        }
        if (!array_key_exists($status, hackathonStatusLabels())) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            break;
        }
        if (!$allowIndividual && !$allowTeam) {
            echo json_encode(['success' => false, 'message' => 'At least one of Allow Individual or Allow Team must be enabled.']);
            break;
        }
        if ($minTeamSize < 1 || $maxTeamSize < 1 || $minTeamSize > $maxTeamSize) {
            echo json_encode(['success' => false, 'message' => 'Min team size must be at least 1 and cannot exceed Max team size.']);
            break;
        }

        // Slug uniqueness, checked up front for a clear message instead of a raw DB error.
        $dupStmt = $conn->prepare("SELECT id FROM hackathons WHERE slug = ? AND id != ?");
        $dupStmt->bind_param('si', $slug, $id);
        $dupStmt->execute();
        if ($dupStmt->get_result()->fetch_assoc()) {
            $dupStmt->close();
            echo json_encode(['success' => false, 'message' => 'That slug is already used by another hackathon. Please choose a different one.']);
            break;
        }
        $dupStmt->close();

        $bannerResult = saveHackathonBanner($_FILES['banner'] ?? []);
        if (!$bannerResult['ok']) {
            echo json_encode(['success' => false, 'message' => $bannerResult['message']]);
            break;
        }
        $bannerUrl = $bannerResult['path'];

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO hackathons
                (slug, title, description, mode, status, allow_individual, allow_team, min_team_size, max_team_size,
                 registration_start, registration_end, event_start, event_end, venue, banner_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'sssssiiiissssss',
                $slug,
                $title,
                $description,
                $mode,
                $status,
                $allowIndividual,
                $allowTeam,
                $minTeamSize,
                $maxTeamSize,
                $registrationStart,
                $registrationEnd,
                $eventStart,
                $eventEnd,
                $venue,
                $bannerUrl
            );
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success, 'message' => $success ? 'Hackathon created successfully.' : 'Failed to create hackathon.']);
            break;
        }

        // update
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid hackathon.']);
            break;
        }

        if ($bannerUrl !== null) {
            // Replacing the banner - remove the old file once the new one is confirmed saved.
            $oldStmt = $conn->prepare("SELECT banner_url FROM hackathons WHERE id = ?");
            $oldStmt->bind_param('i', $id);
            $oldStmt->execute();
            $oldBanner = $oldStmt->get_result()->fetch_assoc()['banner_url'] ?? null;
            $oldStmt->close();

            $stmt = $conn->prepare("UPDATE hackathons SET
                slug = ?, title = ?, description = ?, mode = ?, status = ?, allow_individual = ?, allow_team = ?,
                min_team_size = ?, max_team_size = ?, registration_start = ?, registration_end = ?,
                event_start = ?, event_end = ?, venue = ?, banner_url = ?
                WHERE id = ?");
            $stmt->bind_param(
                'sssssiiiissssssi',
                $slug,
                $title,
                $description,
                $mode,
                $status,
                $allowIndividual,
                $allowTeam,
                $minTeamSize,
                $maxTeamSize,
                $registrationStart,
                $registrationEnd,
                $eventStart,
                $eventEnd,
                $venue,
                $bannerUrl,
                $id
            );
            $success = $stmt->execute();
            $stmt->close();
            if ($success && $oldBanner) {
                deleteHackathonBannerFile($oldBanner);
            }
        } else {
            $stmt = $conn->prepare("UPDATE hackathons SET
                slug = ?, title = ?, description = ?, mode = ?, status = ?, allow_individual = ?, allow_team = ?,
                min_team_size = ?, max_team_size = ?, registration_start = ?, registration_end = ?,
                event_start = ?, event_end = ?, venue = ?
                WHERE id = ?");
            $stmt->bind_param(
                'sssssiiiisssssi',
                $slug,
                $title,
                $description,
                $mode,
                $status,
                $allowIndividual,
                $allowTeam,
                $minTeamSize,
                $maxTeamSize,
                $registrationStart,
                $registrationEnd,
                $eventStart,
                $eventEnd,
                $venue,
                $id
            );
            $success = $stmt->execute();
            $stmt->close();
        }

        echo json_encode(['success' => $success, 'message' => $success ? 'Hackathon updated successfully.' : 'Failed to update hackathon.']);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid hackathon.']);
            break;
        }

        $bannerStmt = $conn->prepare("SELECT banner_url FROM hackathons WHERE id = ?");
        $bannerStmt->bind_param('i', $id);
        $bannerStmt->execute();
        $banner = $bannerStmt->get_result()->fetch_assoc()['banner_url'] ?? null;
        $bannerStmt->close();

        // FK ON DELETE CASCADE removes its registrations, team members and leaderboard entries.
        $stmt = $conn->prepare("DELETE FROM hackathons WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success && $banner) {
            deleteHackathonBannerFile($banner);
        }

        echo json_encode(['success' => $success, 'message' => $success ? 'Hackathon deleted.' : 'Failed to delete hackathon.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
