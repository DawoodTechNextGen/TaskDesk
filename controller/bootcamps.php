<?php
session_start();
include '../include/connection.php';
include_once '../include/bootcamp_helper.php';

// Bootcamps are part of the Bootcamp module: anyone who can see enrollments can
// see the list, and only roles allowed to edit the module can change it.
enforceModuleAccess(MODULE_BOOTCAMP, ['create', 'update', 'delete']);

header('Content-Type: application/json');

if (!in_array(currentUserRole(), [ROLE_ADMIN, ROLE_MANAGER, ROLE_COLLABORATOR], true)) {
    denyJson('Unauthorized access');
}

$action = requestedAction();

switch ($action) {
    case 'get':
        $result = $conn->query("SELECT bc.*,
                " . bootcampSeatsFilledSql('bc.id') . " AS seats_filled,
                (SELECT COUNT(*) FROM " . BOOTCAMP_TABLE . " r WHERE r.bootcamp_id = bc.id) AS registration_count
            FROM " . BOOTCAMPS_TABLE . " bc
            ORDER BY bc.created_at DESC");
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $highlights = trim($_POST['highlights'] ?? '');
        $mode = $_POST['mode'] ?? 'online';
        $status = $_POST['status'] ?? 'draft';
        $totalSeats = (int)($_POST['total_seats'] ?? 0);
        $fee = trim($_POST['fee'] ?? '') === '' ? 0 : (float)$_POST['fee'];
        $duration = trim($_POST['duration'] ?? '') ?: null;
        $schedule = trim($_POST['schedule'] ?? '') ?: null;
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate = trim($_POST['end_date'] ?? '') ?: null;
        $registrationStart = trim($_POST['registration_start'] ?? '') ?: null;
        $registrationEnd = trim($_POST['registration_end'] ?? '') ?: null;
        $venue = trim($_POST['venue'] ?? '') ?: null;

        if (mb_strlen($title) < 2 || mb_strlen($title) > 150) {
            echo json_encode(['success' => false, 'message' => 'Title must be between 2 and 150 characters.']);
            break;
        }
        if (!isValidBootcampSlug($slug) || mb_strlen($slug) > 120) {
            echo json_encode(['success' => false, 'message' => 'Slug must be lowercase letters/numbers separated by single hyphens (e.g. "web-dev-bootcamp-2026").']);
            break;
        }
        if (!array_key_exists($mode, bootcampModeLabels())) {
            echo json_encode(['success' => false, 'message' => 'Invalid mode.']);
            break;
        }
        if (!array_key_exists($status, bootcampStatusLabels())) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            break;
        }
        if ($totalSeats < 0 || $totalSeats > 100000) {
            echo json_encode(['success' => false, 'message' => 'Total seats must be 0 (unlimited) or a positive number.']);
            break;
        }
        if ($fee < 0) {
            echo json_encode(['success' => false, 'message' => 'Fee cannot be negative.']);
            break;
        }
        if ($startDate && $endDate && $endDate < $startDate) {
            echo json_encode(['success' => false, 'message' => 'End date cannot be before the start date.']);
            break;
        }
        if ($registrationStart && $registrationEnd && $registrationEnd < $registrationStart) {
            echo json_encode(['success' => false, 'message' => 'Registration end cannot be before registration start.']);
            break;
        }

        $dupStmt = $conn->prepare("SELECT id FROM " . BOOTCAMPS_TABLE . " WHERE slug = ? AND id != ?");
        $dupStmt->bind_param('si', $slug, $id);
        $dupStmt->execute();
        if ($dupStmt->get_result()->fetch_assoc()) {
            $dupStmt->close();
            echo json_encode(['success' => false, 'message' => 'That slug is already used by another bootcamp. Please choose a different one.']);
            break;
        }
        $dupStmt->close();

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO " . BOOTCAMPS_TABLE . "
                (slug, title, description, highlights, mode, status, total_seats, fee, duration, schedule,
                 start_date, end_date, registration_start, registration_end, venue)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'ssssssidsssssss',
                $slug, $title, $description, $highlights, $mode, $status, $totalSeats, $fee, $duration, $schedule,
                $startDate, $endDate, $registrationStart, $registrationEnd, $venue
            );
            $success = $stmt->execute();
            $stmt->close();
            if ($success) {
                logActivity('Create Bootcamp', "Created bootcamp: {$title}");
            }
            echo json_encode(['success' => $success, 'message' => $success ? 'Bootcamp created successfully.' : 'Failed to create bootcamp.']);
            break;
        }

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid bootcamp.']);
            break;
        }

        // Lowering seats below what is already taken would silently over-book it.
        if ($totalSeats > 0) {
            $filledStmt = $conn->prepare("SELECT " . bootcampSeatsFilledSql('?') . " AS filled");
            $filledStmt->bind_param('i', $id);
            $filledStmt->execute();
            $filled = (int)($filledStmt->get_result()->fetch_assoc()['filled'] ?? 0);
            $filledStmt->close();
            if ($totalSeats < $filled) {
                echo json_encode(['success' => false, 'message' => "{$filled} seats are already taken, so total seats cannot be less than {$filled}."]);
                break;
            }
        }

        $stmt = $conn->prepare("UPDATE " . BOOTCAMPS_TABLE . " SET
            slug = ?, title = ?, description = ?, highlights = ?, mode = ?, status = ?, total_seats = ?, fee = ?,
            duration = ?, schedule = ?, start_date = ?, end_date = ?, registration_start = ?, registration_end = ?, venue = ?
            WHERE id = ?");
        $stmt->bind_param(
            'ssssssidsssssssi',
            $slug, $title, $description, $highlights, $mode, $status, $totalSeats, $fee, $duration, $schedule,
            $startDate, $endDate, $registrationStart, $registrationEnd, $venue, $id
        );
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            logActivity('Update Bootcamp', "Updated bootcamp: {$title}");
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Bootcamp updated successfully.' : 'Failed to update bootcamp.']);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid bootcamp.']);
            break;
        }

        // Enrollments are real people's data - a bootcamp that has any is closed
        // or completed instead of deleted, so nothing is lost by accident.
        $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM " . BOOTCAMP_TABLE . " WHERE bootcamp_id = ?");
        $countStmt->bind_param('i', $id);
        $countStmt->execute();
        $registrations = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $countStmt->close();
        if ($registrations > 0) {
            echo json_encode(['success' => false, 'message' => "This bootcamp has {$registrations} enrollment(s), so it cannot be deleted. Set its status to Closed or Completed instead."]);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM " . BOOTCAMPS_TABLE . " WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            logActivity('Delete Bootcamp', "Deleted bootcamp #{$id}");
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Bootcamp deleted.' : 'Failed to delete bootcamp.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
