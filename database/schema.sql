-- Homeless Shelter Management System Database Schema
-- MySQL Database Structure

-- Drop existing tables if they exist (for clean installation)
DROP TABLE IF EXISTS error_logs;
DROP TABLE IF EXISTS system_config;
DROP TABLE IF EXISTS client_documents;
DROP TABLE IF EXISTS client_resources;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS needs_assessments;
DROP TABLE IF EXISTS intake_assessments;
DROP TABLE IF EXISTS service_providers;
DROP TABLE IF EXISTS bulletin_posts;
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- Roles table
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User settings for customizable UI
CREATE TABLE user_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme_color VARCHAR(20) DEFAULT '#2563eb',
    navbar_color VARCHAR(20) DEFAULT '#1e40af',
    button_color VARCHAR(20) DEFAULT '#3b82f6',
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    layout_style ENUM('compact', 'comfortable', 'spacious') DEFAULT 'comfortable',
    custom_css TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table for authentication
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulletin posts for public landing page
CREATE TABLE bulletin_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('update', 'announcement', 'event', 'resource') DEFAULT 'update',
    author_id INT,
    is_public BOOLEAN DEFAULT TRUE,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    publish_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_type (post_type),
    INDEX idx_public (is_public),
    INDEX idx_publish_date (publish_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service providers table
CREATE TABLE service_providers (
    provider_id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(255) NOT NULL,
    service_type ENUM('housing', 'detox', 'rehab', 'mental_health', 'legal', 'id_services', 'children_services', 'medical', 'employment', 'education', 'other') NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    capacity INT,
    current_availability INT,
    requirements JSON,
    api_endpoint VARCHAR(255),
    api_key VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_type (service_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Intake assessments table
CREATE TABLE intake_assessments (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    assessor_id INT,
    assessment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Basic Information
    current_living_situation VARCHAR(255),
    time_homeless VARCHAR(100),
    previous_address TEXT,
    
    -- Health Information
    physical_health_status ENUM('excellent', 'good', 'fair', 'poor', 'critical') DEFAULT 'fair',
    mental_health_status ENUM('excellent', 'good', 'fair', 'poor', 'critical') DEFAULT 'fair',
    substance_use BOOLEAN DEFAULT FALSE,
    substance_details TEXT,
    medications TEXT,
    allergies TEXT,
    
    -- Social Information
    has_children BOOLEAN DEFAULT FALSE,
    children_details TEXT,
    family_contact TEXT,
    emergency_contact JSON,
    
    -- Legal/ID
    has_id BOOLEAN DEFAULT FALSE,
    id_type VARCHAR(100),
    legal_issues TEXT,
    
    -- Employment/Education
    employment_status VARCHAR(100),
    education_level VARCHAR(100),
    work_history TEXT,
    
    -- Financial
    income_sources TEXT,
    monthly_income DECIMAL(10, 2),
    debts TEXT,
    
    -- Goals and Needs
    immediate_needs TEXT,
    short_term_goals TEXT,
    long_term_goals TEXT,
    
    -- Assessment Results
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    priority_score INT DEFAULT 50,
    recommended_services JSON,
    
    notes TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'reviewed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assessor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Higher needs assessment table
CREATE TABLE needs_assessments (
    needs_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    assessment_id INT,
    
    -- Specific Needs Categories
    housing_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    housing_type VARCHAR(100),
    detox_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    rehab_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    mental_health_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    medical_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    legal_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    id_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    children_services_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    employment_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    education_need ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    
    -- Gap Analysis
    identified_gaps JSON,
    matched_services JSON,
    pending_referrals JSON,
    completed_referrals JSON,
    
    -- Automated Communication
    auto_notify_providers BOOLEAN DEFAULT TRUE,
    notification_log JSON,
    
    -- Follow-up
    next_review_date DATE,
    case_manager_id INT,
    
    status ENUM('assessing', 'matching', 'referred', 'in_service', 'completed') DEFAULT 'assessing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES intake_assessments(assessment_id) ON DELETE CASCADE,
    FOREIGN KEY (case_manager_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255),
    body TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_starred BOOLEAN DEFAULT FALSE,
    parent_message_id INT NULL,
    attachments JSON,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES messages(message_id) ON DELETE SET NULL,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_read (is_read),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client resources table
CREATE TABLE client_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    resource_type VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    resource_url VARCHAR(500),
    provided_by INT,
    status ENUM('available', 'pending', 'assigned', 'completed') DEFAULT 'available',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (provided_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_type (resource_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client documents table
CREATE TABLE client_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    document_type ENUM('id', 'medical', 'legal', 'financial', 'education', 'employment', 'housing', 'other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    expiry_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_type (document_type),
    INDEX idx_verified (is_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System configuration table
CREATE TABLE system_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_editable BOOLEAN DEFAULT TRUE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error logs table
CREATE TABLE error_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    error_type ENUM('php', 'mysql', 'network', 'security', 'application', 'other') NOT NULL,
    severity ENUM('debug', 'info', 'warning', 'error', 'critical') DEFAULT 'error',
    error_message TEXT NOT NULL,
    error_code VARCHAR(50),
    file_path VARCHAR(500),
    line_number INT,
    stack_trace TEXT,
    user_id INT NULL,
    ip_address VARCHAR(45),
    request_uri VARCHAR(500),
    request_method VARCHAR(10),
    user_agent VARCHAR(255),
    context JSON,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_type (error_type),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('admin', 'System Administrator with full access', '{"all": true}'),
('staff', 'Staff member with access to client management', '{"clients": true, "assessments": true, "messages": true, "resources": true, "reports": true}'),
('volunteer', 'Volunteer with limited access', '{"messages": true, "resources": true, "bulletin": true}'),
('client', 'Client with access to personal portal', '{"profile": true, "messages": true, "documents": true, "resources": true}');

-- Insert default system configuration
INSERT INTO system_config (config_key, config_value, config_type, description, is_editable) VALUES
('site_name', 'Shelter Management System', 'string', 'Name of the shelter management system', TRUE),
('site_email', 'admin@shelter.org', 'string', 'Main contact email', TRUE),
('enable_auto_notifications', 'true', 'boolean', 'Enable automatic service provider notifications', TRUE),
('max_upload_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)', TRUE),
('session_timeout', '3600', 'number', 'Session timeout in seconds (1 hour)', TRUE),
('enable_error_logging', 'true', 'boolean', 'Enable automatic error logging', FALSE),
('theme_colors', '["#2563eb", "#3b82f6", "#60a5fa", "#93c5fd"]', 'json', 'Default theme color palette', TRUE),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', TRUE);
