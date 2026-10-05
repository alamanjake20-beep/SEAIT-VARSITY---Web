-- =========================================================
-- VARSITY ATHLETE ID CARD & VISITOR MONITORING SYSTEM
-- Database Schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS varsity_id_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE varsity_id_system;

-- ---------------------------------------------------------
-- 1. USERS TABLE (Admin / Dorm Staff who operate the system)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin','Staff') NOT NULL DEFAULT 'Staff',
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account -> username: admin | password: admin123
-- (password below is a bcrypt hash of "admin123")
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$GkxPxU53d9Da06yiZzx7du9la.ujW0.k96QUij0oji1GdVXKzrsX6', 'System Administrator', 'Admin');

-- ---------------------------------------------------------
-- 2. ATHLETES TABLE (Centralized athlete records)
-- ---------------------------------------------------------
CREATE TABLE athletes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(20) NOT NULL UNIQUE,          -- e.g. VAR-2026-0001
    username VARCHAR(50) NULL UNIQUE,             -- For athlete login
    password VARCHAR(255) NULL,                   -- For athlete login (hashed)
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    sport VARCHAR(100) NOT NULL,
    course VARCHAR(100) NULL,
    year_level VARCHAR(20) NULL,
    gender ENUM('Male','Female') NOT NULL,
    birthdate DATE NULL,
    contact_number VARCHAR(20) NULL,
    guardian_name VARCHAR(150) NULL,
    guardian_contact VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    photo VARCHAR(255) NULL,                      -- uploaded photo file path
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. ACCESS LOGS TABLE (Card scan + identity verification results)
-- ---------------------------------------------------------
CREATE TABLE access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(20) NOT NULL,
    athlete_id INT NULL,
    scan_result ENUM('Valid','Invalid') NOT NULL,
    identity_status ENUM('Verified','Not Verified','Pending') NOT NULL DEFAULT 'Pending',
    verified_by INT NULL,
    scan_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(255) NULL,
    CONSTRAINT fk_accesslog_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_accesslog_user FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. VISITORS TABLE (People requesting access to an athlete)
-- ---------------------------------------------------------
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) NULL,
    id_presented VARCHAR(100) NULL,   -- e.g. Visitor's own valid ID type/number
    purpose VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5. VISITOR LOGS TABLE (Visitor transactions tied to a verified athlete)
-- ---------------------------------------------------------
CREATE TABLE visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    access_log_id INT NULL,
    athlete_id INT NOT NULL,
    visitor_id INT NOT NULL,
    time_in DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    time_out DATETIME NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Inside', 'Checked Out') NOT NULL DEFAULT 'Pending',
    logged_by INT NULL,
    remarks VARCHAR(255) NULL,
    CONSTRAINT fk_visitorlog_accesslog FOREIGN KEY (access_log_id) REFERENCES access_logs(id) ON DELETE SET NULL,
    CONSTRAINT fk_visitorlog_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE,
    CONSTRAINT fk_visitorlog_visitor FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    CONSTRAINT fk_visitorlog_user FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Helpful indexes
-- ---------------------------------------------------------
CREATE INDEX idx_athletes_card ON athletes(card_id);
CREATE INDEX idx_accesslogs_card ON access_logs(card_id);
CREATE INDEX idx_accesslogs_time ON access_logs(scan_time);
CREATE INDEX idx_visitorlogs_timein ON visitor_logs(time_in);
