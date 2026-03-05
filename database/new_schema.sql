-- Enhanced KPI Monitoring System Database Schema
-- Based on the Excel data structure provided

-- Drop existing tables
DROP TABLE IF EXISTS kpi_scores;
DROP TABLE IF EXISTS kpi_master;
DROP TABLE IF EXISTS staff_comments;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS supervisors;

-- Supervisors table
CREATE TABLE supervisors (
    supervisor_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff table
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    department VARCHAR(100),
    position VARCHAR(100) DEFAULT 'Sales Assistant',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- KPI Master List table
CREATE TABLE kpi_master (
    kpi_id INT PRIMARY KEY AUTO_INCREMENT,
    kpi_code VARCHAR(20) UNIQUE NOT NULL,
    section VARCHAR(100) NOT NULL,
    kpi_group VARCHAR(200) NOT NULL,
    kpi_description TEXT NOT NULL,
    weight_percentage DECIMAL(5,2) NOT NULL,
    section_number INT NOT NULL COMMENT '1=Core Competencies, 2=KPI Achievement',
    display_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- KPI Scores table
CREATE TABLE kpi_scores (
    score_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    kpi_code VARCHAR(20) NOT NULL,
    evaluation_date DATE NOT NULL,
    evaluation_year INT NOT NULL,
    score DECIMAL(3,2) NOT NULL CHECK (score >= 1 AND score <= 5),
    weighted_score DECIMAL(5,4) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (kpi_code) REFERENCES kpi_master(kpi_code) ON DELETE RESTRICT,
    UNIQUE KEY unique_staff_kpi_date (staff_id, kpi_code, evaluation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Comments table
CREATE TABLE staff_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    evaluation_year INT NOT NULL,
    supervisor_comment TEXT,
    training_recommendation TEXT,
    supervisor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES supervisors(supervisor_id) ON DELETE SET NULL,
    UNIQUE KEY unique_staff_year (staff_id, evaluation_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Score descriptions lookup table
CREATE TABLE score_descriptions (
    score_value INT PRIMARY KEY,
    description VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert score descriptions
INSERT INTO score_descriptions (score_value, description) VALUES
(1, 'Very Poor'),
(2, 'Poor'),
(3, 'Satisfactory'),
(4, 'Good'),
(5, 'Excellent');

-- Create indexes for better performance
CREATE INDEX idx_kpi_scores_year ON kpi_scores(evaluation_year);
CREATE INDEX idx_kpi_scores_staff ON kpi_scores(staff_id);
CREATE INDEX idx_staff_comments_year ON staff_comments(evaluation_year);
CREATE INDEX idx_staff_status ON staff(status);
CREATE INDEX idx_kpi_master_section ON kpi_master(section_number);
