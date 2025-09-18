-- KSG SMI Performance Database Schema
-- Kenya School of Government Security Management Institute Performance System

CREATE DATABASE IF NOT EXISTS ksg_smi_performance;
USE ksg_smi_performance;

-- Users table
CREATE TABLE users (
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
);

-- Admins table
CREATE TABLE admins (
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
);

-- Task categories table
CREATE TABLE task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Task templates table
CREATE TABLE task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL
);

-- User tasks table
CREATE TABLE user_tasks (
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
);

-- Task uploads table
CREATE TABLE task_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100),
    file_size INT,
    file_data LONGBLOB,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES user_tasks(id) ON DELETE CASCADE
);

-- Security settings table
CREATE TABLE security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Access logs table
CREATE TABLE access_logs (
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
);

-- System backups table
CREATE TABLE system_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    backup_data LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    file_size BIGINT,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- User reports table
CREATE TABLE user_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_type ENUM('weekly', 'monthly', 'quarterly', 'annual') NOT NULL,
    report_data JSON NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System analytics table
CREATE TABLE system_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value JSON NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_key DATE NOT NULL,
    INDEX idx_date_metric (date_key, metric_name)
);

-- Insert default task categories
INSERT INTO task_categories (name, description) VALUES
('Financial Stewardship and Discipline', 'Tasks related to financial management, revenue optimization, debt management, and audit processes'),
('Service Delivery', 'Tasks focused on citizen service delivery, complaint resolution, and service quality improvement'),
('Core Mandate', 'Tasks related to training programs, consultancy, research activities, and organizational development');

-- Insert default task templates
INSERT INTO task_templates (category_id, title, description) VALUES
-- Financial Stewardship and Discipline
(1, 'Revenue', 'Monitor and optimize revenue generation activities, analyze financial performance metrics, and implement strategies to increase organizational income.'),
(1, 'Debt Management', 'Monitor, analyze, and manage organizational debt obligations, ensuring timely payments and optimal debt structure.'),
(1, 'Pending Bills', 'Review, process, and manage pending bills and financial obligations to maintain good vendor relationships and cash flow.'),
(1, 'Zero Fault Audits', 'Conduct comprehensive audits with zero tolerance for errors, ensuring complete accuracy in financial and operational processes.'),

-- Service Delivery
(2, 'Implementation of Citizens\' Service Delivery Charter', 'Implement and monitor compliance with the Citizens\' Service Delivery Charter to ensure quality public service delivery.'),
(2, 'Resolution of Public Complaints', 'Manage and resolve public complaints efficiently and effectively, ensuring citizen satisfaction and service improvement.'),

-- Core Mandate
(3, 'Review Existing Training Programs', 'Evaluate and review current training programs for effectiveness, relevance, and alignment with organizational goals and corporate performance contract.'),
(3, 'Develop and Roll Out New Training Programs', 'Design, develop, and implement new training programs to address identified gaps and meet organizational objectives.'),
(3, 'Undertake Consultancy and Research Activities', 'Conduct consultancy services and research activities to support organizational development and knowledge advancement.'),
(3, 'Organize and Host National Symposia or Conferences', 'Plan, organize, and execute national symposia, conferences, and knowledge-sharing events to promote learning and development.'),
(3, 'Improve Productivity', 'Identify and implement strategies to enhance organizational productivity, efficiency, and performance across all departments.'),
(3, 'Manage Customer Experience and Satisfaction Score', 'Monitor, measure, and improve customer experience metrics and satisfaction scores through systematic feedback and improvement processes.'),
(3, 'Conduct Training Needs Assessment', 'Conduct thorough assessment of training needs across departments to identify skill gaps and development opportunities aligned with institute work plan.'),
(3, 'Mobilize Participants for Training', 'Coordinate and mobilize participants for training programs, workshops, and organizational events. Ensure adequate attendance and engagement.'),
(3, 'Convert and Offer Existing Programs Online', 'Transform traditional training programs into digital formats and establish online delivery mechanisms for broader accessibility.'),
(3, 'Carry Out Program and Facilitator Evaluations', 'Conduct comprehensive evaluations of training programs and facilitator performance to ensure quality and effectiveness.'),
(3, 'Identify and Implement Innovation and Creativity Initiatives', 'Research, develop, and implement innovative solutions and creative approaches to improve organizational processes and services.'),
(3, 'Institutionalize Performance Management Culture', 'Develop and implement systems to embed performance management practices throughout the organization, creating a culture of continuous improvement.');

-- Insert default security settings
INSERT INTO security_settings (setting_name, setting_value) VALUES
('password_policy', '{"minLength": 8, "requireSpecialChar": true, "requireUppercase": true, "requireNumbers": true}'),
('session_settings', '{"timeout": 30, "maxSessions": 3}'),
('two_factor_auth', '{"enabled": false, "method": "email"}'),
('backup_settings', '{"schedule": "weekly", "retention": 10, "autoBackup": true}');

