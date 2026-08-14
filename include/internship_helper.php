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

if (!function_exists('getUserFreezeDays')) {
    // Total number of days a user's internship has been frozen for (across all approved freezes)
    function getUserFreezeDays($conn, $user_id) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(days), 0) as total FROM freeze_logs WHERE user_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['total'] ?? 0);
    }
}

if (!function_exists('getInternshipCompletionDate')) {
    // Internship end date = created_at + duration weeks + total approved freeze days
    function getInternshipCompletionDate($conn, $user_id) {
        $stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) return null;

        $weeks = getInternshipTotalWeeks($user['internship_duration'] ?? '', $user['internship_type'] ?? null);
        $freeze_days = getUserFreezeDays($conn, $user_id);

        $end_date = new DateTime($user['created_at']);
        $end_date->setTime(0, 0, 0);
        $end_date->modify("+{$weeks} weeks");
        if ($freeze_days > 0) {
            $end_date->modify("+{$freeze_days} days");
        }
        return $end_date;
    }
}

if (!function_exists('getWorkingDaysExcludingFreeze')) {
    // Weekday count between two dates, excluding any days that fall inside an approved freeze period for this user
    function getWorkingDaysExcludingFreeze($conn, $user_id, $startDate, $endDate) {
        if ($startDate > $endDate) return 0;

        $workingDays = 0;
        $current = clone $startDate;
        while ($current <= $endDate) {
            if ((int)$current->format('N') < 6) { // Excludes Sat(6) and Sun(7)
                $workingDays++;
            }
            $current->modify('+1 day');
        }

        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        $stmt = $conn->prepare("SELECT start_date, end_date FROM freeze_logs WHERE user_id = ? AND start_date <= ? AND end_date >= ?");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $endStr, $startStr);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $fStart = new DateTime(max($row['start_date'], $startStr));
                $fEnd = new DateTime(min($row['end_date'], $endStr));
                $cur = clone $fStart;
                while ($cur <= $fEnd) {
                    if ((int)$cur->format('N') < 6) {
                        $workingDays--;
                    }
                    $cur->modify('+1 day');
                }
            }
            $stmt->close();
        }

        return max(0, $workingDays);
    }
}
