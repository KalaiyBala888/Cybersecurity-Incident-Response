-- Cybersecurity Incident Response Management System
-- Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS cyber_ir;
USE cyber_ir;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','analyst','viewer') NOT NULL DEFAULT 'viewer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX(email),
    INDEX(role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incidents Table
CREATE TABLE IF NOT EXISTS incidents (
    incident_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    type VARCHAR(100) NOT NULL,
    severity ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    status ENUM('Open','Investigating','Contained','Resolved','Closed') NOT NULL DEFAULT 'Open',
    reported_by INT NOT NULL,
    date_reported TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX(status),
    INDEX(severity),
    INDEX(date_reported),
    FULLTEXT INDEX ft_title_desc (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignments Table
CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    incident_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    deadline DATETIME,
    status ENUM('pending','accepted','completed') NOT NULL DEFAULT 'pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX(incident_id),
    INDEX(assigned_to),
    INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Response Logs Table
CREATE TABLE IF NOT EXISTS response_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    incident_id INT NOT NULL,
    analyst_id INT NOT NULL,
    action_taken LONGTEXT NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (analyst_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX(incident_id),
    INDEX(analyst_id),
    INDEX(log_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evidence Table
CREATE TABLE IF NOT EXISTS evidence (
    evidence_id INT PRIMARY KEY AUTO_INCREMENT,
    incident_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX(incident_id),
    INDEX(uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resolutions Table
CREATE TABLE IF NOT EXISTS resolutions (
    resolution_id INT PRIMARY KEY AUTO_INCREMENT,
    incident_id INT NOT NULL UNIQUE,
    root_cause LONGTEXT NOT NULL,
    summary LONGTEXT NOT NULL,
    resolved_by INT NOT NULL,
    resolved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX(resolved_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Demo Users
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@cyber.local', '$2y$10$sThwKJNsRTYs1tq0u.0.POiQiMMEjHSRHCsaGqztq4BNo7.UIvfGC', 'admin'),
('Security Analyst', 'analyst@cyber.local', '$2y$10$sThwKJNsRTYs1tq0u.0.POiQiMMEjHSRHCsaGqztq4BNo7.UIvfGC', 'analyst'),
('Viewer User', 'viewer@cyber.local', '$2y$10$sThwKJNsRTYs1tq0u.0.POiQiMMEjHSRHCsaGqztq4BNo7.UIvfGC', 'viewer');

-- Note: All demo users have password: password123
