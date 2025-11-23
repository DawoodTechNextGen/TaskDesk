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
    $task_id = (int)$data['task_id'];
    $approval_status = $conn->real_escape_string(trim($data['approval_status']));
    $emails = $data['emails'];

    if (empty($task_id) || empty($approval_status)) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    if ($approval_status == 'add-approval') {
        if (empty($emails) || !is_array($emails)) {
            echo json_encode(["success" => false, "message" => "Kindly select the email(s) of persons you want approvals from"]);
            exit;
        } else {
            if (empty($emails)) {
                $stmt_update = $conn->prepare("UPDATE tasks SET approval_status = 0 where id =?");
                $stmt_update->bind_param('i', $task_id);
                $stmt_update->execute();
                $stmt_update->close();
                echo json_encode(["success" => true, "message" => "Updated Successfully"]);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO approvals (task_id, email, status) VALUES (?, ?, 0)");

            if (!$stmt) {
                echo json_encode(["success" => false, "message" => "DB prepare failed"]);
                exit;
            }

            $stmt->bind_param("is", $task_id, $email);

            foreach ($emails as $email) {
                $email = trim($email);
                if (!empty($email)) {
                    $stmt->execute();
                    $stmt_update = $conn->prepare("UPDATE tasks SET approval_status = 0 where id = ?");
                    $stmt_update->bind_param('i', $task_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
            }
        }
    } else {
        $stmt_update = $conn->prepare("UPDATE tasks SET approval_status = 1 where id =?");
        $stmt_update->bind_param('i', $task_id);
        $stmt_update->execute();
        $stmt_update->close();
        echo json_encode(["success" => true, "message" => "Updated Successfully"]);
        exit;
    }

    $stmt->close();

    echo json_encode(["success" => true, "message" => "Updated successfully"]);
}
if ($data['action'] === 'getApprovals') {
    $user_email = $_SESSION['user_email'];
    $stmt = $conn->prepare("SELECT a.status AS status, t.id, t.title, t.description,t.created_at, crb.name AS assign_by, ast.name AS assign_to FROM approvals a JOIN tasks t ON a.task_id = t.id JOIN users crb ON crb.id = t.created_by JOIN users ast ON ast.id = t.assign_to WHERE a.email = ?");
    $stmt->bind_param("s", $user_email);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        $Approvals = [];
        while ($row = $result->fetch_assoc()) {
            $Approvals[] = $row;
        }

        echo json_encode([
            "success" => true,
            "message" => "Approvals fetched successfully",
            "data"    => $Approvals
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch Approvals"
        ]);
    }

    $stmt->close();
}

if ($data['action'] === 'approve' || $data['action'] === 'decline') {
    $status = (int)$data['status'];
    $task_id = (int)$data['task_id'];
    $user_email = $_SESSION['user_email'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE approvals SET status = ? WHERE task_id = ? AND email = ?");
        $stmt->bind_param("iis", $status, $task_id, $user_email);
        $stmt->execute();
        $stmt->close();

        if ($status === 1) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM approvals WHERE task_id = ? AND status = 2");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $stmt->bind_result($decline_count);
            $stmt->fetch();
            $stmt->close();

            if ($decline_count == 0) {
                $stmt = $conn->prepare("UPDATE tasks SET approval_status = 1 WHERE id = ?");
                $stmt->bind_param("i", $task_id);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($status === 2) {
            $stmt = $conn->prepare("UPDATE tasks SET status = 3, approval_status = 2 WHERE id = ?");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt_update = $conn->prepare("UPDATE tasks SET notification = 0 WHERE id = ?");
        $stmt_update->bind_param("i", $task_id);
        $stmt_update->execute();
        $conn->commit();

        echo json_encode([
            "success" => true,
            "message" => ($status === 1 ? "Task approved successfully" : "Task declined successfully")
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            "success" => false,
            "message" => "Failed to update approvals: " . $e->getMessage()
        ]);
    }
}
