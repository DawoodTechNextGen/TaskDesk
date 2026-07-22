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

    // Check if the intern already has an active task (inprogress or needs_improvement)
    $check_stmt = $conn->prepare("SELECT id, title FROM tasks WHERE assign_to = ? AND status IN ('inprogress', 'needs_improvement')");
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

    $status = 'inprogress';

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
                    <h2>Assalam O Alaikum " . htmlspecialchars($user_data['name']) . ",</h2>
                    <p>You have been assigned a new task: <strong>" . htmlspecialchars($title) . "</strong></p>
                    <p><strong>Due Date:</strong> " . htmlspecialchars(date('j F Y', strtotime($due_date))) . "</p>
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
    $user_role = $_SESSION['user_role'];
    $status = $data['status'] ?? null;

    $sql = "SELECT t.*, u.name as assign_to, u.id as assign_id, tech.name as technology_name, tech.id as tech_id 
            FROM tasks t 
            LEFT JOIN users u ON t.assign_to = u.id 
            LEFT JOIN technologies tech ON u.tech_id = tech.id";
    
    // Admins and Managers see all tasks, others see only their own creations
    if ($user_role != 1 && $user_role != 4) {
        $sql .= " WHERE t.created_by = ?";
    } else {
        $sql .= " WHERE 1=1";
    }
    
    if ($status) {
        if ($status === 'expired') {
            $sql .= " AND t.status = 'expired'";
        } elseif ($status == 'inprogress') {
            $sql .= " AND t.status IN ('inprogress', 'needs_improvement') AND t.due_date >= CURDATE()";
        } else {
            $sql .= " AND t.status = ?";
        }
    }

    $stmt = $conn->prepare($sql);
    
    if ($user_role != 1 && $user_role != 4) {
        if ($status && $status !== 'expired' && $status !== 'inprogress') {
            $stmt->bind_param("ss", $user_id, $status);
        } else {
            $stmt->bind_param("s", $user_id);
        }
    } else {
        if ($status && $status !== 'expired' && $status !== 'inprogress') {
            $stmt->bind_param("s", $status);
        }
    }

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
if ($data['action'] === 'get_task') {
    $task_id = (int)($data['task_id'] ?? 0);
    $stmt = $conn->prepare("SELECT t.*, u.name as assign_to_name, c.name as created_by_name 
                            FROM tasks t 
                            LEFT JOIN users u ON t.assign_to = u.id 
                            LEFT JOIN users c ON t.created_by = c.id 
                            WHERE t.id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(["success" => true, "data" => $row]);
        } else {
            echo json_encode(["success" => false, "message" => "Task not found"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Query failed"]);
    }
    $stmt->close();
    exit;
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

    $sql .= " WHERE id = ?";
    $types .= "i";
    $params[] = $id;

    // Restrict to creator unless Admin or Manager
    if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4) {
        $sql .= " AND created_by = ?";
        $types .= "i";
        $params[] = $_SESSION['user_id'];
    }

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
    $status = $data['status'] ?? null;
    $sql = "select t.id, t.title,t.description, t.due_date, t.status,t.created_at,t.started_at,
    t.completed_at, t.review_notes, u.id as assign_id, 
    u.name as assign_by from tasks t JOIN users u on u.id = t.created_by where assign_to = ?";

    if ($status === 'expired') {
        $sql .= " AND (t.status = 'expired' OR (t.status IN ('inprogress', 'needs_improvement') AND t.due_date < CURDATE()))";
        $stmt = $conn->prepare($sql . " ORDER BY id DESC");
        $stmt->bind_param("s", $user_id);
    } elseif ($status == 'inprogress') {
        $sql .= " AND t.status IN ('inprogress', 'needs_improvement') AND t.due_date >= CURDATE()";
        $stmt = $conn->prepare($sql . " ORDER BY id DESC");
        $stmt->bind_param("s", $user_id);
    } elseif ($status) {
        $sql .= " AND t.status = ?";
        $stmt = $conn->prepare($sql . " ORDER BY id DESC");
        $stmt->bind_param("ss", $user_id, $status);
    } else {
        $stmt = $conn->prepare($sql . " ORDER BY id DESC");
        $stmt->bind_param("s", $user_id);
    }

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
    $stmt = $conn->prepare("SELECT t.title, t.status, u.name AS assign_to FROM tasks t JOIN users u ON u.id = t.assign_to WHERE t.status IN ('inprogress', 'needs_improvement') AND (? IN ('hod','manager') OR t.assign_to = ? OR t.created_by = ?)");
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
    $stmt = $conn->prepare("SELECT t.title,t.status, u.name as assign_to FROM tasks t JOIN users u on u.id = t.assign_to WHERE status IN ('inprogress', 'needs_improvement') AND (? IN ('hod','manager') OR assign_to = ? OR created_by = ?)");
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

// --- Delete Task Action ---
if ($data['action'] === 'delete') {
    $task_id = (int)($data['task_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid task ID"]);
        exit;
    }

    // Authorization: Admin (1), Manager (4) or the one who created it
    $check_stmt = $conn->prepare("SELECT created_by FROM tasks WHERE id = ?");
    $check_stmt->bind_param("i", $task_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Task not found"]);
        exit;
    }
    $task = $result->fetch_assoc();
    $check_stmt->close();

    if ($user_role != 1 && $user_role != 4 && $user_role != 3 && $task['created_by'] != $user_id) {
        echo json_encode(["success" => false, "message" => "Unauthorized to delete this task"]);
        exit;
    }

    // Begin transaction to delete related logs too (if any)
    $conn->begin_transaction();
    try {
        // Delete time logs
        $stmt_logs = $conn->prepare("DELETE FROM time_logs WHERE task_id = ?");
        $stmt_logs->bind_param("i", $task_id);
        $stmt_logs->execute();
        $stmt_logs->close();

        // Delete attendance records linked to this task
        $stmt_att = $conn->prepare("DELETE FROM attendance WHERE task_id = ?");
        $stmt_att->bind_param("i", $task_id);
        $stmt_att->execute();
        $stmt_att->close();

        // Delete the task itself
        $stmt_task = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt_task->bind_param("i", $task_id);
        $stmt_task->execute();
        $stmt_task->close();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Task and related records deleted successfully"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Failed to delete task: " . $e->getMessage()]);
    }
    exit;
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
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND assign_to = ? AND status = 'inprogress'");
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
    
    // Verify supervisor created this task (or is admin/manager)
    $check_stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND created_by = ?");
    $check_stmt->bind_param("ii", $task_id, $supervisor_id);
    $check_stmt->execute();
    $not_creator = $check_stmt->get_result()->num_rows === 0;
    $check_stmt->close();

    if ($not_creator && $_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4) { 
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
    
    // Map review action to task status
    $new_status = '';
    if ($review_action === 'approved') {
        $new_status = 'complete';
    } elseif ($review_action === 'rejected') {
        $new_status = 'rejected';
    } elseif ($review_action === 'needs_improvement') {
        $new_status = 'needs_improvement';
    }
    
    if ($new_status === 'needs_improvement') {
        $stmt = $conn->prepare("UPDATE tasks SET status = ?, review_notes = ?, reviewed_at = NOW(), reviewed_by = ?, completed_at = NULL, notification = 0, due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE tasks SET status = ?, review_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    }
    $stmt->bind_param("ssii", $new_status, $review_notes, $supervisor_id, $task_id);
    
    if ($stmt->execute()) {
        $message = $review_action === 'approved' ? 'Task approved and marked as complete' : 
                   ($review_action === 'rejected' ? 'Task rejected and marked as rejected' : 'Task returned for improvements');
        
        if ($review_action === 'approved') {
            // Get task details to see if it is a curriculum task
            $task_stmt = $conn->prepare("SELECT assign_to, week_number, is_curriculum_task FROM tasks WHERE id = ?");
            $task_stmt->bind_param("i", $task_id);
            $task_stmt->execute();
            $task_details = $task_stmt->get_result()->fetch_assoc();
            $task_stmt->close();

            if ($task_details && $task_details['is_curriculum_task'] == 1 && !empty($task_details['week_number'])) {
                $intern_id = $task_details['assign_to'];
                $curr_week = $task_details['week_number'];
                $next_week = $curr_week + 1;

                // Fetch intern details (tech_id, internship_duration, supervisor_id)
                $intern_stmt = $conn->prepare("SELECT name, email, tech_id, internship_duration, supervisor_id FROM users WHERE id = ?");
                $intern_stmt->bind_param("i", $intern_id);
                $intern_stmt->execute();
                $intern_details = $intern_stmt->get_result()->fetch_assoc();
                $intern_stmt->close();

                $creator_id = (!empty($intern_details['supervisor_id']) && $intern_details['supervisor_id'] > 0) ? (int)$intern_details['supervisor_id'] : $supervisor_id;

                if ($intern_details && !empty($intern_details['internship_duration']) && $intern_details['tech_id'] > 0) {
                    $tech_id = $intern_details['tech_id'];
                    $duration = $intern_details['internship_duration'];

                    // Check if next week curriculum task exists
                    $next_cur_stmt = $conn->prepare("SELECT title, description FROM curriculum_tasks WHERE tech_id = ? AND duration = ? AND week_number = ?");
                    $next_cur_stmt->bind_param("isi", $tech_id, $duration, $next_week);
                    $next_cur_stmt->execute();
                    $next_cur = $next_cur_stmt->get_result()->fetch_assoc();
                    $next_cur_stmt->close();

                    if ($next_cur) {
                        // Check if already assigned
                        $chk_stmt = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND week_number = ? AND is_curriculum_task = 1");
                        $chk_stmt->bind_param("ii", $intern_id, $next_week);
                        $chk_stmt->execute();
                        $already_assigned = $chk_stmt->get_result()->num_rows > 0;
                        $chk_stmt->close();

                        if (!$already_assigned) {
                            $due_date = date('Y-m-d', strtotime('+7 days'));
                            $status = 'inprogress';
                            $is_curriculum = 1;

                            $ins_stmt = $conn->prepare("INSERT INTO tasks (title, description, assign_to, created_by, status, due_date, week_number, is_curriculum_task, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                            $ins_stmt->bind_param("ssisssii", $next_cur['title'], $next_cur['description'], $intern_id, $creator_id, $status, $due_date, $next_week, $is_curriculum);
                            if ($ins_stmt->execute()) {
                                // Send Email Notification for new week
                                if (!empty($intern_details['email'])) {
                                    $subject = "Task Assigned: Week " . $next_week . " Task";
                                    $html_content = "
                                        <div style='font-family: Arial, sans-serif; color: #333;'>
                                            <h2>Assalam O Alaikum " . htmlspecialchars($intern_details['name']) . ",</h2>
                                            <p>Since your Week " . $curr_week . " task was approved, you have been automatically assigned your <strong>Week " . $next_week . " Task: " . htmlspecialchars($next_cur['title']) . "</strong></p>
                                            <p><strong>Due Date:</strong> " . htmlspecialchars(date('j F Y', strtotime($due_date))) . "</p>
                                            <p>Please log in to your dashboard to start working on it.</p>
                                            <br>
                                            <p>Best Regards,<br>Management Team</p>
                                        </div>
                                    ";
                                    sendNotificationFallback([
                                        'email' => $intern_details['email'],
                                        'name' => $intern_details['name'],
                                        'subject' => $subject,
                                        'html_content' => $html_content
                                    ]);
                                }
                            }
                            $ins_stmt->close();
                        }
                    }
                }
            } elseif ($task_details) {
                // Ongoing intern completes a regular task (not a curriculum task)
                $intern_id = $task_details['assign_to'];

                // Check if this intern has EVER had any curriculum task assigned
                $check_cur_stmt = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND is_curriculum_task = 1 LIMIT 1");
                $check_cur_stmt->bind_param("i", $intern_id);
                $check_cur_stmt->execute();
                $has_curriculum_started = ($check_cur_stmt->get_result()->num_rows > 0);
                $check_cur_stmt->close();

                if (!$has_curriculum_started) {
                    // Intern has never had a curriculum task assigned yet!
                    // Let's check if they have any remaining active tasks
                    $active_tasks_stmt = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND id != ? AND status IN ('inprogress', 'needs_improvement') LIMIT 1");
                    $active_tasks_stmt->bind_param("ii", $intern_id, $task_id);
                    $active_tasks_stmt->execute();
                    $has_other_active = ($active_tasks_stmt->get_result()->num_rows > 0);
                    $active_tasks_stmt->close();

                    // If they have no other active tasks in progress, automatically initialize their curriculum!
                    if (!$has_other_active) {
                        // Fetch intern details
                        $intern_stmt = $conn->prepare("SELECT name, email, tech_id, internship_duration, supervisor_id FROM users WHERE id = ?");
                        $intern_stmt->bind_param("i", $intern_id);
                        $intern_stmt->execute();
                        $intern_details = $intern_stmt->get_result()->fetch_assoc();
                        $intern_stmt->close();

                        $creator_id = (!empty($intern_details['supervisor_id']) && $intern_details['supervisor_id'] > 0) ? (int)$intern_details['supervisor_id'] : $supervisor_id;

                        if ($intern_details && !empty($intern_details['internship_duration']) && $intern_details['tech_id'] > 0) {
                            $tech_id = $intern_details['tech_id'];
                            $duration = $intern_details['internship_duration'];

                            // Calculate intern's actual completed weeks
                            $max_w_stmt = $conn->prepare("SELECT MAX(week_number) as max_w FROM tasks WHERE assign_to = ? AND week_number > 0");
                            $max_w_stmt->bind_param("i", $intern_id);
                            $max_w_stmt->execute();
                            $max_w_res = $max_w_stmt->get_result()->fetch_assoc();
                            $max_w = (int)($max_w_res['max_w'] ?? 0);
                            $max_w_stmt->close();

                            $cnt_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tasks WHERE assign_to = ? AND status IN ('complete', 'approved')");
                            $cnt_stmt->bind_param("i", $intern_id);
                            $cnt_stmt->execute();
                            $cnt_res = $cnt_stmt->get_result()->fetch_assoc();
                            $cnt = (int)($cnt_res['cnt'] ?? 0);
                            $cnt_stmt->close();

                            $completed_count = max($max_w, $cnt);
                            $next_week = $completed_count + 1;

                            // 2. Fetch the next week task from curriculum
                            $cur_stmt = $conn->prepare("SELECT title, description FROM curriculum_tasks WHERE tech_id = ? AND duration = ? AND week_number = ?");
                            $cur_stmt->bind_param("isi", $tech_id, $duration, $next_week);
                            $cur_stmt->execute();
                            $cur_task = $cur_stmt->get_result()->fetch_assoc();
                            $cur_stmt->close();

                            if ($cur_task) {
                                $due_date = date('Y-m-d', strtotime('+7 days'));
                                $status = 'inprogress';
                                $is_curriculum = 1;

                                $ins_stmt = $conn->prepare("INSERT INTO tasks (title, description, assign_to, created_by, status, due_date, week_number, is_curriculum_task, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                                $ins_stmt->bind_param("ssisssii", $cur_task['title'], $cur_task['description'], $intern_id, $creator_id, $status, $due_date, $next_week, $is_curriculum);
                                if ($ins_stmt->execute()) {
                                    // Send Email Notification for Next Week
                                    if (!empty($intern_details['email'])) {
                                        $subject = "Task Assigned: Week {$next_week} Curriculum Task";
                                        $html_content = "
                                            <div style='font-family: Arial, sans-serif; color: #333;'>
                                                <h2>Assalam O Alaikum " . htmlspecialchars($intern_details['name']) . ",</h2>
                                                <p>Your previous task has been approved. Your week-wise internship curriculum has now been automatically transitioned!</p>
                                                <p>You have been assigned your <strong>Week {$next_week} Task: " . htmlspecialchars($cur_task['title']) . "</strong></p>
                                                <p><strong>Due Date:</strong> " . htmlspecialchars(date('j F Y', strtotime($due_date))) . "</p>
                                                <p>Please log in to your dashboard to start working on it.</p>
                                                <br>
                                                <p>Best Regards,<br>Management Team</p>
                                            </div>
                                        ";
                                        sendNotificationFallback([
                                            'email' => $intern_details['email'],
                                            'name' => $intern_details['name'],
                                            'subject' => $subject,
                                            'html_content' => $html_content
                                        ]);
                                    }
                                }
                                $ins_stmt->close();
                            }
                        }
                    }
                }
            }
        }

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
    
    // Verify supervisor created this task (or is Admin/Manager)
    $check_stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND status = 'expired' AND (created_by = ? OR ? IN (1, 4))");
    $check_role = $_SESSION['user_role'];
    $check_stmt->bind_param("iii", $task_id, $supervisor_id, $check_role);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Task not found or not expired"]);
        exit;
    }
    
    $new_status = 'inprogress';
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

$conn->close();
?>
