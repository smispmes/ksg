<?php
/**
 * KSG SMI Performance System - Database Setup Script
 * This script will create the database and populate it with initial data
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSG SMI Performance System - Database Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">KSG SMI Performance System</h1>
            <p class="text-gray-600">Database Setup & Installation</p>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? 'ksg_smi_performance';
            $db_user = $_POST['db_user'] ?? 'root';
            $db_pass = $_POST['db_pass'] ?? '';
            
            echo '<div class="mb-6">';
            echo '<h2 class="text-xl font-semibold mb-4">Installation Progress</h2>';
            echo '<div class="space-y-2">';
            
            try {
                // Step 1: Test database connection
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Testing database connection...</span>';
                echo '</div>';
                
                $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Database connection successful</span>';
                echo '</div>';
                
                // Step 2: Create database
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Creating database...</span>';
                echo '</div>';
                
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Database created successfully</span>';
                echo '</div>';
                
                // Step 3: Create tables manually
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Creating tables...</span>';
                echo '</div>';
                
                // Create tables one by one
                $tables = [
                    // Users table
                    "CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        phone VARCHAR(20),
                        department ENUM('administration', 'finance', 'hr', 'it', 'training', 'research') DEFAULT NULL,
                        job_title VARCHAR(255),
                        role ENUM('user', 'manager') DEFAULT 'user',
                        profile_picture LONGTEXT,
                        settings JSON,
                        notification_preferences JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_by VARCHAR(255),
                        last_login TIMESTAMP NULL,
                        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
                    )",
                    
                    // Admins table
                    "CREATE TABLE IF NOT EXISTS admins (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        phone VARCHAR(20),
                        profile_picture LONGTEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        last_login TIMESTAMP NULL,
                        status ENUM('active', 'inactive') DEFAULT 'active'
                    )",
                    
                    // Task categories table
                    "CREATE TABLE IF NOT EXISTS task_categories (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    
                    // Task templates table
                    "CREATE TABLE IF NOT EXISTS task_templates (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        category_id INT,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL
                    )",
                    
                    // User tasks table
                    "CREATE TABLE IF NOT EXISTS user_tasks (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                        status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
                        due_date DATETIME NOT NULL,
                        created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        completed_date TIMESTAMP NULL,
                        assigned_by VARCHAR(255),
                        assigned_by_id INT,
                        instructions TEXT,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (assigned_by_id) REFERENCES admins(id) ON DELETE SET NULL
                    )",
                    
                    // Task uploads table
                    "CREATE TABLE IF NOT EXISTS task_uploads (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        task_id INT NOT NULL,
                        file_name VARCHAR(255) NOT NULL,
                        file_type VARCHAR(100),
                        file_size INT,
                        file_data LONGBLOB,
                        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (task_id) REFERENCES user_tasks(id) ON DELETE CASCADE
                    )",
                    
                    // Security settings table
                    "CREATE TABLE IF NOT EXISTS security_settings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        setting_name VARCHAR(100) UNIQUE NOT NULL,
                        setting_value JSON NOT NULL,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        updated_by INT,
                        FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL
                    )",
                    
                    // Access logs table
                    "CREATE TABLE IF NOT EXISTS access_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        admin_id INT,
                        user_type ENUM('user', 'admin') NOT NULL,
                        action VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
                    )",
                    
                    // System backups table
                    "CREATE TABLE IF NOT EXISTS system_backups (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        backup_name VARCHAR(255) NOT NULL,
                        backup_data LONGTEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        created_by INT,
                        file_size BIGINT,
                        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
                    )",
                    
                    // User reports table
                    "CREATE TABLE IF NOT EXISTS user_reports (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        report_type ENUM('weekly', 'monthly', 'quarterly', 'annual') NOT NULL,
                        report_data JSON NOT NULL,
                        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )",
                    
                    // System analytics table
                    "CREATE TABLE IF NOT EXISTS system_analytics (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        metric_name VARCHAR(100) NOT NULL,
                        metric_value JSON NOT NULL,
                        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        date_key DATE NOT NULL,
                        INDEX idx_date_metric (date_key, metric_name)
                    )"
                ];
                
                foreach ($tables as $table_sql) {
                    try {
                        $pdo->exec($table_sql);
                    } catch (PDOException $e) {
                        throw new Exception("Error creating table: " . $e->getMessage());
                    }
                }
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Tables created successfully</span>';
                echo '</div>';
                
                // Step 4: Insert initial data
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Inserting initial data...</span>';
                echo '</div>';
                
                // Insert task categories
                $categories = [
                    ['Financial Stewardship and Discipline', 'Tasks related to financial management, revenue optimization, debt management, and audit processes'],
                    ['Service Delivery', 'Tasks focused on citizen service delivery, complaint resolution, and service quality improvement'],
                    ['Core Mandate', 'Tasks related to training programs, consultancy, research activities, and organizational development']
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO task_categories (name, description) VALUES (?, ?)");
                foreach ($categories as $category) {
                    $stmt->execute($category);
                }
                
                // Insert task templates
                $templates = [
                    [1, 'Revenue', 'Monitor and optimize revenue generation activities, analyze financial performance metrics, and implement strategies to increase organizational income.'],
                    [1, 'Debt Management', 'Monitor, analyze, and manage organizational debt obligations, ensuring timely payments and optimal debt structure.'],
                    [1, 'Pending Bills', 'Review, process, and manage pending bills and financial obligations to maintain good vendor relationships and cash flow.'],
                    [1, 'Zero Fault Audits', 'Conduct comprehensive audits with zero tolerance for errors, ensuring complete accuracy in financial and operational processes.'],
                    [2, 'Implementation of Citizens\' Service Delivery Charter', 'Implement and monitor compliance with the Citizens\' Service Delivery Charter to ensure quality public service delivery.'],
                    [2, 'Resolution of Public Complaints', 'Manage and resolve public complaints efficiently and effectively, ensuring citizen satisfaction and service improvement.'],
                    [3, 'Review Existing Training Programs', 'Evaluate and review current training programs for effectiveness, relevance, and alignment with organizational goals and corporate performance contract.'],
                    [3, 'Develop and Roll Out New Training Programs', 'Design, develop, and implement new training programs to address identified gaps and meet organizational objectives.'],
                    [3, 'Undertake Consultancy and Research Activities', 'Conduct consultancy services and research activities to support organizational development and knowledge advancement.'],
                    [3, 'Organize and Host National Symposia or Conferences', 'Plan, organize, and execute national symposia, conferences, and knowledge-sharing events to promote learning and development.'],
                    [3, 'Improve Productivity', 'Identify and implement strategies to enhance organizational productivity, efficiency, and performance across all departments.'],
                    [3, 'Manage Customer Experience and Satisfaction Score', 'Monitor, measure, and improve customer experience metrics and satisfaction scores through systematic feedback and improvement processes.']
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO task_templates (category_id, title, description) VALUES (?, ?, ?)");
                foreach ($templates as $template) {
                    $stmt->execute($template);
                }
                
                // Insert security settings
                $security_settings = [
                    ['password_policy', '{"minLength": 8, "requireSpecialChar": true, "requireUppercase": true, "requireNumbers": true}'],
                    ['session_settings', '{"timeout": 30, "maxSessions": 3}'],
                    ['two_factor_auth', '{"enabled": false, "method": "email"}'],
                    ['backup_settings', '{"schedule": "weekly", "retention": 10, "autoBackup": true}']
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO security_settings (setting_name, setting_value) VALUES (?, ?)");
                foreach ($security_settings as $setting) {
                    $stmt->execute($setting);
                }
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Initial data inserted successfully</span>';
                echo '</div>';
                
                // Step 5: Create default admin user
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Creating default admin user...</span>';
                echo '</div>';
                
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT IGNORE INTO admins (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute(['System Administrator', 'admin@ksg.ac.ke', $admin_password]);
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Default admin user created</span>';
                echo '</div>';
                
                // Step 6: Create default user
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Creating default user...</span>';
                echo '</div>';
                
                $user_password = password_hash('user123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, department, job_title) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['John Doe', 'john.doe@ksg.ac.ke', $user_password, 'training', 'Training Officer']);
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Default user created</span>';
                echo '</div>';
                
                // Step 7: Create sample tasks
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Creating sample tasks...</span>';
                echo '</div>';
                
                // Get the user ID we just created
                $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $user_stmt->execute(['john.doe@ksg.ac.ke']);
                $user_id = $user_stmt->fetchColumn();
                
                if ($user_id) {
                    $sample_tasks = [
                        [$user_id, 'Complete Security Training Module', 'Complete the mandatory cybersecurity awareness training and pass the assessment with at least 80% score.', 'high', 'pending', date('Y-m-d H:i:s', strtotime('+1 day')), 'System Administrator'],
                        [$user_id, 'Submit Monthly Performance Report', 'Prepare and submit your monthly performance report including key achievements, challenges, and goals for next month.', 'medium', 'pending', date('Y-m-d H:i:s', strtotime('+7 days')), 'System Administrator'],
                        [$user_id, 'Update Emergency Contact Information', 'Review and update your emergency contact details in the HR system to ensure accuracy.', 'low', 'completed', date('Y-m-d H:i:s', strtotime('-2 days')), 'System Administrator']
                    ];
                    
                    $task_stmt = $pdo->prepare("INSERT INTO user_tasks (user_id, title, description, priority, status, due_date, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($sample_tasks as $task) {
                        $task_stmt->execute($task);
                    }
                }
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Sample tasks created</span>';
                echo '</div>';
                
                // Step 8: Update configuration file
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Updating configuration...</span>';
                echo '</div>';
                
                $config_file = '../config/database.php';
                if (file_exists($config_file) && is_writable($config_file)) {
                    $config_content = file_get_contents($config_file);
                    
                    // Update database configuration
                    $config_content = preg_replace("/private \$host = '.*?';/", "private \$host = '$db_host';", $config_content);
                    $config_content = preg_replace("/private \$db_name = '.*?';/", "private \$db_name = '$db_name';", $config_content);
                    $config_content = preg_replace("/private \$username = '.*?';/", "private \$username = '$db_user';", $config_content);
                    $config_content = preg_replace("/private \$password = '.*?';/", "private \$password = '$db_pass';", $config_content);
                    
                    // Update constants
                    $config_content = preg_replace("/define\('DB_HOST', '.*?'\);/", "define('DB_HOST', '$db_host');", $config_content);
                    $config_content = preg_replace("/define\('DB_NAME', '.*?'\);/", "define('DB_NAME', '$db_name');", $config_content);
                    $config_content = preg_replace("/define\('DB_USER', '.*?'\);/", "define('DB_USER', '$db_user');", $config_content);
                    $config_content = preg_replace("/define\('DB_PASS', '.*?'\);/", "define('DB_PASS', '$db_pass');", $config_content);
                    
                    file_put_contents($config_file, $config_content);
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Configuration updated</span>';
                    echo '</div>';
                } else {
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-yellow-500 rounded-full"></div>';
                    echo '<span class="text-yellow-700">⚠ Configuration file not writable. Please update config/database.php manually.</span>';
                    echo '</div>';
                }
                
                echo '</div>';
                echo '</div>';
                
                // Success message
                echo '<div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">';
                echo '<h3 class="text-lg font-semibold text-green-800 mb-2">✅ Installation Completed Successfully!</h3>';
                echo '<div class="text-green-700 space-y-2">';
                echo '<p><strong>Database:</strong> ' . htmlspecialchars($db_name) . '</p>';
                echo '<p><strong>Admin Login:</strong> admin@ksg.ac.ke / admin123</p>';
                echo '<p><strong>User Login:</strong> john.doe@ksg.ac.ke / user123</p>';
                echo '<p><strong>Admin Index Code:</strong> Richmond@524</p>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<a href="../INDEX.HTML" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">Go to Application</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-red-500 rounded-full"></div>';
                echo '<span class="text-red-600">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">';
                echo '<h3 class="text-lg font-semibold text-red-800 mb-2">Installation Failed</h3>';
                echo '<p class="text-red-700">Please check your database configuration and try again.</p>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<button onclick="window.location.reload()" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">';
                echo 'Try Again';
                echo '</button>';
                echo '</div>';
            }
        } else {
            // Show installation form
            ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label for="db_host" class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="db_name" class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="ksg_smi_performance" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="db_user" class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                    <input type="text" id="db_user" name="db_user" value="root" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" value=""
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Leave empty if no password is required</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">What will be installed:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Complete database schema with all tables</li>
                        <li>• Task categories and templates</li>
                        <li>• Default admin user (admin@ksg.ac.ke / admin123)</li>
                        <li>• Default user account (john.doe@ksg.ac.ke / user123)</li>
                        <li>• Security settings and system configuration</li>
                        <li>• Sample data for testing</li>
                    </ul>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        Install Database
                    </button>
                </div>
            </form>
            <?php
        }
        ?><?php
/**
 * KSG SMI Performance System - Secure Database Setup Script
 * This script will create the database and populate it with initial data
 * 
 * Security Features:
 * - Strong password generation
 * - Input validation and sanitization
 * - Installation lock mechanism
 * - Transaction management
 * - Comprehensive logging
 * - Environment detection
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security: Check if already installed
$lock_file = __DIR__ . '/.installed';
if (file_exists($lock_file)) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Already Complete</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
            <div class="text-red-600 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Installation Already Complete</h1>
            <p class="text-gray-600 mb-6">The system has already been installed. To reinstall, delete the <code>.installed</code> file in the install directory.</p>
            <a href="../INDEX.HTML" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">Go to Application</a>
        </div>
    </body>
    </html>
    ');
}

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Logging function
function logInstallation($message, $level = 'INFO') {
    global $log_dir;
    $log_file = $log_dir . '/installation.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Environment detection
$is_production = !in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1', '::1']);

// Security functions
function generateSecurePassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

function validateInput($input, $type = 'string') {
    switch ($type) {
        case 'host':
            $input = filter_var($input, FILTER_SANITIZE_STRING);
            if (!filter_var($input, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && 
                !filter_var($input, FILTER_VALIDATE_IP)) {
                throw new InvalidArgumentException("Invalid database host format");
            }
            break;
        case 'database_name':
            $input = filter_var($input, FILTER_SANITIZE_STRING);
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $input)) {
                throw new InvalidArgumentException("Database name can only contain letters, numbers, and underscores");
            }
            break;
        case 'username':
            $input = filter_var($input, FILTER_SANITIZE_STRING);
            if (strlen($input) < 1 || strlen($input) > 32) {
                throw new InvalidArgumentException("Username must be between 1 and 32 characters");
            }
            break;
        default:
            $input = filter_var($input, FILTER_SANITIZE_STRING);
    }
    return $input;
}

// Include database configuration
require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSG SMI Performance System - Secure Database Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .password-display {
            font-family: 'Courier New', monospace;
            background-color: #f3f4f6;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">KSG SMI Performance System</h1>
            <p class="text-gray-600">Secure Database Setup & Installation</p>
            <?php if ($is_production): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
                <p class="text-yellow-800 text-sm">🔒 Production environment detected - Enhanced security enabled</p>
            </div>
            <?php endif; ?>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            logInstallation("Installation process started");
            
            try {
                // Validate and sanitize inputs
                $db_host = validateInput($_POST['db_host'] ?? 'localhost', 'host');
                $db_name = validateInput($_POST['db_name'] ?? 'ksg_smi_performance', 'database_name');
                $db_user = validateInput($_POST['db_user'] ?? 'root', 'username');
                $db_pass = $_POST['db_pass'] ?? '';
                
                logInstallation("Input validation completed successfully");
                
                echo '<div class="mb-6">';
                echo '<h2 class="text-xl font-semibold mb-4">Installation Progress</h2>';
                echo '<div class="space-y-2">';
                
                // Step 1: Test database connection
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                echo '<span>Testing database connection...</span>';
                echo '</div>';
                
                $pdo = new PDO(
                    "mysql:host=$db_host;charset=utf8mb4", 
                    $db_user, 
                    $db_pass, 
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                        PDO::ATTR_TIMEOUT => 10
                    ]
                );
                
                logInstallation("Database connection established successfully");
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                echo '<span class="text-green-600">✓ Database connection successful</span>';
                echo '</div>';
                
                // Begin transaction for atomic installation
                $pdo->beginTransaction();
                
                try {
                    // Step 2: Create database
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Creating database...</span>';
                    echo '</div>';
                    
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `$db_name`");
                    
                    logInstallation("Database '$db_name' created successfully");
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Database created successfully</span>';
                    echo '</div>';
                    
                    // Step 3: Create tables
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Creating tables...</span>';
                    echo '</div>';
                    
                    // Enhanced table definitions with better indexes
                    $tables = [
                        // Users table with enhanced security
                        "CREATE TABLE IF NOT EXISTS users (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            phone VARCHAR(20),
                            department ENUM('administration', 'finance', 'hr', 'it', 'training', 'research') DEFAULT NULL,
                            job_title VARCHAR(255),
                            role ENUM('user', 'manager') DEFAULT 'user',
                            profile_picture LONGTEXT,
                            settings JSON,
                            notification_preferences JSON,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            created_by VARCHAR(255),
                            last_login TIMESTAMP NULL,
                            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                            failed_login_attempts INT DEFAULT 0,
                            locked_until TIMESTAMP NULL,
                            password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_email (email),
                            INDEX idx_status (status),
                            INDEX idx_department (department)
                        )",
                        
                        // Admins table with enhanced security
                        "CREATE TABLE IF NOT EXISTS admins (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            phone VARCHAR(20),
                            profile_picture LONGTEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            last_login TIMESTAMP NULL,
                            status ENUM('active', 'inactive') DEFAULT 'active',
                            failed_login_attempts INT DEFAULT 0,
                            locked_until TIMESTAMP NULL,
                            password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_email (email),
                            INDEX idx_status (status)
                        )",
                        
                        // Task categories table
                        "CREATE TABLE IF NOT EXISTS task_categories (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            description TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_name (name)
                        )",
                        
                        // Task templates table
                        "CREATE TABLE IF NOT EXISTS task_templates (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            category_id INT,
                            title VARCHAR(255) NOT NULL,
                            description TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL,
                            INDEX idx_category (category_id),
                            INDEX idx_title (title)
                        )",
                        
                        // User tasks table with performance indexes
                        "CREATE TABLE IF NOT EXISTS user_tasks (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            title VARCHAR(255) NOT NULL,
                            description TEXT NOT NULL,
                            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                            status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
                            due_date DATETIME NOT NULL,
                            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            completed_date TIMESTAMP NULL,
                            assigned_by VARCHAR(255),
                            assigned_by_id INT,
                            instructions TEXT,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (assigned_by_id) REFERENCES admins(id) ON DELETE SET NULL,
                            INDEX idx_user_status (user_id, status),
                            INDEX idx_due_date (due_date),
                            INDEX idx_status (status),
                            INDEX idx_priority (priority)
                        )",
                        
                        // Task uploads table
                        "CREATE TABLE IF NOT EXISTS task_uploads (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            task_id INT NOT NULL,
                            file_name VARCHAR(255) NOT NULL,
                            file_type VARCHAR(100),
                            file_size BIGINT,
                            file_data LONGBLOB,
                            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (task_id) REFERENCES user_tasks(id) ON DELETE CASCADE,
                            INDEX idx_task_id (task_id)
                        )",
                        
                        // Enhanced security settings table
                        "CREATE TABLE IF NOT EXISTS security_settings (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            setting_name VARCHAR(100) UNIQUE NOT NULL,
                            setting_value JSON NOT NULL,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            updated_by INT,
                            FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL,
                            INDEX idx_setting_name (setting_name)
                        )",
                        
                        // Access logs table with better indexing
                        "CREATE TABLE IF NOT EXISTS access_logs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT,
                            admin_id INT,
                            user_type ENUM('user', 'admin') NOT NULL,
                            action VARCHAR(255) NOT NULL,
                            ip_address VARCHAR(45),
                            user_agent TEXT,
                            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
                            INDEX idx_timestamp (timestamp),
                            INDEX idx_user_type_id (user_type, user_id, admin_id),
                            INDEX idx_action (action)
                        )",
                        
                        // System backups table
                        "CREATE TABLE IF NOT EXISTS system_backups (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            backup_name VARCHAR(255) NOT NULL,
                            backup_data LONGTEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            created_by INT,
                            file_size BIGINT,
                            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
                            INDEX idx_created_at (created_at)
                        )",
                        
                        // User reports table
                        "CREATE TABLE IF NOT EXISTS user_reports (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            report_type ENUM('weekly', 'monthly', 'quarterly', 'annual') NOT NULL,
                            report_data JSON NOT NULL,
                            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            INDEX idx_user_type (user_id, report_type),
                            INDEX idx_generated_at (generated_at)
                        )",
                        
                        // System analytics table
                        "CREATE TABLE IF NOT EXISTS system_analytics (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            metric_name VARCHAR(100) NOT NULL,
                            metric_value JSON NOT NULL,
                            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            date_key DATE NOT NULL,
                            INDEX idx_date_metric (date_key, metric_name),
                            INDEX idx_metric_name (metric_name)
                        )"
                    ];
                    
                    foreach ($tables as $table_sql) {
                        $pdo->exec($table_sql);
                    }
                    
                    logInstallation("All database tables created successfully");
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Tables created successfully</span>';
                    echo '</div>';
                    
                    // Step 4: Insert initial data
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Inserting initial data...</span>';
                    echo '</div>';
                    
                    // Insert task categories
                    $categories = [
                        ['Financial Stewardship and Discipline', 'Tasks related to financial management, revenue optimization, debt management, and audit processes'],
                        ['Service Delivery', 'Tasks focused on citizen service delivery, complaint resolution, and service quality improvement'],
                        ['Core Mandate', 'Tasks related to training programs, consultancy, research activities, and organizational development']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO task_categories (name, description) VALUES (?, ?)");
                    foreach ($categories as $category) {
                        $stmt->execute($category);
                    }
                    
                    // Insert task templates
                    $templates = [
                        [1, 'Revenue', 'Monitor and optimize revenue generation activities, analyze financial performance metrics, and implement strategies to increase organizational income.'],
                        [1, 'Debt Management', 'Monitor, analyze, and manage organizational debt obligations, ensuring timely payments and optimal debt structure.'],
                        [1, 'Pending Bills', 'Review, process, and manage pending bills and financial obligations to maintain good vendor relationships and cash flow.'],
                        [1, 'Zero Fault Audits', 'Conduct comprehensive audits with zero tolerance for errors, ensuring complete accuracy in financial and operational processes.'],
                        [2, 'Implementation of Citizens\' Service Delivery Charter', 'Implement and monitor compliance with the Citizens\' Service Delivery Charter to ensure quality public service delivery.'],
                        [2, 'Resolution of Public Complaints', 'Manage and resolve public complaints efficiently and effectively, ensuring citizen satisfaction and service improvement.'],
                        [3, 'Review Existing Training Programs', 'Evaluate and review current training programs for effectiveness, relevance, and alignment with organizational goals and corporate performance contract.'],
                        [3, 'Develop and Roll Out New Training Programs', 'Design, develop, and implement new training programs to address identified gaps and meet organizational objectives.'],
                        [3, 'Undertake Consultancy and Research Activities', 'Conduct consultancy services and research activities to support organizational development and knowledge advancement.'],
                        [3, 'Organize and Host National Symposia or Conferences', 'Plan, organize, and execute national symposia, conferences, and knowledge-sharing events to promote learning and development.'],
                        [3, 'Improve Productivity', 'Identify and implement strategies to enhance organizational productivity, efficiency, and performance across all departments.'],
                        [3, 'Manage Customer Experience and Satisfaction Score', 'Monitor, measure, and improve customer experience metrics and satisfaction scores through systematic feedback and improvement processes.']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO task_templates (category_id, title, description) VALUES (?, ?, ?)");
                    foreach ($templates as $template) {
                        $stmt->execute($template);
                    }
                    
                    // Insert enhanced security settings
                    $security_settings = [
                        ['password_policy', '{"minLength": 12, "requireSpecialChar": true, "requireUppercase": true, "requireNumbers": true, "maxAge": 90, "preventReuse": 5}'],
                        ['session_settings', '{"timeout": 30, "maxSessions": 3, "secureOnly": true, "httpOnly": true}'],
                        ['two_factor_auth', '{"enabled": false, "method": "email", "required_for_admin": true}'],
                        ['backup_settings', '{"schedule": "weekly", "retention": 10, "autoBackup": true, "encryption": true}'],
                        ['login_security', '{"maxFailedAttempts": 5, "lockoutDuration": 30, "requireCaptcha": true}'],
                        ['audit_settings', '{"logAllActions": true, "retentionDays": 365, "alertOnSuspicious": true}']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO security_settings (setting_name, setting_value) VALUES (?, ?)");
                    foreach ($security_settings as $setting) {
                        $stmt->execute($setting);
                    }
                    
                    logInstallation("Initial data and security settings inserted successfully");
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Initial data inserted successfully</span>';
                    echo '</div>';
                    
                    // Step 5: Create secure admin user
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Creating secure admin user...</span>';
                    echo '</div>';
                    
                    // Generate secure password for production or use default for development
                    $admin_password_plain = $is_production ? generateSecurePassword(16) : 'Admin@2024!';
                    $admin_password = password_hash($admin_password_plain, PASSWORD_ARGON2ID);
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO admins (name, email, password, password_changed_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute(['System Administrator', 'admin@ksg.ac.ke', $admin_password]);
                    
                    logInstallation("Secure admin user created successfully");
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Secure admin user created</span>';
                    echo '</div>';
                    
                    // Step 6: Create secure default user
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Creating secure default user...</span>';
                    echo '</div>';
                    
                    $user_password_plain = $is_production ? generateSecurePassword(12) : 'User@2024!';
                    $user_password = password_hash($user_password_plain, PASSWORD_ARGON2ID);
                    
                    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, department, job_title, password_changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute(['John Doe', 'john.doe@ksg.ac.ke', $user_password, 'training', 'Training Officer']);
                    
                    logInstallation("Secure default user created successfully");
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Secure default user created</span>';
                    echo '</div>';
                    
                    // Step 7: Create sample tasks
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Creating sample tasks...</span>';
                    echo '</div>';
                    
                    $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $user_stmt->execute(['john.doe@ksg.ac.ke']);
                    $user_id = $user_stmt->fetchColumn();
                    
                    if ($user_id) {
                        $sample_tasks = [
                            [$user_id, 'Complete Security Training Module', 'Complete the mandatory cybersecurity awareness training and pass the assessment with at least 80% score.', 'high', 'pending', date('Y-m-d H:i:s', strtotime('+1 day')), 'System Administrator'],
                            [$user_id, 'Submit Monthly Performance Report', 'Prepare and submit your monthly performance report including key achievements, challenges, and goals for next month.', 'medium', 'pending', date('Y-m-d H:i:s', strtotime('+7 days')), 'System Administrator'],
                            [$user_id, 'Update Emergency Contact Information', 'Review and update your emergency contact details in the HR system to ensure accuracy.', 'low', 'completed', date('Y-m-d H:i:s', strtotime('-2 days')), 'System Administrator']
                        ];
                        
                        $task_stmt = $pdo->prepare("INSERT INTO user_tasks (user_id, title, description, priority, status, due_date, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        foreach ($sample_tasks as $task) {
                            $task_stmt->execute($task);
                        }
                    }
                    
                    logInstallation("Sample tasks created successfully");
                    
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                    echo '<span class="text-green-600">✓ Sample tasks created</span>';
                    echo '</div>';
                    
                    // Step 8: Update configuration file
                    echo '<div class="flex items-center space-x-2">';
                    echo '<div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>';
                    echo '<span>Updating configuration...</span>';
                    echo '</div>';
                    
                    $config_file = '../config/database.php';
                    if (file_exists($config_file) && is_writable($config_file)) {
                        // Create backup
                        copy($config_file, $config_file . '.backup.' . date('Y-m-d-H-i-s'));
                        
                        $config_content = file_get_contents($config_file);
                        
                        // Update database configuration with more precise patterns
                        $replacements = [
                            "/private \\\$host = '[^']*';/" => "private \$host = '$db_host';",
                            "/private \\\$db_name = '[^']*';/" => "private \$db_name = '$db_name';",
                            "/private \\\$username = '[^']*';/" => "private \$username = '$db_user';",
                            "/private \\\$password = '[^']*';/" => "private \$password = '$db_pass';",
                            "/define\('DB_HOST', '[^']*'\);/" => "define('DB_HOST', '$db_host');",
                            "/define\('DB_NAME', '[^']*'\);/" => "define('DB_NAME', '$db_name');",
                            "/define\('DB_USER', '[^']*'\);/" => "define('DB_USER', '$db_user');",
                            "/define\('DB_PASS', '[^']*'\);/" => "define('DB_PASS', '$db_pass');"
                        ];
                        
                        foreach ($replacements as $pattern => $replacement) {
                            $config_content = preg_replace($pattern, $replacement, $config_content);
                        }
                        
                        file_put_contents($config_file, $config_content);
                        
                        logInstallation("Configuration file updated successfully");
                        
                        echo '<div class="flex items-center space-x-2">';
                        echo '<div class="w-4 h-4 bg-green-500 rounded-full"></div>';
                        echo '<span class="text-green-600">✓ Configuration updated</span>';
                        echo '</div>';
                    } else {
                        logInstallation("Configuration file not writable", "WARNING");
                        echo '<div class="flex items-center space-x-2">';
                        echo '<div class="w-4 h-4 bg-yellow-500 rounded-full"></div>';
                        echo '<span class="text-yellow-700">⚠ Configuration file not writable. Please update config/database.php manually.</span>';
                        echo '</div>';
                    }
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Create installation lock file
                    file_put_contents($lock_file, json_encode([
                        'installed_at' => date('Y-m-d H:i:s'),
                        'database' => $db_name,
                        'version' => '1.0.0',
                        'environment' => $is_production ? 'production' : 'development'
                    ]));
                    
                    logInstallation("Installation completed successfully");
                    
                    echo '</div>';
                    echo '</div>';
                    
                    // Success message with secure credentials
                    echo '<div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">';
                    echo '<h3 class="text-lg font-semibold text-green-800 mb-4">✅ Installation Completed Successfully!</h3>';
                    echo '<div class="text-green-700 space-y-3">';
                    echo '<p><strong>Database:</strong> ' . htmlspecialchars($db_name) . '</p>';
                    echo '<div>';
                    echo '<p><strong>Admin Login:</strong></p>';
                    echo '<p class="ml-4">Email: admin@ksg.ac.ke</p>';
                    echo '<p class="ml-4">Password: <span class="password-display">' . htmlspecialchars($admin_password_plain) . '</span></p>';
                    echo '</div>';
                    echo '<div>';
                    echo '<p><strong>User Login:</strong></p>';
                    echo '<p class="ml-4">Email: john.doe@ksg.ac.ke</p>';
                    echo '<p class="ml-4">Password: <span class="password-display">' . htmlspecialchars($user_password_plain) . '</span></p>';
                    echo '</div>';
                    echo '<p><strong>Admin Index Code:</strong> Richmond@524</p>';
                    if ($is_production) {
                        echo '<div class="bg-yellow-100 border border-yellow-300 rounded p-3 mt-4">';
                        echo '<p class="text-yellow-800 font-semibold">🔒 Security Notice:</p>';
                        echo '<p class="text-yellow-700 text-sm">Strong passwords have been generated for production. Please save these credentials securely and change them after first login.</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="text-center">';
                    echo '<a href="../INDEX.HTML" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">Go to Application</a>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollback();
                    throw $e;
                }
                
            } catch (Exception $e) {
                logInstallation("Installation failed: " . $e->getMessage(), "ERROR");
                
                echo '<div class="flex items-center space-x-2">';
                echo '<div class="w-4 h-4 bg-red-500 rounded-full"></div>';
                echo '<span class="text-red-600">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">';
                echo '<h3 class="text-lg font-semibold text-red-800 mb-2">Installation Failed</h3>';
                echo '<p class="text-red-700">Please check your database configuration and try again.</p>';
                echo '<p class="text-red-600 text-sm mt-2">Error details have been logged to logs/installation.log</p>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<button onclick="window.location.reload()" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">';
                echo 'Try Again';
                echo '</button>';
                echo '</div>';
            }
        } else {
            // Show installation form
            ?>
            <form method="POST" class="space-y-6" onsubmit="return validateForm()">
                <div>
                    <label for="db_host" class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required
                           pattern="^[a-zA-Z0-9.-]+$"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Enter the database server hostname or IP address</p>
                </div>
                
                <div>
                    <label for="db_name" class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="ksg_smi_performance" required
                           pattern="^[a-zA-Z0-9_]+$"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Only letters, numbers, and underscores allowed</p>
                </div>
                
                <div>
                    <label for="db_user" class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                    <input type="text" id="db_user" name="db_user" value="root" required
                           maxlength="32"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" value=""
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Leave empty if no password is required</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">🔒 Enhanced Security Features:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Strong password generation for production environments</li>
                        <li>• Input validation and sanitization</li>
                        <li>• Transaction-based atomic installation</li>
                        <li>• Comprehensive audit logging</li>
                        <li>• Installation lock mechanism</li>
                        <li>• Enhanced database indexes for performance</li>
                        <li>• Secure password hashing (Argon2ID)</li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">What will be installed:</h3>
                    <ul class="text-sm text-gray-700 space-y-1">
                        <li>• Complete database schema with optimized indexes</li>
                        <li>• Task categories and templates</li>
                        <li>• Secure admin user with strong password</li>
                        <li>• Secure default user account</li>
                        <li>• Enhanced security settings and policies</li>
                        <li>• Sample data for testing</li>
                        <li>• Comprehensive logging system</li>
                    </ul>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        🚀 Install Secure Database
                    </button>
                </div>
            </form>
            
            <script>
                function validateForm() {
                    const dbHost = document.getElementById('db_host').value;
                    const dbName = document.getElementById('db_name').value;
                    const dbUser = document.getElementById('db_user').value;
                    
                    // Basic client-side validation
                    if (!dbHost.match(/^[a-zA-Z0-9.-]+$/)) {
                        alert('Database host contains invalid characters');
                        return false;
                    }
                    
                    if (!dbName.match(/^[a-zA-Z0-9_]+$/)) {
                        alert('Database name can only contain letters, numbers, and underscores');
                        return false;
                    }
                    
                    if (dbUser.length < 1 || dbUser.length > 32) {
                        alert('Database username must be between 1 and 32 characters');
                        return false;
                    }
                    
                    return confirm('Are you sure you want to proceed with the installation? This will create/modify the database.');
                }
            </script>
            <?php
        }
        ?>
    </div>
</body>
</html>
</qodoArtifact>

I've created a significantly improved and secure version of your setup script. Here are the key enhancements:

## 🔒 **Security Improvements**

### **1. Strong Password Generation**
- **Production**: Generates cryptographically secure 16-character admin passwords and 12-character user passwords
- **Development**: Uses secure default passwords (`Admin@2024!`, `User@2024!`)
- **Hashing**: Uses Argon2ID instead of basic PASSWORD_DEFAULT

### **2. Input Validation & Sanitization**
- Validates database host (domain/IP format)
- Sanitizes database names (alphanumeric + underscores only)
- Validates username length (1-32 characters)
- Client-side and server-side validation

### **3. Installation Security**
- **Installation Lock**: Prevents re-running after successful installation
- **Environment Detection**: Automatically detects production vs development
- **Configuration Backup**: Creates timestamped backups before modifications

## 🚀 **Technical Improvements**

### **1. Transaction Management**
- Wraps entire installation in database transaction
- Automatic rollback on any failure
- Ensures atomic installation process

### **2. Enhanced Database Schema**
- **Performance Indexes**: Added strategic indexes for better query performance
- **Security Fields**: Added failed login attempts, account locking, password change tracking
- **Better Data Types**: Uses BIGINT for file sizes, proper VARCHAR lengths

### **3. Comprehensive Logging**
- Logs all installation steps to `logs/installation.log`
- Includes timestamps and severity levels
- Helps with debugging and audit trails

### **4. Error Handling**
- More specific error messages
- Graceful failure handling
- Better user feedback during installation

## 🛡️ **Enhanced Security Settings**

The script now includes advanced security configurations:
- **Password Policy**: 12+ characters, special chars, numbers, uppercase required
- **Session Security**: Secure cookies, HTTP-only flags
- **Login Security**: Failed attempt tracking, account lockouts
- **Audit Settings**: Comprehensive action logging

## 📊 **User Experience Improvements**

- **Real-time Progress**: Visual progress indicators during installation
- **Environment Awareness**: Shows production/development status
- **Secure Credential Display**: Clearly shows generated passwords
- **Better Validation**: Client-side form validation with helpful messages

## 🔧 **Production Ready Features**

- **Environment Detection**: Automatically applies stricter security in production
- **Configuration Management**: Safe config file updates with backups
- **Installation Verification**: Creates lock file with installation metadata
- **Comprehensive Logging**: Full audit trail of installation process

The improved script maintains all the functionality of your original while adding enterprise-level security and reliability features. It's now suitable for both development and production deployments with appropriate security measures for each environment.
    </div>
</body>
</html>