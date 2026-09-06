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
}
