<?php
/**
 * User API Endpoints
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
require_once '../classes/User.php';
require_once '../classes/Task.php';

// Check user authentication
function checkUserAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
        echo json_encode(array('status' => 'error', 'message' => 'User access required'));
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        session_destroy();
        echo json_encode(array('status' => 'error', 'message' => 'Session expired'));
        exit;
    }
}

// Helper function to log user activity
function logUserActivity($action) {
    try {
        $database = new Database();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "CALL LogUserActivity(:user_id, NULL, 'user', :action, :ip_address, :user_agent)";
        $params = array(
            ':user_id' => $_SESSION['user_id'],
            ':action' => $action,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        );
        
        $database->execute($query, $params);
    } catch (Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    checkUserAuth();
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'get_profile':
                    $user = new User();
                    $result = $user->getUserById($_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                
                case 'get_dashboard_stats':
                    $user = new User();
                    $result = $user->getDashboardStats($_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                
                case 'get_tasks':
                    $status = $_GET['status'] ?? '';
                    $priority = $_GET['priority'] ?? '';
                    
                    $task = new Task();
                    $result = $task->getTasksByUserId($_SESSION['user_id'], $status, $priority);
                    echo json_encode($result);
                    break;
                
                case 'get_task':
                    $task_id = $_GET['task_id'] ?? '';
                    
                    if (empty($task_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Task ID required'));
                        break;
                    }
                    
                    $task = new Task();
                    $task_result = $task->getTaskById($task_id);
                    
                    // Verify task belongs to current user
                    if ($task_result['status'] === 'success' && $task_result['task']['user_id'] != $_SESSION['user_id']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
                        break;
                    }
                    
                    echo json_encode($task_result);
                    break;
                
                case 'get_task_uploads':
                    $task_id = $_GET['task_id'] ?? '';
                    
                    if (empty($task_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Task ID required'));
                        break;
                    }
                    
                    // Verify task belongs to current user
                    $task = new Task();
                    $task_result = $task->getTaskById($task_id);
                    
                    if ($task_result['status'] !== 'success' || $task_result['task']['user_id'] != $_SESSION['user_id']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
                        break;
                    }
                    
                    $result = $task->getTaskUploads($task_id);
                    echo json_encode($result);
                    break;
                
                case 'download_task_file':
                    $upload_id = $_GET['upload_id'] ?? '';
                    $task_id = $_GET['task_id'] ?? '';
                    
                    if (empty($upload_id) || empty($task_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Upload ID and Task ID required'));
                        break;
                    }
                    
                    // Verify task belongs to current user
                    $task = new Task();
                    $task_result = $task->getTaskById($task_id);
                    
                    if ($task_result['status'] !== 'success' || $task_result['task']['user_id'] != $_SESSION['user_id']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
                        break;
                    }
                    
                    $file_result = $task->downloadTaskFile($upload_id, $task_id);
                    
                    if ($file_result['status'] === 'success') {
                        $file = $file_result['file'];
                        
                        header('Content-Type: ' . $file['file_type']);
                        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
                        header('Content-Length: ' . strlen($file['file_data']));
                        
                        echo $file['file_data'];
                        logUserActivity('Downloaded file: ' . $file['file_name']);
                        exit;
                    } else {
                        echo json_encode($file_result);
                    }
                    break;
                
                case 'get_overdue_tasks':
                    $task = new Task();
                    $result = $task->getOverdueTasks($_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                
                case 'get_tasks_due_soon':
                    $days = $_GET['days'] ?? 3;
                    
                    $task = new Task();
                    $result = $task->getTasksDueSoon($days, $_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                
                case 'get_task_statistics':
                    $date_from = $_GET['date_from'] ?? '';
                    $date_to = $_GET['date_to'] ?? '';
                    
                    $task = new Task();
                    $result = $task->getTaskStatistics($_SESSION['user_id'], $date_from, $date_to);
                    echo json_encode($result);
                    break;
                
                case 'export_user_report':
                    $report_type = $_GET['type'] ?? 'all';
                    
                    $task = new Task();
                    $tasks_result = $task->getTasksByUserId($_SESSION['user_id']);
                    
                    if ($tasks_result['status'] !== 'success') {
                        echo json_encode($tasks_result);
                        break;
                    }
                    
                    $tasks = $tasks_result['tasks'];
                    $csv_data = array();
                    
                    if ($report_type === 'weekly' || $report_type === 'all') {
                        $csv_data[] = 'Weekly Performance Report';
                        $csv_data[] = 'Generated: ' . date('Y-m-d H:i:s');
                        $csv_data[] = '';
                        $csv_data[] = 'Task,Status,Priority,Due Date,Completed Date';
                        
                        $week_ago = date('Y-m-d', strtotime('-7 days'));
                        foreach ($tasks as $task) {
                            if (date('Y-m-d', strtotime($task['created_date'])) >= $week_ago || 
                                ($task['completed_date'] && date('Y-m-d', strtotime($task['completed_date'])) >= $week_ago)) {
                                $csv_data[] = '"' . $task['title'] . '","' . $task['status'] . '","' . 
                                            $task['priority'] . '","' . date('Y-m-d', strtotime($task['due_date'])) . '","' . 
                                            ($task['completed_date'] ? date('Y-m-d', strtotime($task['completed_date'])) : '') . '"';
                            }
                        }
                        $csv_data[] = '';
                    }
                    
                    if ($report_type === 'time' || $report_type === 'all') {
                        $csv_data[] = 'Time Tracking Report';
                        $csv_data[] = 'Task,Hours Estimated';
                        
                        foreach ($tasks as $task) {
                            if ($task['status'] === 'completed') {
                                $csv_data[] = '"' . $task['title'] . '",2'; // Estimated 2 hours per task
                            }
                        }
                        $csv_data[] = '';
                    }
                    
                    if ($report_type === 'projects' || $report_type === 'all') {
                        $csv_data[] = 'Project Status Report';
                        $csv_data[] = 'Task,Status,Priority,Due Date';
                        
                        foreach ($tasks as $task) {
                            $csv_data[] = '"' . $task['title'] . '","' . $task['status'] . '","' . 
                                        $task['priority'] . '","' . date('Y-m-d', strtotime($task['due_date'])) . '"';
                        }
                    }
                    
                    $csv_content = implode("\n", $csv_data);
                    
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="user_report_' . $report_type . '_' . date('Y-m-d') . '.csv"');
                    header('Content-Length: ' . strlen($csv_content));
                    
                    echo $csv_content;
                    logUserActivity('Exported ' . $report_type . ' report');
                    exit;
                    break;
                
                default:
                    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
                    break;
            }
            break;
        
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'upload_task_file':
                    $task_id = $_POST['task_id'] ?? '';
                    
                    if (empty($task_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Task ID required'));
                        break;
                    }
                    
                    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                        echo json_encode(array('status' => 'error', 'message' => 'File upload failed'));
                        break;
                    }
                    
                    $file = $_FILES['file'];
                    
                    // Validate file size
                    if ($file['size'] > MAX_FILE_SIZE) {
                        echo json_encode(array('status' => 'error', 'message' => 'File size exceeds limit'));
                        break;
                    }
                    
                    // Validate file type
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($file_extension, ALLOWED_FILE_TYPES)) {
                        echo json_encode(array('status' => 'error', 'message' => 'File type not allowed'));
                        break;
                    }
                    
                    // Verify task belongs to current user
                    $task = new Task();
                    $task_result = $task->getTaskById($task_id);
                    
                    if ($task_result['status'] !== 'success' || $task_result['task']['user_id'] != $_SESSION['user_id']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
                        break;
                    }
                    
                    // Read file data
                    $file_data = array(
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'size' => $file['size'],
                        'data' => file_get_contents($file['tmp_name'])
                    );
                    
                    $result = $task->uploadTaskFile($task_id, $file_data);
                    
                    if ($result['status'] === 'success') {
                        logUserActivity('Uploaded file: ' . $file['name'] . ' for task ID: ' . $task_id);
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
                case 'update_profile':
                    if (empty($input)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Profile data required'));
                        break;
                    }
                    
                    $user = new User();
                    $result = $user->updateProfile($_SESSION['user_id'], $input);
                    
                    if ($result['status'] === 'success') {
                        logUserActivity('Updated profile');
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
                    $result = $task->updateTaskStatus($task_id, $status, $_SESSION['user_id']);
                    
                    if ($result['status'] === 'success') {
                        logUserActivity('Updated task status for task ID: ' . $task_id . ' to ' . $status);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'save_settings':
                    if (empty($input)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Settings data required'));
                        break;
                    }
                    
                    $user = new User();
                    $result = $user->updateProfile($_SESSION['user_id'], array('settings' => $input));
                    
                    if ($result['status'] === 'success') {
                        logUserActivity('Updated user settings');
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'save_notification_preferences':
                    if (empty($input)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Notification preferences required'));
                        break;
                    }
                    
                    $user = new User();
                    $result = $user->updateProfile($_SESSION['user_id'], array('notification_preferences' => $input));
                    
                    if ($result['status'] === 'success') {
                        logUserActivity('Updated notification preferences');
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
                case 'delete_task_upload':
                    $upload_id = $_GET['upload_id'] ?? '';
                    $task_id = $_GET['task_id'] ?? '';
                    
                    if (empty($upload_id) || empty($task_id)) {
                        echo json_encode(array('status' => 'error', 'message' => 'Upload ID and Task ID required'));
                        break;
                    }
                    
                    // Verify task belongs to current user
                    $task = new Task();
                    $task_result = $task->getTaskById($task_id);
                    
                    if ($task_result['status'] !== 'success' || $task_result['task']['user_id'] != $_SESSION['user_id']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
                        break;
                    }
                    
                    $result = $task->deleteTaskUpload($upload_id, $task_id);
                    
                    if ($result['status'] === 'success') {
                        logUserActivity('Deleted file upload ID: ' . $upload_id . ' from task ID: ' . $task_id);
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
    error_log("User API Error: " . $e->getMessage());
    echo json_encode(array('status' => 'error', 'message' => 'Internal server error'));
}
?>