<?php
// Universities offered in the public registration form's University dropdown.
// An Admin manages them from universities.php: add one at a time, bulk import
// a JSON list, and switch each one on/off. The Node.js registration backend
// reads the active ones (GET /api/universities); with none active the form
// hides the field. The student's choice is saved as text in
// registrations.university, so renaming or removing a university later never
// changes past registrations.

if (!function_exists('ensureUniversitySchema')) {
    function ensureUniversitySchema($conn)
    {
        static $done = false;
        if ($done || !($conn instanceof mysqli)) {
            return;
        }
        $done = true;

        try {
            $exists = $conn->query("SHOW TABLES LIKE 'universities'");
            if ($exists && $exists->num_rows === 0) {
                $conn->query("CREATE TABLE IF NOT EXISTS `universities` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `name` VARCHAR(191) NOT NULL,
                  `city` VARCHAR(100) NOT NULL DEFAULT '',
                  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  UNIQUE KEY `uq_universities_name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

                // Seeded once, when the table is first created, with Pakistan's
                // major universities. The same file doubles as an import example.
                $seed = json_decode((string)@file_get_contents(__DIR__ . '/../database/universities_pakistan.json'), true);
                if (is_array($seed)) {
                    importUniversities($conn, $seed);
                }
            }

            $col = $conn->query("SHOW COLUMNS FROM `registrations` LIKE 'university'");
            if ($col && $col->num_rows === 0) {
                $conn->query("ALTER TABLE `registrations` ADD COLUMN `university` VARCHAR(191) NULL DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            error_log('University self-migration failed: ' . $e->getMessage());
        }
    }
}

// Adds universities from a decoded JSON list. Each item is either a name
// string or {"name": ..., "city": ..., "is_active": true/false}. Names already
// on file are skipped (matched case-insensitively), never overwritten.
// Returns ['added' => n, 'skipped' => n, 'invalid' => n].
if (!function_exists('importUniversities')) {
    function importUniversities($conn, array $items)
    {
        $result = ['added' => 0, 'skipped' => 0, 'invalid' => 0];
        $stmt = $conn->prepare("INSERT IGNORE INTO universities (name, city, is_active) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            if (is_string($item)) {
                $item = ['name' => $item];
            }
            if (!is_array($item)) {
                $result['invalid']++;
                continue;
            }
            $name = trim(preg_replace('/\s+/', ' ', (string)($item['name'] ?? '')));
            $city = trim((string)($item['city'] ?? ''));
            $active = array_key_exists('is_active', $item) ? (int)(bool)$item['is_active'] : 1;
            if ($name === '' || mb_strlen($name) > 191 || mb_strlen($city) > 100) {
                $result['invalid']++;
                continue;
            }
            $stmt->bind_param('ssi', $name, $city, $active);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $result['added']++;
            } else {
                $result['skipped']++;
            }
        }
        $stmt->close();
        return $result;
    }
}

if (!function_exists('activeUniversityCount')) {
    function activeUniversityCount($conn)
    {
        ensureUniversitySchema($conn);
        $res = $conn->query("SELECT COUNT(*) AS total FROM universities WHERE is_active = 1");
        return $res ? (int)$res->fetch_assoc()['total'] : 0;
    }
}
