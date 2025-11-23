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
?>