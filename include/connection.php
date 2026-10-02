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

// An Intern can also be a Campus Ambassador on the same account (users.referral_code
// set + amb_active = 1). Re-read on every request so an Admin switching it off
// hides the referral pages on the intern's next page load.
if (isset($_SESSION['user_id']) && (int)($_SESSION['user_role'] ?? 0) === ROLE_INTERN) {
    $_SESSION['is_ambassador'] = false;
    try {
        $ambStmt = $conn->prepare("SELECT 1 FROM users WHERE id = ? AND referral_code IS NOT NULL AND amb_active = 1 LIMIT 1");
        $ambId = (int)$_SESSION['user_id'];
        $ambStmt->bind_param('i', $ambId);
        $ambStmt->execute();
        $_SESSION['is_ambassador'] = $ambStmt->get_result()->num_rows > 0;
        $ambStmt->close();
    } catch (Throwable $e) {
        // amb_active not added yet (ensureInternshipTypeSchema hasn't run).
    }
}

// An Assessment candidate account (see controller/registrations.php's
// send_assessment action) may only ever reach its own assessment - never the
// dashboard, sidebar, or any other module/controller. Checked here because
// this file is included by every page and every controller after
// session_start(), so this is the one place that has to enforce it.
if (isset($_SESSION['user_id']) && (int)($_SESSION['user_role'] ?? 0) === ROLE_CANDIDATE) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $file = basename($scriptName);
    $allowedForCandidate = ['my_assessment.php', 'candidate_assessment.php', 'logout.php', 'auth.php'];
    if (!in_array($file, $allowedForCandidate, true)) {
        if (strpos($scriptName, '/controller/') !== false) {
            denyJson('Access restricted to your assessment.');
        }
        header('Location: ' . BASE_URL . 'my_assessment.php');
        exit;
    }
}

// A Campus Ambassador (see include/ambassador_helper.php) can only see their own
// referral dashboard. Same whitelist idea as the Candidate lock above. The
// account's status is re-checked on every request so an Admin deactivating an
// ambassador signs them out straight away.
if (isset($_SESSION['user_id']) && (int)($_SESSION['user_role'] ?? 0) === ROLE_AMBASSADOR) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $file = basename($scriptName);

    $ambStmt = $conn->prepare("SELECT status FROM users WHERE id = ? AND user_role = ? LIMIT 1");
    $ambId = (int)$_SESSION['user_id'];
    $ambRole = ROLE_AMBASSADOR;
    $ambStmt->bind_param('ii', $ambId, $ambRole);
    $ambStmt->execute();
    $ambRow = $ambStmt->get_result()->fetch_assoc();
    $ambStmt->close();
    if (!$ambRow || (int)$ambRow['status'] !== 1) {
        session_unset();
        session_destroy();
        if (strpos($scriptName, '/controller/') !== false) {
            denyJson('Your ambassador account is inactive.');
        }
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $allowedForAmbassador = ['ambassador_dashboard.php', 'ambassador_registrations.php', 'ambassador.php', 'logout.php', 'auth.php'];
    if (!in_array($file, $allowedForAmbassador, true)) {
        if (strpos($scriptName, '/controller/') !== false) {
            denyJson('Access restricted to your ambassador dashboard.');
        }
        header('Location: ' . BASE_URL . 'ambassador_dashboard.php');
        exit;
    }
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