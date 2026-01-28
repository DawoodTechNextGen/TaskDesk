<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";
include_once "../include/notification_helper.php";

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

    // Check if the intern already has an active task (pending or working)
    $check_stmt = $conn->prepare("SELECT id, title FROM tasks WHERE assign_to = ? AND status IN ('pending', 'working')");
    $check_stmt->bind_param("i", $assign_to);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows > 0) {
        $existing_task = $result->fetch_assoc();
        echo json_encode([
            "success" => false, 
            "message" => "Intern already has an active task: '" . $existing_task['title'] . "'. Please wait for completion or mark as expired."
        ]);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    $status = 'pending';

    $stmt = $conn->prepare("
        INSERT INTO tasks 
        (title, description, assign_to, created_by, status, due_date, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssisss", $title, $description, $assign_to, $created_by, $status, $due_date);

    if ($stmt->execute()) {
        // --- Send Notification ---
        $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $assign_to);
        $user_stmt->execute();
        $user_data = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        if ($user_data && !empty($user_data['email'])) {
            $subject = "Task Assigned: " . $title;
            // Professional HTML Content
            $html_content = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Hello " . htmlspecialchars($user_data['name']) . ",</h2>
                    <p>You have been assigned a new task: <strong>" . htmlspecialchars($title) . "</strong></p>
                    <p><strong>Due Date:</strong> " . htmlspecialchars($due_date) . "</p>
                    <p>Please log in to your dashboard to start working on it.</p>
                    <br>
                    <p>Best Regards,<br>Management Team</p>
                </div>
            ";

            sendNotificationFallback([
                'email' => $user_data['email'],
                'name' => $user_data['name'],
                'subject' => $subject,
                'html_content' => $html_content
            ]);
        }
        // --- End Notification ---

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
    $stmt->close();
}

// ====================== COMPLETE TASK (Intern Action) ======================
if ($data['action'] === 'complete_task') {
    $task_id = (int)($data['task_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid task ID"]);
        exit;
    }
    
    // Verify task is assigned to user and is currently working
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND assign_to = ? AND status = 'working'");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Task not found or not in working status"]);
        exit;
    }
    $stmt->close();
    
    // Update status to 'pending_review' (waiting for supervisor approval)
    // Note: User requested "when supervisor approve then its mark as complete", so intern completes -> pending_review
    $stmt = $conn->prepare("UPDATE tasks SET status = 'pending_review', completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Task submitted for review"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to submit task"]);
    }
    $stmt->close();
    exit;
}

// Review Task Action (Approve/Reject/Request Improvements)
if ($data['action'] === 'review_task') {
    $task_id = (int)($data['task_id'] ?? 0);
    $review_action = $data['review_action'] ?? ''; // 'approved', 'rejected', 'needs_improvement'
    $review_notes = trim($data['review_notes'] ?? '');
    $supervisor_id = $_SESSION['user_id'];
    
    if ($task_id <= 0 || !in_array($review_action, ['approved', 'rejected', 'needs_improvement'])) {
        echo json_encode(["success" => false, "message" => "Invalid review data"]);
        exit;
    }
    
    // Verify supervisor created this task (or is admin/manager?)
    // For now assuming creator is supervisor
    $check_stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND created_by = ?");
    $check_stmt->bind_param("ii", $task_id, $supervisor_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0 && $_SESSION['user_role'] != 1) { // Allow admin too maybe?
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
    
    // Map review action to task status
    $new_status = '';
    if ($review_action === 'approved') {
        $new_status = 'complete';
    } elseif ($review_action === 'rejected') {
        $new_status = 'expired'; // As requested
    } elseif ($review_action === 'needs_improvement') {
        $new_status = 'working'; // Re-open loop
    }
    
    $stmt = $conn->prepare("UPDATE tasks SET status = ?, review_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("ssii", $new_status, $review_notes, $supervisor_id, $task_id);
    
    if ($stmt->execute()) {
        $message = $review_action === 'approved' ? 'Task approved and marked as complete' : 
                   ($review_action === 'rejected' ? 'Task rejected and marked as expired' : 'Task returned for improvements');
        echo json_encode(["success" => true, "message" => $message]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to review task"]);
    }
    $stmt->close();
    exit;
}

// Reactivate Expired Task
if ($data['action'] === 'reactivate_task') {
    $task_id = (int)($data['task_id'] ?? 0);
    $new_due_date = $data['new_due_date'] ?? '';
    $supervisor_id = $_SESSION['user_id'];
    
    if ($task_id <= 0 || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $new_due_date)) {
        echo json_encode(["success" => false, "message" => "Invalid data"]);
        exit;
    }
    
    // Verify supervisor created this task
    $check_stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND created_by = ? AND status = 'expired'");
    $check_stmt->bind_param("ii", $task_id, $supervisor_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Task not found or not expired"]);
        exit;
    }
    
    $new_status = 'pending';
    $stmt = $conn->prepare("UPDATE tasks SET due_date = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_due_date, $new_status, $task_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Task reactivated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to reactivate task"]);
    }
    $stmt->close();
    exit;
}
