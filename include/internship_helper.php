<?php
// freeze_logs is read by several of the functions below (getUserFreezeDays,
// getInternshipCompletionDate, getWorkingDaysExcludingFreeze). Create it here,
// once, so it's guaranteed to exist regardless of whether controller/freeze.php
// has ever run yet on this environment — otherwise the first query against a
// missing table throws (PHP 8.1+ mysqli default) and breaks every caller,
// including the admin/supervisor dashboard attendance stats.
if (isset($conn) && $conn instanceof mysqli) {
    $conn->query("CREATE TABLE IF NOT EXISTS `freeze_logs` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `start_date` DATE NOT NULL,
      `end_date` DATE NOT NULL,
      `days` INT NOT NULL,
      `reason` TEXT DEFAULT NULL,
      `approved_by` INT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY `idx_freeze_logs_user` (`user_id`),
      CONSTRAINT `fk_freeze_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_freeze_logs_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

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

if (!function_exists('countWeekdaysBetween')) {
    // Mon-Fri day count between two DateTime bounds (inclusive)
    function countWeekdaysBetween($startDate, $endDate) {
        if ($startDate > $endDate) return 0;
        $count = 0;
        $current = clone $startDate;
        while ($current <= $endDate) {
            if ((int)$current->format('N') < 6) { // Excludes Sat(6) and Sun(7)
                $count++;
            }
            $current->modify('+1 day');
        }
        return $count;
    }
}

if (!function_exists('subtractFreezeWeekdays')) {
    // Subtract, from an already-computed weekday count over [$startDate, $endDate], the
    // weekdays that fall inside any of the given freeze periods (rows with start_date/end_date).
    function subtractFreezeWeekdays($workingDays, array $freezePeriods, $startDate, $endDate) {
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');
        foreach ($freezePeriods as $period) {
            if ($period['start_date'] > $endStr || $period['end_date'] < $startStr) continue;
            $fStart = new DateTime(max($period['start_date'], $startStr));
            $fEnd = new DateTime(min($period['end_date'], $endStr));
            $workingDays -= countWeekdaysBetween($fStart, $fEnd);
        }
        return max(0, $workingDays);
    }
}

if (!function_exists('getWorkingDaysExcludingFreeze')) {
    // Weekday count between two dates, excluding any days that fall inside an approved freeze period for this user
    function getWorkingDaysExcludingFreeze($conn, $user_id, $startDate, $endDate) {
        if ($startDate > $endDate) return 0;

        $workingDays = countWeekdaysBetween($startDate, $endDate);

        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        $freezePeriods = [];
        $stmt = $conn->prepare("SELECT start_date, end_date FROM freeze_logs WHERE user_id = ? AND start_date <= ? AND end_date >= ?");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $endStr, $startStr);
            $stmt->execute();
            $freezePeriods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        return subtractFreezeWeekdays($workingDays, $freezePeriods, $startDate, $endDate);
    }
}

if (!function_exists('getInternAttendanceSummaries')) {
    // Batch version of getInternAttendanceSummary(): computes freeze-aware attendance stats
    // for many interns using a fixed number of queries (not one round-trip per intern), so
    // admin/supervisor list views don't pay an N+1 cost as the intern count grows.
    // $users: array of ['id'=>int, 'created_at'=>DateTime (midnight), 'internship_type'=>?, 'internship_duration'=>?]
    // Returns: [user_id => summary array] (same shape as getInternAttendanceSummary())
    function getInternAttendanceSummaries($conn, array $users) {
        if (empty($users)) return [];

        $ids = array_values(array_unique(array_map(fn($u) => (int)$u['id'], $users)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $freezeDaysByUser = [];
        $freezePeriodsByUser = [];
        $stmt = $conn->prepare("SELECT user_id, start_date, end_date, days FROM freeze_logs WHERE user_id IN ($placeholders)");
        if ($stmt) {
            $stmt->execute($ids);
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $uid = (int)$row['user_id'];
                $freezeDaysByUser[$uid] = ($freezeDaysByUser[$uid] ?? 0) + (int)$row['days'];
                $freezePeriodsByUser[$uid][] = $row;
            }
            $stmt->close();
        }

        $presentDaysByUser = [];
        $stmt = $conn->prepare("
            SELECT user_id, COUNT(DISTINCT date) as present_days FROM (
                SELECT user_id, DATE(date) as date FROM attendance WHERE total_work_seconds >= 10800 AND user_id IN ($placeholders)
                UNION
                SELECT assign_to as user_id, DATE(completed_at) as date FROM tasks WHERE status = 'complete' AND assign_to IN ($placeholders)
            ) as combined_attendance
            GROUP BY user_id
        ");
        if ($stmt) {
            $stmt->execute(array_merge($ids, $ids));
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $presentDaysByUser[(int)$row['user_id']] = (int)$row['present_days'];
            }
            $stmt->close();
        }

        $now = new DateTime();
        $now->setTime(0, 0, 0);

        $summaries = [];
        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $created_at = $u['created_at'];

            $weeks = getInternshipTotalWeeks($u['internship_duration'] ?? '', $u['internship_type'] ?? null);
            $freeze_days = $freezeDaysByUser[$uid] ?? 0;

            $completion_date = clone $created_at;
            $completion_date->modify("+{$weeks} weeks");
            if ($freeze_days > 0) {
                $completion_date->modify("+{$freeze_days} days");
            }

            $calc_end_date = min($now, $completion_date);
            $total_days = $created_at <= $calc_end_date
                ? subtractFreezeWeekdays(
                    countWeekdaysBetween($created_at, $calc_end_date),
                    $freezePeriodsByUser[$uid] ?? [],
                    $created_at,
                    $calc_end_date
                )
                : 0;

            $present_days = $presentDaysByUser[$uid] ?? 0;

            $summaries[$uid] = [
                'completion_date' => $completion_date,
                'is_completed' => $now > $completion_date,
                'present_days' => $present_days,
                'total_days' => $total_days,
                'attendance_percentage' => $total_days > 0 ? round(($present_days / $total_days) * 100) : 0,
            ];
        }

        return $summaries;
    }
}

if (!function_exists('getInternAttendanceSummary')) {
    // Single-intern convenience wrapper around getInternAttendanceSummaries(), shared by the
    // intern's own dashboard and every admin/supervisor view, so a completed intern's
    // freeze-adjusted completion date and working-day count never disagree between the two sides.
    function getInternAttendanceSummary($conn, $user_id, $created_at, $internship_type = null, $internship_duration = null) {
        if ($internship_type === null && $internship_duration === null) {
            $stmt = $conn->prepare("SELECT internship_type, internship_duration FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $u = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $internship_type = $u['internship_type'] ?? null;
                $internship_duration = $u['internship_duration'] ?? null;
            }
        }

        $summaries = getInternAttendanceSummaries($conn, [[
            'id' => $user_id,
            'created_at' => $created_at,
            'internship_type' => $internship_type,
            'internship_duration' => $internship_duration,
        ]]);

        return $summaries[(int)$user_id];
    }
}
