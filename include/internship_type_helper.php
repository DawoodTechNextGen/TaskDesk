<?php
// Internship types offered on the public registration form (Task Base = 0,
// Learning Base = 1). An Admin turns each one on or off and edits its
// "Includes" list from internship_types.php. The Node.js registration site
// reads the same `internship_types` table, so a change here shows up on the
// form on its next load.
//
// When no type is enabled the form hides the field, new registrations are
// saved with internship_type = NULL, and the registration pipeline pages hide
// the Internship Type column (see showInternshipTypeColumn()).
//
// Also adds the referral columns used by the Campus Ambassador module
// (registrations.ref_code, users.referral_code, users.university) and the
// universities list (include/university_helper.php), since these features all
// change the tables the registration site writes to.
//
// Types 0 and 1 are built in. An Admin can add more (2, 3, ...); those only
// apply at the registration stage - hiring still sets users.internship_type to
// 0/1 from the chosen duration (see controller/registrations.php).
require_once __DIR__ . '/university_helper.php';

if (!function_exists('ensureInternshipTypeSchema')) {
    function ensureInternshipTypeSchema($conn)
    {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        $done = true;

        try {
            $conn->query("CREATE TABLE IF NOT EXISTS `internship_types` (
              `type_value` TINYINT NOT NULL PRIMARY KEY,
              `label` VARCHAR(100) NOT NULL,
              `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
              `includes_title` VARCHAR(150) NOT NULL DEFAULT '',
              `includes_items` TEXT NULL,
              `sort_order` INT NOT NULL DEFAULT 0,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Seeded with the lists that used to be hardcoded in the React form.
            // INSERT IGNORE so an Admin's later edits are never overwritten.
            $taskItems = implode("\n", [
                'Beginner-Friendly Learning Program',
                'Practice-Based Tasks & Assignments',
                'Platform & Training Fee: PKR 1000',
                'Beginner-Level Real-World Projects',
                'Weekly Tasks & Progress Tracking',
                '6-Week Internship Completion Certificate',
                'Community Support & Mentor Guidance',
                'Internship Access & Onboarding Support',
            ]);
            $learningItems = implode("\n", [
                'Structured Learning-Based Program',
                'Affordable Learning & Training Fee',
                'Real-World Projects',
                'Portfolio & GitHub Setup',
                'Mentor Support & Guidance',
                'Job & Freelancing Guidance',
                'Verified Internship Certificate',
                'Top Performers get Recommendation Letter',
            ]);
            $seed = $conn->prepare("INSERT IGNORE INTO `internship_types`
                (type_value, label, is_enabled, includes_title, includes_items, sort_order) VALUES
                (0, 'Task-Based Internship', 1, 'Task-Based Internship Includes:', ?, 1),
                (1, 'Learning-Based Internship', 1, 'Learning-Based Internship Includes:', ?, 2)");
            $seed->bind_param('ss', $taskItems, $learningItems);
            $seed->execute();
            $seed->close();

            // NULL = "no type chosen", used when every type is switched off.
            $col = $conn->query("SHOW COLUMNS FROM `registrations` LIKE 'internship_type'");
            if ($col && ($row = $col->fetch_assoc()) && strtoupper($row['Null']) === 'NO') {
                $conn->query("ALTER TABLE `registrations` MODIFY `internship_type` INT(11) NULL DEFAULT NULL");
            }

            $col = $conn->query("SHOW COLUMNS FROM `registrations` LIKE 'ref_code'");
            if ($col && $col->num_rows === 0) {
                $conn->query("ALTER TABLE `registrations` ADD COLUMN `ref_code` VARCHAR(20) NULL DEFAULT NULL, ADD INDEX `idx_registrations_ref_code` (`ref_code`)");
            }

            $col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'referral_code'");
            if ($col && $col->num_rows === 0) {
                $conn->query("ALTER TABLE `users` ADD COLUMN `referral_code` VARCHAR(20) NULL DEFAULT NULL, ADD UNIQUE KEY `uq_users_referral_code` (`referral_code`)");
            }

            $col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'university'");
            if ($col && $col->num_rows === 0) {
                $conn->query("ALTER TABLE `users` ADD COLUMN `university` VARCHAR(150) NULL DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            error_log('Internship type self-migration failed: ' . $e->getMessage());
        }

        ensureUniversitySchema($conn);
    }
}

// Adds a new internship type with the next free value (2, 3, ...).
// Returns the new type_value.
if (!function_exists('addInternshipType')) {
    function addInternshipType($conn, $label, $isEnabled, $includesTitle, $includesItems)
    {
        ensureInternshipTypeSchema($conn);
        $conn->begin_transaction();
        try {
            $row = $conn->query("SELECT COALESCE(MAX(type_value), 1) + 1 AS next_value, COALESCE(MAX(sort_order), 0) + 1 AS next_sort FROM internship_types FOR UPDATE")->fetch_assoc();
            $value = max(2, (int)$row['next_value']);
            $sort = (int)$row['next_sort'];
            $stmt = $conn->prepare("INSERT INTO internship_types (type_value, label, is_enabled, includes_title, includes_items, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isissi', $value, $label, $isEnabled, $includesTitle, $includesItems, $sort);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            return $value;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
}

// Every type, enabled or not, ordered for display. includes_items comes back as an array.
if (!function_exists('getInternshipTypes')) {
    function getInternshipTypes($conn)
    {
        ensureInternshipTypeSchema($conn);
        $types = [];
        $res = $conn->query("SELECT type_value, label, is_enabled, includes_title, includes_items
                             FROM internship_types ORDER BY sort_order, type_value");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['type_value'] = (int)$row['type_value'];
                $row['is_enabled'] = (int)$row['is_enabled'];
                $row['includes_items'] = array_values(array_filter(array_map('trim', explode("\n", (string)$row['includes_items'])), 'strlen'));
                $types[] = $row;
            }
        }
        return $types;
    }
}

if (!function_exists('getEnabledInternshipTypeValues')) {
    function getEnabledInternshipTypeValues($conn)
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            foreach (getInternshipTypes($conn) as $type) {
                if ($type['is_enabled']) {
                    $cache[] = $type['type_value'];
                }
            }
        }
        return $cache;
    }
}

// The Internship Type column/field is shown only while at least one type is enabled.
if (!function_exists('showInternshipTypeColumn')) {
    function showInternshipTypeColumn($conn)
    {
        return count(getEnabledInternshipTypeValues($conn)) > 0;
    }
}

if (!function_exists('internshipTypeLabel')) {
    function internshipTypeLabel($value)
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }
        if ((int)$value === 0) {
            return 'Task Base Intern';
        }
        if ((int)$value === 1) {
            return 'Learning Base Intern';
        }

        // Admin-added types: use their label from internship_types.
        static $labels = null;
        if ($labels === null) {
            global $conn;
            $labels = [];
            if ($conn instanceof mysqli) {
                foreach (getInternshipTypes($conn) as $type) {
                    $labels[$type['type_value']] = $type['label'];
                }
            }
        }
        return $labels[(int)$value] ?? 'Unknown';
    }
}

// Wording used in the "Interested in ..." WhatsApp message of candidate emails.
if (!function_exists('internshipTypeEmailLabel')) {
    function internshipTypeEmailLabel($value)
    {
        if ((int)$value === 0) {
            return 'Task Base Interns';
        }
        if ((int)$value === 1) {
            return 'Learning Base Interns';
        }
        return internshipTypeLabel($value);
    }
}
