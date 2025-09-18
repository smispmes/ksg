<?php
/**
 * Task Class - Handles task operations
 * KSG SMI Performance System
 */

require_once '../config/database.php';

class Task {
    private $db;
    private $table_name = "user_tasks";
    
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $priority;
    public $status;
    public $due_date;
    public $created_date;
    public $completed_date;
    public $assigned_by;
    public $assigned_by_id;
    public $instructions;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database;
    }
    // --- INTEGRATED assignPredefinedTask METHOD (v5) ---
    /**
     * Assign a predefined task to a user, recording the admin.
     *
     * @param string $category
     * @param string $task
     * @param int $user_id
     * @param int $admin_id
     * @param string|null $due_date
     * @param string $priority
     * @param string|null $instructions
     * @return array
     */
    public function assignPredefinedTask($category, $task, $user_id, $admin_id, $due_date = null, $priority = 'medium', $instructions = '') {
        // Get admin name
        $admin_name = $this->getUserNameById($admin_id) ?: 'Admin';
        // Optionally get standard description for the task
        $description = $this->getPredefinedDescription($category, $task);

        $this->user_id = $user_id;
        $this->title = $task;
        $this->description = $description;
        $this->priority = $priority;
        $this->status = 'pending';
        $this->due_date = $due_date;
        $this->assigned_by = $admin_name;
        $this->assigned_by_id = $admin_id;
        $this->instructions = $instructions;

        return $this->createTask();
    }

    /**
     * Returns a description for a task based on category/task.
     */
    public function getPredefinedDescription($category, $task) {
        $desc = [
            "Financial Stewardship and Discipline" => [
                "Revenue" => "Monitor and optimize revenue generation activities.",
                "Debt Management" => "Manage and track organizational debts.",
                "Pending Bills" => "Review and process all pending bills.",
                "Zero Fault Audits" => "Conduct audits to ensure zero faults in financial records."
            ],
            "Service Delivery" => [
                "Implementation of Citizens' Service Delivery Charter" => "Ensure service delivery charter implementation.",
                "Resolution of Public Complaints" => "Resolve public complaints efficiently."
            ],
            "Core Mandate" => [
                "Review existing training programs." => "Evaluate and update current training programs.",
                "Develop and roll out new training programs." => "Create and implement new training programs.",
                "Undertake consultancy and research activities." => "Conduct consultancy and research.",
                "Organize and host national symposia or conferences." => "Plan and host national events.",
                "Improve productivity." => "Implement productivity improvement measures.",
                "Manage the customer experience and satisfaction score." => "Track and improve customer satisfaction.",
                "Conduct a training needs assessment." => "Assess staff training needs.",
                "Mobilize participants for training." => "Coordinate training participation.",
                "Convert and offer existing programs online." => "Digitize programs for online delivery.",
                "Carry out program and facilitator evaluations." => "Evaluate programs and facilitators.",
                "Identify and implement innovation and creativity initiatives." => "Foster innovation and creativity.",
                "Institutionalize Performance Management Culture" => "Embed performance management in the institute."
            ],
            "Administration and Infrastructure" => [
                "Operationalize digitalized processes." => "Implement digital processes for efficiency.",
                "Implement a risk register." => "Develop and maintain a risk register.",
                "Implement Quality Management Systems." => "Set up and run quality management systems.",
                "Implementation of Presidential Directives" => "Carry out presidential directives."
            ],
            "Cross-Cutting Issues" => [
                "Youth Internships, Industrial Attachment and Apprenticeship" => "Coordinate youth internships and attachments.",
                "Competence Development" => "Promote competence development.",
                "National Cohesion and Values" => "Foster national cohesion and values."
            ]
        ];
        return $desc[$category][$task] ?? $task;
    }

    /**
     * Utility: get user name by ID (for assigned_by).
     */
    private function getUserNameById($user_id) {
        try {
            $row = $this->db->fetchOne("SELECT name FROM users WHERE id = :id", array(':id' => $user_id));
            return $row && isset($row['name']) ? $row['name'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    // Create new task
    public function createTask() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, title, description, priority, status, due_date, assigned_by, assigned_by_id, instructions) 
                  VALUES (:user_id, :title, :description, :priority, :status, :due_date, :assigned_by, :assigned_by_id, :instructions)";
        
        $params = array(
            ':user_id' => $this->user_id,
            ':title' => $this->title,
            ':description' => $this->description,
            ':priority' => $this->priority ?: 'medium',
            ':status' => $this->status ?: 'pending',
            ':due_date' => $this->due_date,
            ':assigned_by' => $this->assigned_by,
            ':assigned_by_id' => $this->assigned_by_id,
            ':instructions' => $this->instructions
        );
        
        try {
            $this->id = $this->db->insert($query, $params);
            return array('status' => 'success', 'message' => 'Task created successfully', 'task_id' => $this->id);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Task creation failed: ' . $e->getMessage());
        }
    }
    
    // Get tasks by user ID
    public function getTasksByUserId($user_id, $status = '', $priority = '') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $params = array(':user_id' => $user_id);
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($priority)) {
            $query .= " AND priority = :priority";
            $params[':priority'] = $priority;
        }
        
        $query .= " ORDER BY due_date ASC, priority DESC";
        
        try {
            $tasks = $this->db->fetchAll($query, $params);
            return array('status' => 'success', 'tasks' => $tasks);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching tasks: ' . $e->getMessage());
        }
    }
    
    // Get task by ID
    public function getTaskById($task_id) {
        $query = "SELECT ut.*, u.name as user_name, u.email as user_email 
                  FROM " . $this->table_name . " ut
                  LEFT JOIN users u ON ut.user_id = u.id
                  WHERE ut.id = :id";
        
        try {
            $task = $this->db->fetchOne($query, array(':id' => $task_id));
            if ($task) {
                return array('status' => 'success', 'task' => $task);
            } else {
                return array('status' => 'error', 'message' => 'Task not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching task: ' . $e->getMessage());
        }
    }
    
    // Update task status
    public function updateTaskStatus($task_id, $status, $user_id = null) {
        $valid_statuses = array('pending', 'in_progress', 'completed', 'overdue');
        if (!in_array($status, $valid_statuses)) {
            return array('status' => 'error', 'message' => 'Invalid status');
        }
        
        $query = "UPDATE " . $this->table_name . " SET status = :status";
        $params = array(':status' => $status, ':id' => $task_id);
        
        // If marking as completed, set completion date
        if ($status === 'completed') {
            $query .= ", completed_date = NOW()";
        }
        
        $query .= " WHERE id = :id";
        
        // If user_id is provided, ensure user can only update their own tasks
        if ($user_id) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        try {
            $affected_rows = $this->db->execute($query, $params);
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Task status updated successfully');
            } else {
                return array('status' => 'error', 'message' => 'Task not found or update failed');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Status update failed: ' . $e->getMessage());
        }
    }
    
    // Update task
    public function updateTask($task_id, $data, $user_id = null) {
        $allowed_fields = array('title', 'description', 'priority', 'status', 'due_date', 'instructions');
        $set_clauses = array();
        $params = array(':id' => $task_id);
        
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
        
        // If user_id is provided, ensure user can only update their own tasks
        if ($user_id) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        try {
            $affected_rows = $this->db->execute($query, $params);
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Task updated successfully');
            } else {
                return array('status' => 'error', 'message' => 'Task not found or no changes made');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Update failed: ' . $e->getMessage());
        }
    }
    
    // Delete task
    public function deleteTask($task_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $affected_rows = $this->db->execute($query, array(':id' => $task_id));
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'Task deleted successfully');
            } else {
                return array('status' => 'error', 'message' => 'Task not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage());
        }
    }
    
    // Get all tasks (admin view)
    public function getAllTasks($status = '', $priority = '', $user_id = '', $date_from = '', $date_to = '') {
        $query = "SELECT ut.*, u.name as user_name, u.email as user_email, u.department 
                  FROM " . $this->table_name . " ut
                  LEFT JOIN users u ON ut.user_id = u.id
                  WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND ut.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($priority)) {
            $query .= " AND ut.priority = :priority";
            $params[':priority'] = $priority;
        }
        
        if (!empty($user_id)) {
            $query .= " AND ut.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(ut.created_date) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(ut.created_date) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $query .= " ORDER BY ut.created_date DESC";
        
        try {
            $tasks = $this->db->fetchAll($query, $params);
            return array('status' => 'success', 'tasks' => $tasks);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching tasks: ' . $e->getMessage());
        }
    }
    
    // Get overdue tasks
    public function getOverdueTasks($user_id = null) {
        $query = "SELECT ut.*, u.name as user_name, u.email as user_email 
                  FROM " . $this->table_name . " ut
                  LEFT JOIN users u ON ut.user_id = u.id
                  WHERE ut.status = 'pending' AND ut.due_date < NOW()";
        $params = array();
        
        if ($user_id) {
            $query .= " AND ut.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        $query .= " ORDER BY ut.due_date ASC";
        
        try {
            $tasks = $this->db->fetchAll($query, $params);
            return array('status' => 'success', 'tasks' => $tasks);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching overdue tasks: ' . $e->getMessage());
        }
    }
    
    // Get task statistics
    public function getTaskStatistics($user_id = null, $date_from = '', $date_to = '') {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'pending' AND due_date < NOW() THEN 1 ELSE 0 END) as overdue_tasks,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tasks,
                    SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_priority_tasks,
                    SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_priority_tasks
                  FROM " . $this->table_name . " WHERE 1=1";
        $params = array();
        
        if ($user_id) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(created_date) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(created_date) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        try {
            $stats = $this->db->fetchOne($query, $params);
            
            // Calculate completion rate
            if ($stats['total_tasks'] > 0) {
                $stats['completion_rate'] = round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 2);
            } else {
                $stats['completion_rate'] = 0;
            }
            
            return array('status' => 'success', 'statistics' => $stats);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching statistics: ' . $e->getMessage());
        }
    }
    
    // Get task templates
    public function getTaskTemplates() {
        $query = "SELECT tt.*, tc.name as category_name 
                  FROM task_templates tt
                  LEFT JOIN task_categories tc ON tt.category_id = tc.id
                  ORDER BY tc.name, tt.title";
        
        try {
            $templates = $this->db->fetchAll($query);
            return array('status' => 'success', 'templates' => $templates);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching task templates: ' . $e->getMessage());
        }
    }
    public function getAssignableTaskTemplates() {
    return [
        "Financial Stewardship and Discipline" => [
            "Revenue",
            "Debt Management",
            "Pending Bills",
            "Zero Fault Audits"
        ],
        "Service Delivery" => [
            "Implementation of Citizens' Service Delivery Charter",
            "Resolution of Public Complaints"
        ],
        "Core Mandate" => [
            // Project Aligned to Corporate Performance Contract
            "Review existing training programs.",
            "Develop and roll out new training programs.",
            "Undertake consultancy and research activities.",
            "Organize and host national symposia or conferences.",
            "Improve productivity.",
            "Manage the customer experience and satisfaction score.",
            // Projects Aligned to Institute Work Plan
            "Conduct a training needs assessment.",
            "Mobilize participants for training.",
            "Convert and offer existing programs online.",
            "Carry out program and facilitator evaluations.",
            "Identify and implement innovation and creativity initiatives.",
            "Institutionalize Performance Management Culture"
        ],
        "Administration and Infrastructure" => [
            // Project Aligned to Infrastructure Improvement
            "Operationalize digitalized processes.",
            // Projects Aligned to Improvement of Internal Processes
            "Implement a risk register.",
            "Implement Quality Management Systems.",
            // Implementation of Presidential Directives
            "Implementation of Presidential Directives"
        ],
        "Cross-Cutting Issues" => [
            "Youth Internships, Industrial Attachment and Apprenticeship",
            "Competence Development",
            "National Cohesion and Values"
        ]
    ];
}
    // Get task categories
    public function getTaskCategories() {
        $query = "SELECT * FROM task_categories ORDER BY name";
        
        try {
            $categories = $this->db->fetchAll($query);
            return array('status' => 'success', 'categories' => $categories);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching task categories: ' . $e->getMessage());
        }
    }
    
    // Upload task file
    public function uploadTaskFile($task_id, $file_data) {
        $query = "INSERT INTO task_uploads (task_id, file_name, file_type, file_size, file_data) 
                  VALUES (:task_id, :file_name, :file_type, :file_size, :file_data)";
        
        $params = array(
            ':task_id' => $task_id,
            ':file_name' => $file_data['name'],
            ':file_type' => $file_data['type'],
            ':file_size' => $file_data['size'],
            ':file_data' => $file_data['data']
        );
        
        try {
            $upload_id = $this->db->insert($query, $params);
            return array('status' => 'success', 'message' => 'File uploaded successfully', 'upload_id' => $upload_id);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'File upload failed: ' . $e->getMessage());
        }
    }
    
    // Get task uploads
    public function getTaskUploads($task_id) {
        $query = "SELECT id, file_name, file_type, file_size, uploaded_at 
                  FROM task_uploads WHERE task_id = :task_id ORDER BY uploaded_at DESC";
        
        try {
            $uploads = $this->db->fetchAll($query, array(':task_id' => $task_id));
            return array('status' => 'success', 'uploads' => $uploads);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching uploads: ' . $e->getMessage());
        }
    }
    
    // Download task file
    public function downloadTaskFile($upload_id, $task_id = null) {
        $query = "SELECT file_name, file_type, file_data FROM task_uploads WHERE id = :id";
        $params = array(':id' => $upload_id);
        
        if ($task_id) {
            $query .= " AND task_id = :task_id";
            $params[':task_id'] = $task_id;
        }
        
        try {
            $file = $this->db->fetchOne($query, $params);
            if ($file) {
                return array('status' => 'success', 'file' => $file);
            } else {
                return array('status' => 'error', 'message' => 'File not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error downloading file: ' . $e->getMessage());
        }
    }
    
    // Delete task upload
    public function deleteTaskUpload($upload_id, $task_id = null) {
        $query = "DELETE FROM task_uploads WHERE id = :id";
        $params = array(':id' => $upload_id);
        
        if ($task_id) {
            $query .= " AND task_id = :task_id";
            $params[':task_id'] = $task_id;
        }
        
        try {
            $affected_rows = $this->db->execute($query, $params);
            if ($affected_rows > 0) {
                return array('status' => 'success', 'message' => 'File deleted successfully');
            } else {
                return array('status' => 'error', 'message' => 'File not found');
            }
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error deleting file: ' . $e->getMessage());
        }
    }
    
    // Get recent task assignments (admin view)
    public function getRecentAssignments($limit = 10) {
        $query = "SELECT ut.*, u.name as user_name, u.email as user_email 
                  FROM " . $this->table_name . " ut
                  LEFT JOIN users u ON ut.user_id = u.id
                  ORDER BY ut.created_date DESC LIMIT :limit";
        
        try {
            $assignments = $this->db->fetchAll($query, array(':limit' => (int)$limit));
            return array('status' => 'success', 'assignments' => $assignments);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching recent assignments: ' . $e->getMessage());
        }
    }
    
    // Get tasks due soon
    public function getTasksDueSoon($days = 3, $user_id = null) {
        $query = "SELECT ut.*, u.name as user_name, u.email as user_email 
                  FROM " . $this->table_name . " ut
                  LEFT JOIN users u ON ut.user_id = u.id
                  WHERE ut.status = 'pending' 
                  AND ut.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)";
        $params = array(':days' => $days);
        
        if ($user_id) {
            $query .= " AND ut.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        $query .= " ORDER BY ut.due_date ASC";
        
        try {
            $tasks = $this->db->fetchAll($query, $params);
            return array('status' => 'success', 'tasks' => $tasks);
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => 'Error fetching tasks due soon: ' . $e->getMessage());
        }
    }
}
?>