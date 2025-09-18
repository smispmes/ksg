<?php
/**
 * Admin Class - Handles admin operations
 * KSG SMI Performance System
 */

require_once '../config/database.php';

class Admin {
    private $db;
    private $table_name = "admins";
    
    public $id;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $profile_picture;
    public $created_at;
    public $updated_at;
    public $last_login;
    public $status;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database;
    }
    
    // Admin registration
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, phone) 
                  VALUES (:name, :email, :password, :phone)";
        
        $params = array(
            ':name' => $this->name,
            ':email' => $this->email,
            ':password' => password_hash($this->password, PASSWORD_DEFAULT),
            ':phone' => $this->phone
        );
        
        try {
            $this->id = $this->db->insert($query, $params);
            return array('status' => 'success', 'message' => 'Admin registered successfully', 'admin_id' => $this->id);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage());
        }
    }
    
    // Admin login
    public function login($email, $password, $index_code) {
        // Verify index code first
        if ($index_code !== ADMIN_INDEX_CODE) {
            return array('status' => 'error', 'message' => 'Invalid admin index code');
        }
        
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND status = 'active'";
        
        try {
            $admin = $this->db->fetchOne($query, array(':email' => $email));
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Update last login
                $this->updateLastLogin($admin['id']);
                
                // Remove password from returned data
                unset($admin['password']);
                
                return array('status' => 'success', 'admin' => $admin);
            } else {
                return array('status' => 'error', 'message' => 'Invalid email or password');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Login failed: ' . $e->getMessage());
        }
    }
    
    // Update last login
    private function updateLastLogin($admin_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $this->db->execute($query, array(':id' => $admin_id));
    }
    
    // Get admin by ID
    public function getAdminById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $admin = $this->db->fetchOne($query, array(':id' => $id));
            if ($admin) {
                unset($admin['password']);
                return array('status' => 'success', 'admin' => $admin);
            } else {
                return array('status' => 'error', 'message' => 'Admin not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching admin: ' . $e->getMessage());
        }
    }
    
    // Get all admins
    public function getAllAdmins() {
        $query = "SELECT id, name, email, phone, created_at, last_login, status 
                  FROM " . $this->table_name . " ORDER BY name ASC";
        
        try {
            $admins = $this->db->fetchAll($query);
            return array('status' => 'success', 'admins' => $admins);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching admins: ' . $e->getMessage());
        }
    }
    
    // Update admin profile
    public function updateProfile($admin_id, $data) {
        $allowed_fields = array('name', 'phone', 'profile_picture');
        $set_clauses = array();
        $params = array(':id' => $admin_id);
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $set_clauses[] = "$field = :$field";
                $params[":$field"] = $value;
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
                return array('status' => 'error', 'message' => 'No changes made or admin not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Update failed: ' . $e->getMessage());
        }
    }
    
    // Change password
    public function changePassword($admin_id, $current_password, $new_password) {
        // First verify current password
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $admin = $this->db->fetchOne($query, array(':id' => $admin_id));
            
            if (!$admin || !password_verify($current_password, $admin['password'])) {
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
                ':id' => $admin_id
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
    
    // Get system analytics
    public function getSystemAnalytics() {
        $query = "CALL GetSystemAnalytics()";
        
        try {
            $analytics = $this->db->fetchOne($query);
            return array('status' => 'success', 'analytics' => $analytics);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching analytics: ' . $e->getMessage());
        }
    }
    
    // Get user management data
    public function getUserManagementData($search = '', $department = '') {
        require_once 'User.php';
        $user = new User();
        return $user->getAllUsers($search, $department);
    }
    
    // Create new user (admin function)
    public function createUser($userData) {
        require_once 'User.php';
        $user = new User();
        
        // Set user properties
        $user->name = $userData['name'];
        $user->email = $userData['email'];
        $user->password = $userData['password'];
        $user->phone = $userData['phone'] ?? null;
        $user->department = $userData['department'] ?? null;
        $user->job_title = $userData['job_title'] ?? null;
        $user->role = $userData['role'] ?? 'user';
        $user->created_by = $userData['created_by'] ?? 'Admin';
        
        return $user->register();
    }
    
    // Delete user (admin function)
    public function deleteUser($user_id) {
        require_once 'User.php';
        $user = new User();
        return $user->deleteUser($user_id);
    }
    
    // Reset user password (admin function)
    public function resetUserPassword($user_id, $new_password) {
        require_once 'User.php';
        $user = new User();
        return $user->resetPassword($user_id, $new_password);
    }
    
    // Get security settings
    public function getSecuritySettings() {
        $query = "SELECT setting_name, setting_value FROM security_settings";
        
        try {
            $settings = $this->db->fetchAll($query);
            $result = array();
            
            foreach ($settings as $setting) {
                $result[$setting['setting_name']] = json_decode($setting['setting_value'], true);
            }
            
            return array('status' => 'success', 'settings' => $result);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching security settings: ' . $e->getMessage());
        }
    }
    
    // Update security settings
    public function updateSecuritySettings($settings, $admin_id) {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $setting_name => $setting_value) {
                $query = "INSERT INTO security_settings (setting_name, setting_value, updated_by) 
                          VALUES (:name, :value, :admin_id)
                          ON DUPLICATE KEY UPDATE 
                          setting_value = :value, updated_by = :admin_id, updated_at = NOW()";
                
                $params = array(
                    ':name' => $setting_name,
                    ':value' => json_encode($setting_value),
                    ':admin_id' => $admin_id
                );
                
                $this->db->execute($query, $params);
            }
            
            $this->db->commit();
            return array('status' => 'success', 'message' => 'Security settings updated successfully');
        } catch (Exception $e) {
            $this->db->rollback();
            return array('status' => 'error', 'message' => 'Error updating security settings: ' . $e->getMessage());
        }
    }
    
    // Get access logs
    public function getAccessLogs($limit = 50, $user_type = '', $date_from = '', $date_to = '') {
        $query = "SELECT al.*, u.name as user_name, a.name as admin_name 
                  FROM access_logs al
                  LEFT JOIN users u ON al.user_id = u.id
                  LEFT JOIN admins a ON al.admin_id = a.id
                  WHERE 1=1";
        $params = array();
        
        if (!empty($user_type)) {
            $query .= " AND al.user_type = :user_type";
            $params[':user_type'] = $user_type;
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(al.timestamp) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(al.timestamp) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $query .= " ORDER BY al.timestamp DESC LIMIT :limit";
        $params[':limit'] = (int)$limit;
        
        try {
            $logs = $this->db->fetchAll($query, $params);
            return array('status' => 'success', 'logs' => $logs);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching access logs: ' . $e->getMessage());
        }
    }
    
    // Create system backup
    public function createSystemBackup($admin_id) {
        try {
            // Get all data for backup
            $backup_data = array(
                'users' => $this->db->fetchAll("SELECT * FROM users"),
                'admins' => $this->db->fetchAll("SELECT * FROM admins"),
                'user_tasks' => $this->db->fetchAll("SELECT * FROM user_tasks"),
                'task_uploads' => $this->db->fetchAll("SELECT id, task_id, file_name, file_type, file_size, uploaded_at FROM task_uploads"),
                'security_settings' => $this->db->fetchAll("SELECT * FROM security_settings"),
                'access_logs' => $this->db->fetchAll("SELECT * FROM access_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
                'task_categories' => $this->db->fetchAll("SELECT * FROM task_categories"),
                'task_templates' => $this->db->fetchAll("SELECT * FROM task_templates")
            );
            
            $backup_json = json_encode($backup_data);
            $backup_name = 'KSG_SMI_Backup_' . date('Y-m-d_H-i-s');
            $file_size = strlen($backup_json);
            
            $query = "INSERT INTO system_backups (backup_name, backup_data, created_by, file_size) 
                      VALUES (:name, :data, :admin_id, :size)";
            
            $params = array(
                ':name' => $backup_name,
                ':data' => $backup_json,
                ':admin_id' => $admin_id,
                ':size' => $file_size
            );
            
            $backup_id = $this->db->insert($query, $params);
            
            return array(
                'status' => 'success', 
                'message' => 'Backup created successfully',
                'backup_id' => $backup_id,
                'backup_name' => $backup_name
            );
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Backup creation failed: ' . $e->getMessage());
        }
    }
    
    // Get system backups
    public function getSystemBackups($limit = 10) {
        $query = "SELECT sb.*, a.name as created_by_name 
                  FROM system_backups sb
                  LEFT JOIN admins a ON sb.created_by = a.id
                  ORDER BY sb.created_at DESC LIMIT :limit";
        
        try {
            $backups = $this->db->fetchAll($query, array(':limit' => (int)$limit));
            return array('status' => 'success', 'backups' => $backups);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching backups: ' . $e->getMessage());
        }
    }
    
    // Download backup
    public function downloadBackup($backup_id) {
        $query = "SELECT backup_name, backup_data FROM system_backups WHERE id = :id";
        
        try {
            $backup = $this->db->fetchOne($query, array(':id' => $backup_id));
            if ($backup) {
                return array('status' => 'success', 'backup' => $backup);
            } else {
                return array('status' => 'error', 'message' => 'Backup not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error downloading backup: ' . $e->getMessage());
        }
    }
    
    // Delete backup
    public function deleteBackup($backup_id) {
        $query = "DELETE FROM system_backups WHERE id = :id";
        
        try {
            $affected_rows = $this->db->execute($query, array(':id' => $backup_id));
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Backup deleted successfully');
            } else {
                return array('status' => 'error', 'message' => 'Backup not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error deleting backup: ' . $e->getMessage());
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
}
?>