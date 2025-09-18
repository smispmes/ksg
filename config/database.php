<?php
/**
 * KSG SMI Performance System - Database Configuration
 * Kenya School of Government Security Management Institute
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'ksg_smi_performance';
    private $username = 'root'; // Change to your MySQL username
    private $password = '';     // Change to your MySQL password
    private $conn;
    
    // Database connection
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
    
    // Close connection
    public function closeConnection() {
        $this->conn = null;
    }
    
    // Test database connection
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                return array('status' => 'success', 'message' => 'Database connection successful');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => $e->getMessage());
        }
    }
    
    // Execute query with parameters
    public function executeQuery($query, $params = array()) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution error: " . $e->getMessage());
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    // Get single record
    public function fetchOne($query, $params = array()) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt->fetch();
    }
    
    // Get multiple records
    public function fetchAll($query, $params = array()) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt->fetchAll();
    }
    
    // Insert record and return last insert ID
    public function insert($query, $params = array()) {
        $this->executeQuery($query, $params);
        return $this->conn->lastInsertId();
    }
    
    // Update/Delete record and return affected rows
    public function execute($query, $params = array()) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt->rowCount();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->conn->rollback();
    }
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'ksg_smi_performance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application constants
define('ADMIN_INDEX_CODE', 'Richmond@524');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', array(
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'
));

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('REQUIRE_SPECIAL_CHAR', true);
define('REQUIRE_UPPERCASE', true);
define('REQUIRE_NUMBERS', true);

// Email settings (configure as needed)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@ksg.ac.ke');
define('FROM_NAME', 'KSG SMI Performance System');

// Application settings
define('APP_NAME', 'KSG SMI Performance System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Africa/Nairobi');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>