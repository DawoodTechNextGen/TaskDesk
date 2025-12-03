<?php
// config.php
// Load environment variables from .env file

function loadEnvironmentVariables($filePath = '.env') {
    if (!file_exists($filePath)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Split key and value
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Set environment variable (if not already set)
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load environment variables
try {
    loadEnvironmentVariables(__DIR__ . '/../.env');

} catch (Exception $e) {
    error_log('Config Error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Configuration error"]);
    exit;
}

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'task_management');
// Smtp
define('MAIL_HOST', getenv('MAIL_HOST'));
define('MAIL_PORT', getenv('MAIL_PORT'));
define('MAIL_USERNAME', getenv('MAIL_USERNAME'));
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD'));
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION'));
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME'));

// Application Configuration
define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'UTC');

// Security
define('SECRET_KEY', getenv('SECRET_KEY') ?: 'default-secret-key-change-in-production');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/task-management');
// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting based on debug mode
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>