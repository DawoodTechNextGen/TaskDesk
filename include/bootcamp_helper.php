<?php
// `bootcamp_registrations` holds the public Bootcamp sign-up form's submissions.
// It is a completely separate data source from the internship `registrations`
// table - nothing in the bootcamp module reads or writes that table.
//
// The sign-up form owns its own database (BOOTCAMP_DB in include/config.php),
// which lives on the same MySQL server as this app, so every bootcamp query
// reaches it by fully qualifying the table on the existing $conn. Use this
// constant instead of writing the table name out by hand.
define('BOOTCAMP_TABLE', '`' . BOOTCAMP_DB . '`.`bootcamp_registrations`');
// The bootcamps themselves (title, seats, schedule...), created from bootcamps.php
// and listed on the public site's /bootcamps page. Same database as the sign-up
// rows, so the public form's Node.js API reads it too.
define('BOOTCAMPS_TABLE', '`' . BOOTCAMP_DB . '`.`bootcamps`');

if (isset($conn) && $conn instanceof mysqli) {
    // Best-effort self-migration, same idea as freeze_logs in
    // include/internship_helper.php: create the table and add the two columns
    // this module needs if they're missing yet.
    //
    // Wrapped in try/catch because the DB user TaskDesk connects with often has
    // only SELECT/INSERT/UPDATE on the Bootcamp form's database (it's not this
    // app's own database), not CREATE/ALTER - and PHP 8.1+ mysqli throws on a
    // denied query instead of just returning false. On an environment where
    // that's the case, the table and its status/email_status columns are
    // assumed to already exist (see database/bootcamp_registrations.sql and
    // database/bootcamp_add_status_columns.sql for the SQL to run manually
    // once) and this whole block quietly does nothing instead of taking the
    // page down.
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS " . BOOTCAMP_TABLE . " (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(50) NOT NULL,
          `email` VARCHAR(100) NOT NULL,
          `mbl_number` VARCHAR(20) NOT NULL,
          `province` VARCHAR(50) NOT NULL,
          `city` VARCHAR(50) NOT NULL,
          `cnic` VARCHAR(15) NOT NULL,
          `status` ENUM('new','contact','enrolled','rejected') NOT NULL DEFAULT 'new',
          `email_status` TINYINT NOT NULL DEFAULT 0,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `uq_bootcamp_email` (`email`),
          UNIQUE KEY `uq_bootcamp_mbl_number` (`mbl_number`),
          UNIQUE KEY `uq_bootcamp_cnic` (`cnic`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // `status` tracks the enrollment through new -> contact -> enrolled / rejected,
        // and `email_status` records how the contact was made (mirroring the same
        // column on `registrations`: 0 = none, 1 = email sent, 2 = email failed,
        // 3 = contacted on WhatsApp). The sign-up form only writes the row itself, so
        // on an environment where the table already existed these columns are added
        // here. Both are additive with defaults, so the form's INSERT keeps working.
        $hasStatus = $conn->query("SHOW COLUMNS FROM " . BOOTCAMP_TABLE . " LIKE 'status'");
        if ($hasStatus && $hasStatus->num_rows === 0) {
            $conn->query("ALTER TABLE " . BOOTCAMP_TABLE . "
                ADD COLUMN `status` ENUM('new','contact','enrolled','rejected') NOT NULL DEFAULT 'new' AFTER `cnic`");
        }

        $hasEmailStatus = $conn->query("SHOW COLUMNS FROM " . BOOTCAMP_TABLE . " LIKE 'email_status'");
        if ($hasEmailStatus && $hasEmailStatus->num_rows === 0) {
            $conn->query("ALTER TABLE " . BOOTCAMP_TABLE . "
                ADD COLUMN `email_status` TINYINT NOT NULL DEFAULT 0 AFTER `status`");
        }
    } catch (\Throwable $e) {
        error_log('Bootcamp self-migration skipped (likely missing CREATE/ALTER privilege on ' . BOOTCAMP_DB . '): ' . $e->getMessage());
    }

    // Multiple bootcamps: the `bootcamps` table, and a bootcamp_id on every
    // sign-up row. The public form's Node.js API runs the same idempotent
    // migration on boot (backend/config/ensureBootcampColumns.js), so whichever
    // app is deployed first sets it up. Rows that existed before this have a
    // NULL bootcamp_id and show as "Unassigned".
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS " . BOOTCAMPS_TABLE . " (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `slug` VARCHAR(120) NOT NULL,
          `title` VARCHAR(150) NOT NULL,
          `description` TEXT NULL,
          `highlights` TEXT NULL,
          `mode` ENUM('online','onsite','hybrid') NOT NULL DEFAULT 'online',
          `status` ENUM('draft','upcoming','open','closed','completed') NOT NULL DEFAULT 'draft',
          `total_seats` INT UNSIGNED NOT NULL DEFAULT 0,
          `fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
          `duration` VARCHAR(60) NULL,
          `schedule` VARCHAR(150) NULL,
          `start_date` DATE NULL,
          `end_date` DATE NULL,
          `registration_start` DATETIME NULL,
          `registration_end` DATETIME NULL,
          `venue` VARCHAR(150) NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `uniq_bootcamps_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $hasBootcampId = $conn->query("SHOW COLUMNS FROM " . BOOTCAMP_TABLE . " LIKE 'bootcamp_id'");
        if ($hasBootcampId && $hasBootcampId->num_rows === 0) {
            $conn->query("ALTER TABLE " . BOOTCAMP_TABLE . "
                ADD COLUMN `bootcamp_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
                ADD INDEX `idx_bootcamp_reg_bootcamp` (`bootcamp_id`)");
        }

        // Email / WhatsApp / CNIC used to be unique across the whole table, which
        // would stop a student from joining a second bootcamp. They become unique
        // per bootcamp instead. The single-column keys were named uq_* when this
        // file created the table and uniq_* when the Node.js API did, so both are
        // looked up by the column they cover rather than by name. Each composite
        // key has two index rows, so 6 rows means this already ran.
        $done = $conn->query("SHOW INDEX FROM " . BOOTCAMP_TABLE . " WHERE Key_name IN ('uniq_bootcamp_reg_email', 'uniq_bootcamp_reg_mbl_number', 'uniq_bootcamp_reg_cnic')");
        $perBootcampColumns = ($done && $done->num_rows >= 6) ? [] : ['email', 'mbl_number', 'cnic'];
        foreach ($perBootcampColumns as $col) {
            $keys = $conn->query("SHOW INDEX FROM " . BOOTCAMP_TABLE . " WHERE Non_unique = 0 AND Column_name = '$col'");
            $indexColumns = [];
            while ($keys && ($k = $keys->fetch_assoc())) {
                $indexColumns[$k['Key_name']] = true;
            }
            foreach (array_keys($indexColumns) as $keyName) {
                $parts = $conn->query("SHOW INDEX FROM " . BOOTCAMP_TABLE . " WHERE Key_name = '" . $conn->real_escape_string($keyName) . "'");
                if ($parts && $parts->num_rows === 1) {
                    $conn->query("ALTER TABLE " . BOOTCAMP_TABLE . " DROP INDEX `" . str_replace('`', '', $keyName) . "`");
                }
            }
            $composite = "uniq_bootcamp_reg_$col";
            $exists = $conn->query("SHOW INDEX FROM " . BOOTCAMP_TABLE . " WHERE Key_name = '$composite'");
            if ($exists && $exists->num_rows === 0) {
                $conn->query("ALTER TABLE " . BOOTCAMP_TABLE . " ADD UNIQUE KEY `$composite` (`bootcamp_id`, `$col`)");
            }
        }
    } catch (\Throwable $e) {
        error_log('Bootcamps self-migration skipped (likely missing CREATE/ALTER privilege on ' . BOOTCAMP_DB . '): ' . $e->getMessage());
    }

    // Completion + certificates: an enrolled student who finishes is marked
    // `completed`, which emails them a bootcamp certificate. certificate_code is
    // what the certificate's QR / verify link points at, certificate_status
    // records the email (0 = not sent, 1 = sent, 2 = failed).
    try {
        $hasCertCode = $conn->query("SHOW COLUMNS FROM " . BOOTCAMP_TABLE . " LIKE 'certificate_code'");
        if ($hasCertCode && $hasCertCode->num_rows === 0) {
            $conn->query("ALTER TABLE " . BOOTCAMP_TABLE . "
                MODIFY `status` ENUM('new','contact','enrolled','completed','rejected') NOT NULL DEFAULT 'new',
                ADD COLUMN `completed_at` DATETIME NULL DEFAULT NULL AFTER `email_status`,
                ADD COLUMN `certificate_code` VARCHAR(20) NULL DEFAULT NULL AFTER `completed_at`,
                ADD COLUMN `certificate_status` TINYINT NOT NULL DEFAULT 0 AFTER `certificate_code`,
                ADD COLUMN `certificate_sent_at` DATETIME NULL DEFAULT NULL AFTER `certificate_status`,
                ADD UNIQUE KEY `uniq_bootcamp_reg_certificate_code` (`certificate_code`)");
        }
    } catch (\Throwable $e) {
        error_log('Bootcamp certificate self-migration skipped (likely missing ALTER privilege on ' . BOOTCAMP_DB . '): ' . $e->getMessage());
    }
}

// The stages an enrollment moves through, in order. `completed` comes after
// `enrolled` and is the only one that issues a certificate.
if (!function_exists('bootcampEnrollmentStatuses')) {
    function bootcampEnrollmentStatuses()
    {
        return ['new', 'contact', 'enrolled', 'completed', 'rejected'];
    }
}

if (!function_exists('bootcampStatusLabels')) {
    function bootcampStatusLabels()
    {
        return [
            'draft'     => 'Draft',
            'upcoming'  => 'Upcoming',
            'open'      => 'Open',
            'closed'    => 'Closed',
            'completed' => 'Completed',
        ];
    }
}

if (!function_exists('bootcampModeLabels')) {
    function bootcampModeLabels()
    {
        return [
            'online' => 'Online',
            'onsite' => 'Onsite',
            'hybrid' => 'Hybrid',
        ];
    }
}

// Lowercase letters/numbers with single hyphens - the slug is the public URL
// (/bootcamps/<slug>), so it has to stay URL-safe and unique.
if (!function_exists('isValidBootcampSlug')) {
    function isValidBootcampSlug($slug)
    {
        return is_string($slug) && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1;
    }
}

// Sign-ups that hold a seat: everything except rejected ones. Used by the
// bootcamps list here and mirrored by the public API's seat check.
if (!function_exists('bootcampSeatsFilledSql')) {
    function bootcampSeatsFilledSql($bootcampIdExpr)
    {
        return "(SELECT COUNT(*) FROM " . BOOTCAMP_TABLE . " r WHERE r.bootcamp_id = $bootcampIdExpr AND r.status <> 'rejected')";
    }
}
