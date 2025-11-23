<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}
if ($data['action'] === 'create') {
    $title       = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $assign_to   = (int)($data['user_id'] ?? 0);
    $due_date    = $data['due_date'] ?? '';
    $created_by  = $_SESSION['user_id'];

    if (empty($title) || empty($description) || $assign_to <= 0 || empty($due_date)) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $due_date)) {
        echo json_encode(["success" => false, "message" => "Invalid date format"]);
        exit;
    }

    $status = 'pending';

    $stmt = $conn->prepare("
        INSERT INTO tasks 
        (title, description, assign_to, created_by, status, due_date, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssisss", $title, $description, $assign_to, $created_by, $status, $due_date);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Task created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create task"]);
    }
    $stmt->close();
    exit;
}
if ($data['action'] === 'get') {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT t.*, u.name as assign_to, u.id as assign_id, tech.name as technology_name, tech.id as tech_id FROM tasks t LEFT JOIN users u ON t.assign_to = u.id LEFT JOIN technologies tech ON u.tech_id = tech.id where created_by = ?");
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Tasks fetched successfully",
            "data"    => $tasks
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
// ====================== UPDATE TASK (now with due_date) ======================
if ($data['action'] === 'update') {
    $id            = (int)($data['id'] ?? 0);
    $title         = trim($data['title'] ?? '');
    $description   = trim($data['description'] ?? '');
    $assign_to     = (int)($data['user_id'] ?? 0);
    $due_date      = $data['due_date'] ?? null;

    if ($id <= 0 || empty($title) || empty($description) || $assign_to <= 0) {
        echo json_encode(["success" => false, "message" => "Required fields missing"]);
        exit;
    }

    // Build dynamic query
    $sql = "UPDATE tasks SET title = ?, description = ?, assign_to = ?";
    $types = "ssi";
    $params = [$title, $description, $assign_to];

    if ($due_date && preg_match("/^\d{4}-\d{2}-\d{2}$/", $due_date)) {
        $sql .= ", due_date = ?";
        $types .= "s";
        $params[] = $due_date;
    } else {
        $sql .= ", due_date = NULL";
    }

    $sql .= " WHERE id = ? AND created_by = ?";
    $types .= "ii";
    $params[] = $id;
    $params[] = $_SESSION['user_id'];

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    echo $stmt->execute()
        ? json_encode(["success" => true, "message" => "Task updated successfully"])
        : json_encode(["success" => false, "message" => "Update failed or unauthorized"]);

    $stmt->close();
    exit;
}
if ($data['action'] === 'getAssignedTask') {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("select t.id, t.title,t.description, t.due_date, t.status,t.created_at,t.started_at,
    t.completed_at,u.id as assign_id, 
    u.name as assign_by from tasks t JOIN users u on u.id = t.created_by where assign_to = ? ORDER BY id DESC");
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Tasks fetched successfully",
            "data"    => $tasks
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'getAllTasks') {
    $user_id = (int)$_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $stmt = $conn->prepare("SELECT t.title, t.status,u.name as assign_to FROM tasks t JOIN users u on u.id = t.assign_to WHERE  (? IN ('hod', 'manager')) OR (assign_to = ? OR created_by = ?)");
    $stmt->bind_param("sii", $user_role, $user_id, $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Tasks fetched successfully",
            "data"    => $tasks
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'getCompleteTasks') {
    $user_id = (int)$_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    $stmt = $conn->prepare("SELECT t.title,t.status, u.name as assign_to FROM tasks t JOIN users u on u.id = t.assign_to WHERE status = 'complete' AND (? IN ('hod','manager') OR assign_to = ? OR created_by = ?)");
    $stmt->bind_param("sii", $user_role, $user_id, $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Tasks fetched successfully",
            "data"    => $tasks
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'getWorkingTasks') {
    $user_id = (int)$_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $stmt = $conn->prepare("SELECT t.title, t.status, u.name AS assign_to FROM tasks t JOIN users u ON u.id = t.assign_to WHERE t.status = 'working' AND (? IN ('hod','manager') OR t.assign_to = ? OR t.created_by = ?)");
    $stmt->bind_param("sii", $user_role, $user_id, $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Tasks fetched successfully",
            "data"    => $tasks
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
if ($data['action'] === 'getPendingTasks') {
    $user_id = (int)$_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $stmt = $conn->prepare("SELECT t.title,t.status, u.name as assign_to FROM tasks t JOIN users u on u.id = t.assign_to WHERE status = 'pending' AND (? IN ('hod','manager') OR assign_to = ? OR created_by = ?)");
    $stmt->bind_param("sii", $user_role, $user_id, $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Tasks fetched successfully",
            "data"    => $tasks
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch tasks"
        ]);
    }

    $stmt->close();
}
