<?php
// Admin management of the University dropdown on the public registration form -
// see include/university_helper.php.
session_start();
include '../include/connection.php';
require_once '../include/university_helper.php';
header('Content-Type: application/json');

requireAdminAction();
ensureUniversitySchema($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $res = $conn->query("SELECT u.id, u.name, u.city, u.is_active,
                                    (SELECT COUNT(*) FROM registrations r WHERE r.university = u.name) AS registrations
                             FROM universities u
                             ORDER BY u.name ASC");
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'add':
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim(preg_replace('/\s+/', ' ', (string)($_POST['name'] ?? '')));
        $city = trim((string)($_POST['city'] ?? ''));
        if ($name === '' || mb_strlen($name) > 191 || mb_strlen($city) > 100) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid university name']);
            exit;
        }

        $dup = $conn->prepare("SELECT id FROM universities WHERE name = ? AND id <> ? LIMIT 1");
        $dup->bind_param('si', $name, $id);
        $dup->execute();
        $exists = $dup->get_result()->num_rows > 0;
        $dup->close();
        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'That university is already in the list']);
            exit;
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO universities (name, city, is_active) VALUES (?, ?, 1)");
            $stmt->bind_param('ss', $name, $city);
        } else {
            $stmt = $conn->prepare("UPDATE universities SET name = ?, city = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $city, $id);
        }
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            logActivity($action === 'add' ? 'Add University' : 'Update University', $name);
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? ($action === 'add' ? 'University added' : 'University updated') : 'Failed to save']);
        break;

    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        $active = ((int)($_POST['is_active'] ?? 0) === 1) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE universities SET is_active = ? WHERE id = ?");
        $stmt->bind_param('ii', $active, $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => $ok ? ($active ? 'University activated' : 'University deactivated') : 'Failed to update']);
        break;

    case 'set_all':
        $active = ((int)($_POST['is_active'] ?? 0) === 1) ? 1 : 0;
        $ok = $conn->query("UPDATE universities SET is_active = $active");
        if ($ok) {
            logActivity('Update University', $active ? 'Activated all universities' : 'Deactivated all universities');
        }
        echo json_encode(['success' => (bool)$ok, 'message' => $active ? 'All universities activated' : 'All universities deactivated']);
        break;

    // Past registrations keep the university name as text, so removing one
    // from the list never changes them.
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM universities WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        if ($ok) {
            logActivity('Delete University', "University ID $id");
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? 'University removed' : 'Failed to remove']);
        break;

    // JSON from an uploaded .json file or pasted text: a list of names, or of
    // {"name": ..., "city": ..., "is_active": true/false} objects.
    case 'import':
        $raw = '';
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            if ($_FILES['file']['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'The file is too large (2 MB max)']);
                exit;
            }
            $raw = (string)file_get_contents($_FILES['file']['tmp_name']);
        } else {
            $raw = (string)($_POST['json'] ?? '');
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw));
        $items = json_decode($raw, true);
        if (is_array($items) && isset($items['universities']) && is_array($items['universities'])) {
            $items = $items['universities'];
        }
        if (!is_array($items) || array_values($items) !== $items) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON: expected a list like ["University A", "University B"] or [{"name": "...", "city": "..."}]']);
            exit;
        }
        if (count($items) > 5000) {
            echo json_encode(['success' => false, 'message' => 'Too many items in one import (5000 max)']);
            exit;
        }

        $result = importUniversities($conn, $items);
        logActivity('Import Universities', "Added {$result['added']}, skipped {$result['skipped']}, invalid {$result['invalid']}");
        echo json_encode([
            'success' => true,
            'message' => "Imported: {$result['added']} added, {$result['skipped']} already in the list" . ($result['invalid'] ? ", {$result['invalid']} invalid" : ''),
            'result' => $result,
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
