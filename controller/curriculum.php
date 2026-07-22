<?php
session_start();
include '../include/connection.php';
include '../include/notification_helper.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get':
        $tech_id = (int)($_GET['tech_id'] ?? 0);
        $duration = $_GET['duration'] ?? '';

        if ($tech_id <= 0 || empty($duration)) {
            echo json_encode(['success' => false, 'message' => 'Technology and Duration are required']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, week_number, title, description FROM curriculum_tasks WHERE tech_id = ? AND duration = ? ORDER BY week_number ASC");
        $stmt->bind_param('is', $tech_id, $duration);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $tasks]);
        break;

    case 'save':
        if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized to edit curriculum']);
            exit;
        }

        $tech_id = (int)($_POST['tech_id'] ?? 0);
        $duration = $_POST['duration'] ?? '';
        $week_number = (int)($_POST['week_number'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($tech_id <= 0 || empty($duration) || $week_number <= 0 || empty($title)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        // Validate max weeks based on duration
        $max_weeks = ($duration === '4 weeks') ? 4 : (($duration === '8 weeks') ? 8 : 12);
        if ($week_number > $max_weeks) {
            echo json_encode(['success' => false, 'message' => "Week number cannot exceed $max_weeks weeks for this duration"]);
            exit;
        }

        // Check if exists
        $stmt = $conn->prepare("SELECT id FROM curriculum_tasks WHERE tech_id = ? AND duration = ? AND week_number = ?");
        $stmt->bind_param('isi', $tech_id, $duration, $week_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            // Update
            $stmt = $conn->prepare("UPDATE curriculum_tasks SET title = ?, description = ? WHERE id = ?");
            $stmt->bind_param('ssi', $title, $description, $exists['id']);
            $success = $stmt->execute();
            $stmt->close();
            $msg = 'Curriculum task updated successfully!';
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO curriculum_tasks (tech_id, duration, week_number, title, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('isiss', $tech_id, $duration, $week_number, $title, $description);
            $success = $stmt->execute();
            $stmt->close();
            $msg = 'Curriculum task added successfully!';
        }

        echo json_encode(['success' => $success, 'message' => $success ? $msg : 'Database error occurred']);
        break;

    case 'delete':
        if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized to edit curriculum']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid curriculum task ID']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM curriculum_tasks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Curriculum task deleted successfully!' : 'Database error occurred']);
        break;

    case 'start_curriculum':
        // Start curriculum assigns Week 1 task to the intern
        $intern_id = (int)($_POST['intern_id'] ?? 0);
        if ($intern_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid intern ID']);
            exit;
        }

        // Fetch intern detail including supervisor_id
        $user_stmt = $conn->prepare("SELECT id, name, email, tech_id, internship_duration, supervisor_id FROM users WHERE id = ? AND user_role = 2 AND status = 1");
        $user_stmt->bind_param('i', $intern_id);
        $user_stmt->execute();
        $intern = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        if (!$intern) {
            echo json_encode(['success' => false, 'message' => 'Active intern not found']);
            exit;
        }

        if (empty($intern['internship_duration'])) {
            echo json_encode(['success' => false, 'message' => 'Internship duration is not set for this intern']);
            exit;
        }

        if ($intern['tech_id'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Technology is not set for this intern']);
            exit;
        }

        $posted_week = (int)($_POST['week_number'] ?? 0);
        if ($posted_week > 0) {
            $week_num = $posted_week;
        } else {
            // Calculate intern's actual target week dynamically
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

            $completed_weeks = max($max_w, $cnt);
            $week_num = $completed_weeks + 1;
        }

        // Fetch specified week task from curriculum
        $cur_stmt = $conn->prepare("SELECT title, description FROM curriculum_tasks WHERE tech_id = ? AND duration = ? AND week_number = ?");
        $cur_stmt->bind_param('isi', $intern['tech_id'], $intern['internship_duration'], $week_num);
        $cur_stmt->execute();
        $cur_task = $cur_stmt->get_result()->fetch_assoc();
        $cur_stmt->close();

        if (!$cur_task) {
            echo json_encode(['success' => false, 'message' => "No Week {$week_num} task defined in curriculum for this technology and duration"]);
            exit;
        }

        // Check if they already have an active task
        $task_check = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND status IN ('inprogress', 'needs_improvement')");
        $task_check->bind_param('i', $intern_id);
        $task_check->execute();
        if ($task_check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Intern already has an active task assigned']);
            $task_check->close();
            exit;
        }
        $task_check->close();

        // Check if they already have this specific week task assigned
        $cur_check = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND week_number = ? AND is_curriculum_task = 1");
        $cur_check->bind_param('ii', $intern_id, $week_num);
        $cur_check->execute();
        if ($cur_check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => "Week {$week_num} task is already assigned to this intern."]);
            $cur_check->close();
            exit;
        }
        $cur_check->close();

        // Insert new task with supervisor as created_by
        $due_date = date('Y-m-d', strtotime('+7 days'));
        $created_by = (!empty($intern['supervisor_id']) && $intern['supervisor_id'] > 0) ? (int)$intern['supervisor_id'] : (($_SESSION['user_role'] == 3) ? $_SESSION['user_id'] : 1);
        $status = 'inprogress';
        $is_curriculum = 1;

        $ins_stmt = $conn->prepare("INSERT INTO tasks (title, description, assign_to, created_by, status, due_date, week_number, is_curriculum_task, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $ins_stmt->bind_param('ssisssii', $cur_task['title'], $cur_task['description'], $intern_id, $created_by, $status, $due_date, $week_num, $is_curriculum);
        $success = $ins_stmt->execute();
        $ins_stmt->close();

        if ($success) {
            // Send email
            if (!empty($intern['email'])) {
                $subject = "Curriculum Task Assigned: Week {$week_num}";
                $html_content = "
                    <div style='font-family: Arial, sans-serif; color: #333;'>
                        <h2>Assalam O Alaikum " . htmlspecialchars($intern['name']) . ",</h2>
                        <p>You have been assigned your <strong>Week {$week_num} Curriculum Task: " . htmlspecialchars($cur_task['title']) . "</strong></p>
                        <p><strong>Due Date:</strong> " . htmlspecialchars(date('j F Y', strtotime($due_date))) . "</p>
                        <p>Please log in to your dashboard to start working on it.</p>
                        <br>
                        <p>Best Regards,<br>Management Team</p>
                    </div>
                ";
                sendNotificationFallback([
                    'email' => $intern['email'],
                    'name' => $intern['name'],
                    'subject' => $subject,
                    'html_content' => $html_content
                ]);
            }

            echo json_encode(['success' => true, 'message' => "Curriculum task for Week {$week_num} assigned successfully!"]);
        } else {
            echo json_encode(['success' => false, 'message' => "Failed to assign Week {$week_num} task"]);
        }
        break;

    case 'import_json':
        if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $tech_id = (int)($_POST['tech_id'] ?? 0);
        $duration = $_POST['duration'] ?? '';

        if ($tech_id <= 0 || empty($duration)) {
            echo json_encode(['success' => false, 'message' => 'Technology and Duration are required.']);
            exit;
        }

        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please upload a valid JSON file.']);
            exit;
        }

        $file_tmp = $_FILES['json_file']['tmp_name'];
        $json_content = file_get_contents($file_tmp);
        $json_data = json_decode($json_content, true);

        if (!$json_data || !is_array($json_data)) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON structure. Must be an array of tasks.']);
            exit;
        }

        // Validate max weeks based on duration
        $max_weeks = ($duration === '4 weeks') ? 4 : (($duration === '8 weeks') ? 8 : 12);

        $conn->begin_transaction();
        try {
            // Delete existing curriculum for this tech & duration first to refresh
            $del_stmt = $conn->prepare("DELETE FROM curriculum_tasks WHERE tech_id = ? AND duration = ?");
            $del_stmt->bind_param('is', $tech_id, $duration);
            $del_stmt->execute();
            $del_stmt->close();

            $ins_stmt = $conn->prepare("INSERT INTO curriculum_tasks (tech_id, duration, week_number, title, description) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($json_data as $item) {
                $week = (int)($item['week_number'] ?? 0);
                $title = trim($item['title'] ?? '');
                $desc = trim($item['description'] ?? '');

                if ($week <= 0 || empty($title) || empty($desc)) {
                    throw new Exception("Validation error: 'week_number', 'title', and 'description' are required for all weeks.");
                }

                if ($week > $max_weeks) {
                    throw new Exception("Validation error: Week {$week} exceeds the selected duration of {$duration}.");
                }

                $ins_stmt->bind_param('isiss', $tech_id, $duration, $week, $title, $desc);
                $ins_stmt->execute();
            }
            $ins_stmt->close();
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Curriculum imported successfully!']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
