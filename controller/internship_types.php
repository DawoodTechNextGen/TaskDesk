<?php
// Admin settings for the internship types offered on the public registration
// form - see include/internship_type_helper.php.
session_start();
include '../include/connection.php';
require_once '../include/internship_type_helper.php';
header('Content-Type: application/json');

requireAdminAction();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'data' => getInternshipTypes($conn)]);
        break;

    case 'add':
    case 'save':
        $typeValue = (int)($_POST['type_value'] ?? -1);
        $label = trim($_POST['label'] ?? '');
        $isEnabled = !empty($_POST['is_enabled']) ? 1 : 0;
        $includesTitle = trim($_POST['includes_title'] ?? '');
        // One item per line; blank lines dropped.
        $items = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)($_POST['includes_items'] ?? ''))), 'strlen');
        $includesItems = implode("\n", $items);

        if ($label === '') {
            echo json_encode(['success' => false, 'message' => 'Label is required']);
            exit;
        }
        if (mb_strlen($label) > 100 || mb_strlen($includesTitle) > 150) {
            echo json_encode(['success' => false, 'message' => 'Label or title is too long']);
            exit;
        }

        $existing = getInternshipTypes($conn);
        foreach ($existing as $type) {
            if ($type['type_value'] !== $typeValue && strcasecmp($type['label'], $label) === 0) {
                echo json_encode(['success' => false, 'message' => 'Another internship type already uses that label']);
                exit;
            }
        }

        if ($action === 'add') {
            try {
                $newValue = addInternshipType($conn, $label, $isEnabled, $includesTitle, $includesItems);
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to add internship type']);
                exit;
            }
            logActivity('Add Internship Type', "Added internship type $newValue ($label)");
            echo json_encode(['success' => true, 'message' => 'Internship type added']);
            exit;
        }

        if (!in_array($typeValue, array_column($existing, 'type_value'), true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE internship_types SET label = ?, is_enabled = ?, includes_title = ?, includes_items = ? WHERE type_value = ?");
        $stmt->bind_param('sissi', $label, $isEnabled, $includesTitle, $includesItems, $typeValue);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            logActivity('Update Internship Type', "Internship type $typeValue ($label) " . ($isEnabled ? 'enabled' : 'disabled'));
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Saved' : 'Failed to save']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
