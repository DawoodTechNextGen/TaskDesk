<?php
session_start();
include '../include/connection.php';
include_once '../include/hackathon_helper.php';

enforceModuleAccess(MODULE_HACKATHONS, ['delete']);

header('Content-Type: application/json');

$action = requestedAction();

switch ($action) {
    case 'get':
        $hackathonId = (int)($_REQUEST['hackathon_id'] ?? 0);
        if ($hackathonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid hackathon.']);
            break;
        }

        $stmt = $conn->prepare("SELECT r.*, t.name AS technology_name
            FROM hackathon_registrations r
            LEFT JOIN technologies t ON t.id = r.technology_id
            WHERE r.hackathon_id = ?
            ORDER BY r.created_at DESC");
        $stmt->bind_param('i', $hackathonId);
        $stmt->execute();
        $registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $ids = array_column($registrations, 'id');
        $membersByRegistration = [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $memberStmt = $conn->prepare("SELECT * FROM hackathon_team_members WHERE registration_id IN ($placeholders) ORDER BY is_leader DESC, id ASC");
            $memberStmt->bind_param($types, ...$ids);
            $memberStmt->execute();
            $members = $memberStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $memberStmt->close();

            foreach ($members as $member) {
                $membersByRegistration[$member['registration_id']][] = $member;
            }
        }

        foreach ($registrations as &$reg) {
            $reg['members'] = $membersByRegistration[$reg['id']] ?? [];
        }
        unset($reg);

        echo json_encode(['success' => true, 'data' => $registrations]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $hackathonId = (int)($_POST['hackathon_id'] ?? 0);
        if ($id <= 0 || $hackathonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid registration.']);
            break;
        }

        // FK ON DELETE CASCADE removes its team members too.
        $stmt = $conn->prepare("DELETE FROM hackathon_registrations WHERE id = ? AND hackathon_id = ?");
        $stmt->bind_param('ii', $id, $hackathonId);
        $success = $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        echo json_encode(['success' => $success && $deleted, 'message' => ($success && $deleted) ? 'Registration deleted.' : 'Failed to delete registration.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
