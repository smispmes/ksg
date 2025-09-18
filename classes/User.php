<?php
/**
 * User Class - Handles user operations
 * KSG SMI Performance System
 */

require_once '../config/database.php';

class User {
    private $db;
    private $table_name = "users";
    
    public $id;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $department;
    public $job_title;
    public $role;
    public $profile_picture;
    public $settings;
    public $notification_preferences;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $last_login;
    public $status;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database;
    }
    
    // User registration
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, phone, department, job_title, role, created_by) 
                  VALUES (:name, :email, :password, :phone, :department, :job_title, :role, :created_by)";
        
        $params = array(
            ':name' => $this->name,
            ':email' => $this->email,
            ':password' => password_hash($this->password, PASSWORD_DEFAULT),
            ':phone' => $this->phone,
            ':department' => $this->department,
            ':job_title' => $this->job_title,
            ':role' => $this->role ?: 'user',
            ':created_by' => $this->created_by
        );
        
        try {
            $this->id = $this->db->insert($query, $params);
            return array('status' => 'success', 'message' => 'User registered successfully', 'user_id' => $this->id);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage());
        }
    }
    
    // User login
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND status = 'active'";
        
        try {
            $user = $this->db->fetchOne($query, array(':email' => $email));
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Remove password from returned data
                unset($user['password']);
                
                return array('status' => 'success', 'user' => $user);
            } else {
                return array('status' => 'error', 'message' => 'Invalid email or password');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Login failed: ' . $e->getMessage());
        }
    }
    
    // Update last login
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $this->db->execute($query, array(':id' => $user_id));
    }
    
    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $user = $this->db->fetchOne($query, array(':id' => $id));
            if ($user) {
                unset($user['password']);
                return array('status' => 'success', 'user' => $user);
            } else {
                return array('status' => 'error', 'message' => 'User not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching user: ' . $e->getMessage());
        }
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        
        try {
            $user = $this->db->fetchOne($query, array(':email' => $email));
            if ($user) {
                unset($user['password']);
                return array('status' => 'success', 'user' => $user);
            } else {
                return array('status' => 'error', 'message' => 'User not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching user: ' . $e->getMessage());
        }
    }
    
    // Get all users
    public function getAllUsers($search = '', $department = '', $status = 'active') {
        $query = "SELECT id, name, email, phone, department, job_title, role, created_at, last_login, status 
                  FROM " . $this->table_name . " WHERE 1=1";
        $params = array();
        
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($department)) {
            $query .= " AND department = :department";
            $params[':department'] = $department;
        }
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY name ASC";
        
        try {
            $users = $this->db->fetchAll($query, $params);
            return array('status' => 'success', 'users' => $users);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching users: ' . $e->getMessage());
        }
    }
    
    // Update user profile
    public function updateProfile($user_id, $data) {
        $allowed_fields = array('name', 'phone', 'department', 'job_title', 'profile_picture', 'settings', 'notification_preferences');
        $set_clauses = array();
        $params = array(':id' => $user_id);
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $set_clauses[] = "$field = :$field";
                if (in_array($field, array('settings', 'notification_preferences'))) {
                    $params[":$field"] = json_encode($value);
                } else {
                    $params[":$field"] = $value;
                }
            }
        }
        
        if (empty($set_clauses)) {
            return array('status' => 'error', 'message' => 'No valid fields to update');
        }
        
        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $set_clauses) . " WHERE id = :id";
        
        try {
            $affected_rows = $this->db->execute($query, $params);
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Profile updated successfully');
            } else {
                return array('status' => 'error', 'message' => 'No changes made or user not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Update failed: ' . $e->getMessage());
        }
    }
    
    // Change password
    public function changePassword($user_id, $current_password, $new_password) {
        // First verify current password
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $user = $this->db->fetchOne($query, array(':id' => $user_id));
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                return array('status' => 'error', 'message' => 'Current password is incorrect');
            }
            
            // Validate new password
            if (!$this->validatePassword($new_password)) {
                return array('status' => 'error', 'message' => 'New password does not meet requirements');
            }
            
            // Update password
            $update_query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
            $params = array(
                ':password' => password_hash($new_password, PASSWORD_DEFAULT),
                ':id' => $user_id
            );
            
            $affected_rows = $this->db->execute($update_query, $params);
            
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Password changed successfully');
            } else {
                return array('status' => 'error', 'message' => 'Password change failed');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Password change failed: ' . $e->getMessage());
        }
    }
    
    // Reset password (admin function)
    public function resetPassword($user_id, $new_password) {
        if (!$this->validatePassword($new_password)) {
            return array('status' => 'error', 'message' => 'Password does not meet requirements');
        }
        
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $params = array(
            ':password' => password_hash($new_password, PASSWORD_DEFAULT),
            ':id' => $user_id
        );
        
        try {
            $affected_rows = $this->db->execute($query, $params);
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Password reset successfully');
            } else {
                return array('status' => 'error', 'message' => 'User not found or password reset failed');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Password reset failed: ' . $e->getMessage());
        }
    }
    
    // Delete user
    public function deleteUser($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $affected_rows = $this->db->execute($query, array(':id' => $user_id));
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'User deleted successfully');
            } else {
                return array('status' => 'error', 'message' => 'User not found or deletion failed');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage());
        }
    }
    
    // Update user status
    public function updateStatus($user_id, $status) {
        $valid_statuses = array('active', 'inactive', 'suspended');
        if (!in_array($status, $valid_statuses)) {
            return array('status' => 'error', 'message' => 'Invalid status');
        }
        
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $params = array(':status' => $status, ':id' => $user_id);
        
        try {
            $affected_rows = $this->db->execute($query, $params);
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'User status updated successfully');
            } else {
                return array('status' => 'error', 'message' => 'User not found or status update failed');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Status update failed: ' . $e->getMessage());
        }
    }
    
    // Get user dashboard statistics
    public function getDashboardStats($user_id) {
        $query = "CALL GetUserDashboardStats(:user_id)";
        
        try {
            $stats = $this->db->fetchOne($query, array(':user_id' => $user_id));
            return array('status' => 'success', 'stats' => $stats);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching dashboard stats: ' . $e->getMessage());
        }
    }
    
    // Validate password strength
    private function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        
        if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if (REQUIRE_SPECIAL_CHAR && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    // Check if email exists
    public function emailExists($email, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $params = array(':email' => $email);
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
            $params[':exclude_id'] = $exclude_id;
        }
        
        try {
            $result = $this->db->fetchOne($query, $params);
            return $result ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get users by department
    public function getUsersByDepartment($department) {
        $query = "SELECT id, name, email, job_title FROM " . $this->table_name . " 
                  WHERE department = :department AND status = 'active' ORDER BY name";
        
        try {
            $users = $this->db->fetchAll($query, array(':department' => $department));
            return array('status' => 'success', 'users' => $users);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching users: ' . $e->getMessage());
        }
    }
    
    // Get user count by status
    public function getUserCountByStatus() {
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " GROUP BY status";
        
        try {
            $counts = $this->db->fetchAll($query);
            return array('status' => 'success', 'counts' => $counts);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching user counts: ' . $e->getMessage());
        }
    }
}
?>