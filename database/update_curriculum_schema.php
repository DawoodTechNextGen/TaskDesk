<?php
require_once __DIR__ . '/../include/connection.php';

echo "Starting Database Migration...\n";

// 1. Create curriculum_tasks table
$sql_create_table = "
CREATE TABLE IF NOT EXISTS `curriculum_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tech_id` INT NOT NULL,
  `duration` ENUM('4 weeks', '8 weeks', '12 weeks') NOT NULL,
  `week_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_curriculum_week` (`tech_id`, `duration`, `week_number`),
  FOREIGN KEY (`tech_id`) REFERENCES `technologies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($sql_create_table)) {
    echo "Table 'curriculum_tasks' created or already exists.\n";
} else {
    echo "Error creating table 'curriculum_tasks': " . $conn->error . "\n";
    exit(1);
}

// 2. Add columns to tasks table if they do not exist
$result = $conn->query("SHOW COLUMNS FROM `tasks` LIKE 'week_number'");
if ($result->num_rows == 0) {
    $sql_alter = "ALTER TABLE `tasks` ADD COLUMN `week_number` INT DEFAULT NULL AFTER `reviewed_by`";
    if ($conn->query($sql_alter)) {
        echo "Column 'week_number' added to 'tasks' table.\n";
    } else {
        echo "Error adding column 'week_number': " . $conn->error . "\n";
    }
} else {
    echo "Column 'week_number' already exists in 'tasks' table.\n";
}

$result2 = $conn->query("SHOW COLUMNS FROM `tasks` LIKE 'is_curriculum_task'");
if ($result2->num_rows == 0) {
    $sql_alter2 = "ALTER TABLE `tasks` ADD COLUMN `is_curriculum_task` TINYINT(1) DEFAULT 0 AFTER `week_number`";
    if ($conn->query($sql_alter2)) {
        echo "Column 'is_curriculum_task' added to 'tasks' table.\n";
    } else {
        echo "Error adding column 'is_curriculum_task': " . $conn->error . "\n";
    }
} else {
    echo "Column 'is_curriculum_task' already exists in 'tasks' table.\n";
}

echo "Database Migration Completed Successfully!\n";
?>
