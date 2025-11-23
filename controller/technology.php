<?php
include '../include/connection.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get':
        $stmt = $conn->query("SELECT * FROM technologies ORDER BY id DESC");
        $data = $stmt->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
        $name = trim($_POST['name']);
        $stmt = $conn->prepare("INSERT INTO technologies (name) VALUES (?)");
        $stmt->bind_param('s', $name);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'message' => $success ? 'Technology added!' : 'Error']);
        break;

    case 'update':
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $stmt = $conn->prepare("UPDATE technologies SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $name, $id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'message' => $success ? 'Updated!' : 'Error']);
        break;

    case 'delete':
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM technologies WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'message' => $success ? 'Deleted!' : 'Error']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>