<?php
if (!function_exists('getInternshipTotalWeeks')) {
    function getInternshipTotalWeeks($duration_str, $internship_type = null) {
        if (!empty($duration_str)) {
            if (strpos($duration_str, '4') !== false) return 4;
            if (strpos($duration_str, '8') !== false) return 8;
            if (strpos($duration_str, '12') !== false) return 12;
        }
        return ($internship_type == 0) ? 4 : 12;
    }
}

if (!function_exists('isInternshipDurationCompleted')) {
    function isInternshipDurationCompleted($conn, $user_id) {
        if (!$user_id || $user_id <= 0) return false;

        $stmt = $conn->prepare("SELECT internship_duration, internship_type FROM users WHERE id = ? AND user_role = 2");
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) return false;

        $total_weeks = getInternshipTotalWeeks($user['internship_duration'] ?? '', $user['internship_type'] ?? null);

        // Check 1: Max approved curriculum week number
        $max_w_stmt = $conn->prepare("SELECT MAX(week_number) as max_w FROM tasks WHERE assign_to = ? AND week_number > 0 AND status IN ('complete', 'approved')");
        if ($max_w_stmt) {
            $max_w_stmt->bind_param("i", $user_id);
            $max_w_stmt->execute();
            $max_res = $max_w_stmt->get_result()->fetch_assoc();
            $max_w = (int)($max_res['max_w'] ?? 0);
            $max_w_stmt->close();

            if ($max_w >= $total_weeks) {
                return true;
            }
        }

        // Check 2: Total count of completed/approved tasks
        $cnt_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tasks WHERE assign_to = ? AND status IN ('complete', 'approved')");
        if ($cnt_stmt) {
            $cnt_stmt->bind_param("i", $user_id);
            $cnt_stmt->execute();
            $cnt_res = $cnt_stmt->get_result()->fetch_assoc();
            $cnt = (int)($cnt_res['cnt'] ?? 0);
            $cnt_stmt->close();

            if ($cnt >= $total_weeks) {
                return true;
            }
        }

        return false;
    }
}
