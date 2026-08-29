<?php
// database.php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    public $conn;
    public $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->conn->connect_error) {
            $this->error = "Connection failed: " . $this->conn->connect_error;
            error_log($this->error);
            
            if (APP_DEBUG) {
                echo json_encode(["success" => false, "message" => "Database connection failed"]);
            } else {
                echo json_encode(["success" => false, "message" => "Service temporarily unavailable"]);
            }
            exit;
        }

        // Set charset to utf8
        $this->conn->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Create global database instance
$database = new Database();
$conn = $database->getConnection();

// module_permissions stores per-module read/write access for Collaborator (role 5)
// accounts (see include/permissions.php). Created lazily here so it exists
// regardless of which page runs first on a given environment.
$conn->query("CREATE TABLE IF NOT EXISTS `module_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `access` ENUM('read','write') NOT NULL DEFAULT 'read',
  UNIQUE KEY `user_module` (`user_id`, `module`),
  CONSTRAINT `fk_module_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Refreshed on every request (not just at login) so an Admin editing a
// Collaborator's permissions takes effect on that Collaborator's very next page
// load instead of requiring them to log out and back in.
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && (int)$_SESSION['user_role'] === ROLE_COLLABORATOR) {
    $_SESSION['module_permissions'] = getModulePermissionsForUser($conn, (int)$_SESSION['user_id']);
}

if (!function_exists('logActivity')) {
    function logActivity($action, $details) {
        global $conn;
        if (!isset($conn) || !$conn) {
            return false;
        }

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            $userId = 1; // Default to Admin/System
        }

        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $action, $details);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }
}
?>