-- Insert default admin user
INSERT INTO admins (name, email, password) VALUES
('System Administrator', 'admin@ksg.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert sample user
INSERT INTO users (name, email, password, department, job_title) VALUES
('John Doe', 'john.doe@ksg.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'training', 'Training Officer'); -- password: password

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_department ON users(department);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_admins_email ON admins(email);
CREATE INDEX idx_user_tasks_user_id ON user_tasks(user_id);
CREATE INDEX idx_user_tasks_status ON user_tasks(status);
CREATE INDEX idx_user_tasks_due_date ON user_tasks(due_date);
CREATE INDEX idx_access_logs_timestamp ON access_logs(timestamp);
CREATE INDEX idx_access_logs_user_type ON access_logs(user_type);

-- Create views for common queries
CREATE VIEW active_users AS
SELECT id, name, email, department, job_title, created_at, last_login
FROM users 
WHERE status = 'active';

CREATE VIEW pending_tasks AS
SELECT ut.*, u.name as user_name, u.email as user_email
FROM user_tasks ut
JOIN users u ON ut.user_id = u.id
WHERE ut.status = 'pending';

CREATE VIEW overdue_tasks AS
SELECT ut.*, u.name as user_name, u.email as user_email
FROM user_tasks ut
JOIN users u ON ut.user_id = u.id
WHERE ut.status = 'pending' AND ut.due_date < NOW();

CREATE VIEW task_completion_stats AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    u.department,
    COUNT(ut.id) as total_tasks,
    SUM(CASE WHEN ut.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN ut.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN ut.status = 'pending' AND ut.due_date < NOW() THEN 1 ELSE 0 END) as overdue_tasks,
    ROUND(
        (SUM(CASE WHEN ut.status = 'completed' THEN 1 ELSE 0 END) / COUNT(ut.id)) * 100, 2
    ) as completion_rate
FROM users u
LEFT JOIN user_tasks ut ON u.id = ut.user_id
WHERE u.status = 'active'
GROUP BY u.id, u.name, u.department;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetUserDashboardStats(IN userId INT)
BEGIN
    SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'completed' AND completed_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as completed_this_week,
        COUNT(CASE WHEN status = 'pending' AND due_date < NOW() THEN 1 END) as overdue_count,
        COUNT(*) as total_tasks
    FROM user_tasks 
    WHERE user_id = userId;
END //

CREATE PROCEDURE GetSystemAnalytics()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
        (SELECT COUNT(*) FROM user_tasks) as total_tasks,
        (SELECT COUNT(*) FROM user_tasks WHERE status = 'completed') as completed_tasks,
        (SELECT COUNT(*) FROM user_tasks WHERE status = 'pending') as pending_tasks,
        (SELECT COUNT(*) FROM user_tasks WHERE status = 'pending' AND due_date < NOW()) as overdue_tasks,
        (SELECT COUNT(*) FROM access_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as daily_logins;
END //

CREATE PROCEDURE LogUserActivity(
    IN p_user_id INT,
    IN p_admin_id INT,
    IN p_user_type ENUM('user', 'admin'),
    IN p_action VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    INSERT INTO access_logs (user_id, admin_id, user_type, action, ip_address, user_agent)
    VALUES (p_user_id, p_admin_id, p_user_type, p_action, p_ip_address, p_user_agent);
END //

DELIMITER ;

-- Create triggers for automatic updates
DELIMITER //

CREATE TRIGGER update_task_status_on_completion
BEFORE UPDATE ON user_tasks
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        SET NEW.completed_date = NOW();
    END IF;
    
    IF NEW.status = 'pending' AND NEW.due_date < NOW() THEN
        SET NEW.status = 'overdue';
    END IF;
END //

CREATE TRIGGER log_user_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.last_login != OLD.last_login THEN
        INSERT INTO access_logs (user_id, user_type, action, timestamp)
        VALUES (NEW.id, 'user', CONCAT('User login: ', NEW.name), NEW.last_login);
    END IF;
END //

CREATE TRIGGER log_admin_login
AFTER UPDATE ON admins
FOR EACH ROW
BEGIN
    IF NEW.last_login != OLD.last_login THEN
        INSERT INTO access_logs (admin_id, user_type, action, timestamp)
        VALUES (NEW.id, 'admin', CONCAT('Admin login: ', NEW.name), NEW.last_login);
    END IF;
END //

DELIMITER ;

-- Insert sample data for testing
INSERT INTO user_tasks (user_id, title, description, priority, status, due_date, assigned_by) VALUES
(1, 'Complete Security Training Module', 'Complete the mandatory cybersecurity awareness training and pass the assessment with at least 80% score.', 'high', 'pending', DATE_ADD(NOW(), INTERVAL 1 DAY), 'System Administrator'),
(1, 'Submit Monthly Performance Report', 'Prepare and submit your monthly performance report including key achievements, challenges, and goals for next month.', 'medium', 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY), 'System Administrator'),
(1, 'Update Emergency Contact Information', 'Review and update your emergency contact details in the HR system to ensure accuracy.', 'low', 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY), 'System Administrator');

-- Grant permissions (adjust as needed for your environment)
-- CREATE USER 'ksg_user'@'localhost' IDENTIFIED BY 'ksg_password_2024';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ksg_smi_performance.* TO 'ksg_user'@'localhost';
-- FLUSH PRIVILEGES;