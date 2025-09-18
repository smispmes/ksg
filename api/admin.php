<?php
/**
 * Admin API Endpoints
 * KSG SMI Performance System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../classes/Admin.php';
require_once '../classes/User.php';
require_once '../classes/Task.php';

// Check admin authentication
function checkAdminAuth() {
    if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
        echo json_encode(array('status' => 'error', 'message' => 'Admin access required'));
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        session_destroy();
        echo json_encode(array('status' => 'error', 'message' => 'Session expired'));
        exit;
    }
}

// Helper function to log admin activity
function logAdminActivity($action) {
    try {
        $database = new Database();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "CALL LogUserActivity(NULL, :admin_id, 'admin', :action, :ip_address, :user_agent)";
        $params = array(
            ':admin_id' => $_SESSION['admin_id'],
            ':action' => $action,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        );
        
        $database->execute($query, $params);
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    checkAdminAuth();
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'get_users':
                    $search = $_GET['search'] ?? '';
                    $department = $_GET['department'] ?? '';
                    $status = $_GET['status'] ?? 'active';
                    
                    $admin = new Admin();
                    $result = $admin->getUserManagementData($search, $department);
                    echo json_encode($result);
                    break;
                
                case 'get_analytics':
                    $admin = new Admin();
                    $result = $admin->getSystemAnalytics();
                    echo json_encode($result);
                    break;
                
                case 'get_security_settings':
                    $admin = new Admin();
                    $result = $admin->getSecuritySettings();
                    echo json_encode($result);
                    break;
                
                case 'get_access_logs':
                    $limit = $_GET['limit'] ?? 50;
                    $user_type = $_GET['user_type'] ?? '';
                    $date_from = $_GET['date_from'] ?? '';
                    $date_to = $_GET['date_to'] ?? '';
                    
                    $admin = new Admin();
                    $result = $admin->getAccessLogs($limit, $user_type, $date_from, $date_to);
                    echo json_encode($result);
                    break;
                
                case 'get_backups':
                    $limit = $_GET['limit'] ?? 10;
                    
                    $admin = new Admin();
                    $result = $admin->getSystemBackups($limit);
                    echo json_encode($result);
                    break;
                
                case 'download_backup':
                    $backup_id = $_GET['backup_id'] ?? '';
                    if (empty($backup_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Backup ID required'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->downloadBackup($backup_id);
                    
                    if ($result['status'] === 'success') {
                        $backup = $result['backup'];
                        
                        header('Content-Type: application/json');
                        header('Content-Disposition: attachment; filename="' . $backup['backup_name'] . '.json"');
                        header('Content-Length: ' . strlen($backup['backup_data']));
                        
                        echo $backup['backup_data'];
                        logAdminActivity('Downloaded backup: ' . $backup['backup_name']);
                        exit;
                    } else {
                        echo json_encode($result);
                    }
                    break;
                
                case 'get_all_tasks':
                    $status = $_GET['status'] ?? '';
                    $priority = $_GET['priority'] ?? '';
                    $user_id = $_GET['user_id'] ?? '';
                    $date_from = $_GET['date_from'] ?? '';
                    $date_to = $_GET['date_to'] ?? '';
                    
                    $task = new Task();
                    $result = $task->getAllTasks($status, $priority, $user_id, $date_from, $date_to);
                    echo json_encode($result);
                    break;
                case 'get_task_templates':
                    $task = new Task();
                    $result = $task->getTaskTemplates();
                    echo json_encode($result);
                    break;
                
                case 'get_recent_assignments':
                    $limit = $_GET['limit'] ?? 10;
                    
                    $task = new Task();
                    $result = $task->getRecentAssignments($limit);
                    echo json_encode($result);
                    break;
                
                case 'export_analytics':
                    $admin = new Admin();
                    $analytics_result = $admin->getSystemAnalytics();
                    
                    if ($analytics_result['status'] === 'success') {
                        $user = new User();
                        $users_result = $user->getAllUsers();
                        
                        $csv_data = array();
                        $csv_data[] = 'KSG SMI Performance System - Analytics Report';
                        $csv_data[] = 'Generated: ' . date('Y-m-d H:i:s');
                        $csv_data[] = '';
                        $csv_data[] = 'System Statistics';
                        
                        $analytics = $analytics_result['analytics'];
                        foreach ($analytics as $key => $value) {
                            $csv_data[] = ucfirst(str_replace('_', ' ', $key)) . ',' . $value;
                        }
                        
                        $csv_data[] = '';
                        $csv_data[] = 'User Details';
                        $csv_data[] = 'Name,Email,Department,Status,Created Date,Last Login';
                        
                        if ($users_result['status'] === 'success') {
                            foreach ($users_result['users'] as $user) {
                                $csv_data[] = '"' . $user['name'] . '","' . $user['email'] . '","' . 
                                            ($user['department'] ?? 'Not set') . '","' . $user['status'] . '","' . 
                                            date('Y-m-d', strtotime($user['created_at'])) . '","' . 
                                            ($user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never') . '"';
                            }
                        }
                        
                        $csv_content = implode("\n", $csv_data);
                        
                        header('Content-Type: text/csv');
                        header('Content-Disposition: attachment; filename="ksg_analytics_' . date('Y-m-d') . '.csv"');
                        header('Content-Length: ' . strlen($csv_content));
                        
                        echo $csv_content;
                        logAdminActivity('Exported analytics report');
                        exit;
                    } else {
                        echo json_encode($analytics_result);
                    }
                    break;
                
                default:
                    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
                    break;
            }
            break;
        
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'create_user':
                    $required_fields = array('name', 'email', 'password');
                    $errors = array();
                    
                    foreach ($required_fields as $field) {
                        if (!isset($input[$field]) || empty(trim($input[$field]))) {
                            $errors[] = ucfirst($field) . ' is required';
                        }
                    }
                    
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Invalid email format'));
                        break;
                    }
                    
                    $input['created_by'] = $_SESSION['admin_name'] ?? 'Admin';
                    
                    $admin = new Admin();
                    $result = $admin->createUser($input);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Created user: ' . $input['name'] . ' (' . $input['email'] . ')');
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'update_security_settings':
                    if (empty($input)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Settings data required'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->updateSecuritySettings($input, $_SESSION['admin_id']);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Updated security settings');
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'create_backup':
                    $admin = new Admin();
                    $result = $admin->createSystemBackup($_SESSION['admin_id']);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Created system backup: ' . $result['backup_name']);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'assign_predefined_task':
                    $required_fields = array('user_id', 'category', 'title', 'due_date');
                    $errors = array();
                    
                    foreach ($required_fields as $field) {
                        if (!isset($input[$field]) || empty(trim($input[$field]))) {
                            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        }
                    }
                    
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    $task = new Task();
                    $admin_id = $_SESSION['admin_id'];
                    $result = $task->assignPredefinedTask(
                        $input['category'],
                        $input['title'],
                        $input['user_id'],
                        $admin_id,
                        $input['due_date'],
                        $input['priority'] ?? 'medium',
                        trim($input['instructions'] ?? '')
                    );
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Assigned predefined task: ' . $input['title'] . ' to user ID ' . $input['user_id']);
                    }
                    
                    echo json_encode($result);
                    break;
                case 'assign_task':
                    $required_fields = array('user_id', 'title', 'description', 'due_date');
                    $errors = array();
                    
                    foreach ($required_fields as $field) {
                        if (!isset($input[$field]) || empty(trim($input[$field]))) {
                            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        }
                    }
                    
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    $task = new Task();
                    $task->user_id = $input['user_id'];
                    $task->title = trim($input['title']);
                    $task->description = trim($input['description']);
                    $task->priority = $input['priority'] ?? 'medium';
                    $task->due_date = $input['due_date'];
                    $task->assigned_by = $_SESSION['admin_name'] ?? 'Admin';
                    $task->assigned_by_id = $_SESSION['admin_id'];
                    $task->instructions = trim($input['instructions'] ?? '');
                    
                    $result = $task->createTask();
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Assigned task: ' . $task->title . ' to user ID ' . $task->user_id);
                    }
                    
                    echo json_encode($result);
                    break;
                
                default:
                    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
                    break;
            }
            break;
        
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'reset_user_password':
                    $user_id = $_GET['user_id'] ?? '';
                    $new_password = $input['new_password'] ?? '';
                    
                    if (empty($user_id) || empty($new_password)) {
                        echo json_encode(array('status' => 'error', 'message' => 'User ID and new password required'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->resetUserPassword($user_id, $new_password);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Reset password for user ID: ' . $user_id);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'update_user':
                    $user_id = $_GET['user_id'] ?? '';
                    
                    if (empty($user_id) || empty($input)) {
                        echo json_encode(array('status' => 'error', 'message' => 'User ID and update data required'));
                        break;
                    }
                    
                    $user = new User();
                    $result = $user->updateProfile($user_id, $input);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Updated user profile for user ID: ' . $user_id);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'update_task_status':
                    $task_id = $_GET['task_id'] ?? '';
                    $status = $input['status'] ?? '';
                    
                    if (empty($task_id) || empty($status)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Task ID and status required'));
                        break;
                    }
                    
                    $task = new Task();
                    $result = $task->updateTaskStatus($task_id, $status);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Updated task status for task ID: ' . $task_id . ' to ' . $status);
                    }
                    
                    echo json_encode($result);
                    break;
                
                default:
                    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
                    break;
            }
            break;
        
        case 'DELETE':
            switch ($action) {
                case 'delete_user':
                    $user_id = $_GET['user_id'] ?? '';
                    
                    if (empty($user_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'User ID required'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->deleteUser($user_id);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Deleted user ID: ' . $user_id);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'delete_backup':
                    $backup_id = $_GET['backup_id'] ?? '';
                    
                    if (empty($backup_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Backup ID required'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->deleteBackup($backup_id);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Deleted backup ID: ' . $backup_id);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'delete_task':
                    $task_id = $_GET['task_id'] ?? '';
                    
                    if (empty($task_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Task ID required'));
                        break;
                    }
                    
                    $task = new Task();
                    $result = $task->deleteTask($task_id);
                    
                    if ($result['status'] === 'success') {
                        logAdminActivity('Deleted task ID: ' . $task_id);
                    }
                    
                    echo json_encode($result);
                    break;
                
                default:
                    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
                    break;
            }
            break;
        
        default:
            echo json_encode(array('status' => 'error', 'message' => 'Method not allowed'));
            break;
    }
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    echo json_encode(array('status' => 'error', 'message' => 'Internal server error'));
}
?>