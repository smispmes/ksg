<?php
/**
 * Authentication API Endpoints
 * KSG SMI Performance System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Admin.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Helper function to log activity
function logActivity($user_id, $admin_id, $user_type, $action) {
    try {
        $database = new Database();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "CALL LogUserActivity(:user_id, :admin_id, :user_type, :action, :ip_address, :user_agent)";
        $params = array(
            ':user_id' => $user_type === 'user' ? $user_id : null,
            ':admin_id' => $user_type === 'admin' ? $admin_id : null,
            ':user_type' => $user_type,
            ':action' => $action,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        );
        
        $database->execute($query, $params);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Helper function to validate input
function validateInput($data, $required_fields) {
    $errors = array();
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    
    return $errors;
}

// Helper function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Helper function to validate password strength
function validatePassword($password) {
    $errors = array();
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
    }
    
    if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (REQUIRE_SPECIAL_CHAR && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return $errors;
}

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'user_login':
                    $errors = validateInput($input, array('email', 'password'));
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    if (!validateEmail($input['email'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Invalid email format'));
                        break;
                    }
                    
                    $user = new User();
                    $result = $user->login($input['email'], $input['password']);
                    
                    if ($result['status'] === 'success') {
                        $_SESSION['user_id'] = $result['user']['id'];
                        $_SESSION['user_type'] = 'user';
                        $_SESSION['user_name'] = $result['user']['name'];
                        $_SESSION['login_time'] = time();
                        
                        logActivity($result['user']['id'], null, 'user', 'User login: ' . $result['user']['name']);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'user_register':
                    $errors = validateInput($input, array('name', 'email', 'password', 'confirm_password'));
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    if (!validateEmail($input['email'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Invalid email format'));
                        break;
                    }
                    
                    if ($input['password'] !== $input['confirm_password']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Passwords do not match'));
                        break;
                    }
                    
                    $password_errors = validatePassword($input['password']);
                    if (!empty($password_errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $password_errors)));
                        break;
                    }
                    
                    $user = new User();
                    
                    // Check if email already exists
                    if ($user->emailExists($input['email'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Email already registered'));
                        break;
                    }
                    
                    $user->name = trim($input['name']);
                    $user->email = trim($input['email']);
                    $user->password = $input['password'];
                    $user->phone = trim($input['phone'] ?? '');
                    $user->department = $input['department'] ?? null;
                    $user->job_title = trim($input['job_title'] ?? '');
                    $user->created_by = 'Self Registration';
                    
                    $result = $user->register();
                    
                    if ($result['status'] === 'success') {
                        logActivity($result['user_id'], null, 'user', 'User registration: ' . $user->name);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'admin_login':
                    $errors = validateInput($input, array('email', 'password', 'index_code'));
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    if (!validateEmail($input['email'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Invalid email format'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->login($input['email'], $input['password'], $input['index_code']);
                    
                    if ($result['status'] === 'success') {
                        $_SESSION['admin_id'] = $result['admin']['id'];
                        $_SESSION['user_type'] = 'admin';
                        $_SESSION['admin_name'] = $result['admin']['name'];
                        $_SESSION['login_time'] = time();
                        
                        logActivity(null, $result['admin']['id'], 'admin', 'Admin login: ' . $result['admin']['name']);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'admin_register':
                    $errors = validateInput($input, array('name', 'email', 'password', 'confirm_password', 'index_code'));
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    if ($input['index_code'] !== ADMIN_INDEX_CODE) {
                        echo json_encode(array('status' => 'error', 'message' => 'Invalid admin index code'));
                        break;
                    }
                    
                    if (!validateEmail($input['email'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Invalid email format'));
                        break;
                    }
                    
                    if ($input['password'] !== $input['confirm_password']) {
                        echo json_encode(array('status' => 'error', 'message' => 'Passwords do not match'));
                        break;
                    }
                    
                    $password_errors = validatePassword($input['password']);
                    if (!empty($password_errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $password_errors)));
                        break;
                    }
                    
                    $admin = new Admin();
                    
                    // Check if email already exists
                    if ($admin->emailExists($input['email'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Admin email already registered'));
                        break;
                    }
                    
                    $admin->name = trim($input['name']);
                    $admin->email = trim($input['email']);
                    $admin->password = $input['password'];
                    $admin->phone = trim($input['phone'] ?? '');
                    
                    $result = $admin->register();
                    
                    if ($result['status'] === 'success') {
                        logActivity(null, $result['admin_id'], 'admin', 'Admin registration: ' . $admin->name);
                    }
                    
                    echo json_encode($result);
                    break;
                
                case 'logout':
                    $user_type = $_SESSION['user_type'] ?? '';
                    $user_name = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Unknown';
                    
                    if ($user_type === 'user') {
                        logActivity($_SESSION['user_id'] ?? null, null, 'user', 'User logout: ' . $user_name);
                    } elseif ($user_type === 'admin') {
                        logActivity(null, $_SESSION['admin_id'] ?? null, 'admin', 'Admin logout: ' . $user_name);
                    }
                    
                    session_destroy();
                    echo json_encode(array('status' => 'success', 'message' => 'Logged out successfully'));
                    break;
                
                case 'change_password':
                    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Not authenticated'));
                        break;
                    }
                    
                    $errors = validateInput($input, array('current_password', 'new_password', 'confirm_password'));
                    if (!empty($errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors)));
                        break;
                    }
                    
                    if ($input['new_password'] !== $input['confirm_password']) {
                        echo json_encode(array('status' => 'error', 'message' => 'New passwords do not match'));
                        break;
                    }
                    
                    $password_errors = validatePassword($input['new_password']);
                    if (!empty($password_errors)) {
                        echo json_encode(array('status' => 'error', 'message' => implode(', ', $password_errors)));
                        break;
                    }
                    
                    if ($_SESSION['user_type'] === 'user') {
                        $user = new User();
                        $result = $user->changePassword($_SESSION['user_id'], $input['current_password'], $input['new_password']);
                        
                        if ($result['status'] === 'success') {
                            logActivity($_SESSION['user_id'], null, 'user', 'Password changed');
                        }
                    } else {
                        $admin = new Admin();
                        $result = $admin->changePassword($_SESSION['admin_id'], $input['current_password'], $input['new_password']);
                        
                        if ($result['status'] === 'success') {
                            logActivity(null, $_SESSION['admin_id'], 'admin', 'Password changed');
                        }
                    }
                    
                    echo json_encode($result);
                    break;
                
                default:
                    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
                    break;
            }
            break;
        
        case 'GET':
            switch ($action) {
                case 'check_session':
                    if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
                        // Check session timeout
                        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
                            session_destroy();
                            echo json_encode(array('status' => 'error', 'message' => 'Session expired'));
                        } else {
                            echo json_encode(array(
                                'status' => 'success',
                                'user_type' => $_SESSION['user_type'],
                                'user_id' => $_SESSION['user_id'] ?? null,
                                'admin_id' => $_SESSION['admin_id'] ?? null,
                                'user_name' => $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? ''
                            ));
                        }
                    } else {
                        echo json_encode(array('status' => 'error', 'message' => 'Not authenticated'));
                    }
                    break;
                
                case 'get_user_profile':
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Not authenticated'));
                        break;
                    }
                    
                    $user = new User();
                    $result = $user->getUserById($_SESSION['user_id']);
                    echo json_encode($result);
                    break;
                
                case 'get_admin_profile':
                    if (!isset($_SESSION['admin_id'])) {
                        echo json_encode(array('status' => 'error', 'message' => 'Not authenticated'));
                        break;
                    }
                    
                    $admin = new Admin();
                    $result = $admin->getAdminById($_SESSION['admin_id']);
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
    error_log("Auth API Error: " . $e->getMessage());
    echo json_encode(array('status' => 'error', 'message' => 'Internal server error'));
}
?>