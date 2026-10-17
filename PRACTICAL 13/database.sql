-- =============================================
-- StudentHub ITUE203 — Practical 13
-- Database: studenthub_p13
-- PHP JSON API (students) + Fetch CRUD
-- XAMPP default: user root, no password
-- =============================================

CREATE DATABASE IF NOT EXISTS studenthub_p13
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE studenthub_p13;

-- ---------- users ----------
-- Used for login demo / audit reference. Password hashes are
-- bcrypt for Admin@123 and Student@123 (exact values required).
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login DATETIME NULL,
  remember_token VARCHAR(100) NULL,
  remember_expiry DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- students ----------
-- Main table used by the Practical 13 JSON API.
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  phone VARCHAR(15) NOT NULL,
  student_id VARCHAR(20) NOT NULL UNIQUE,
  course VARCHAR(100) NOT NULL,
  year TINYINT NOT NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- events ----------
-- Extra table so dashboard / registrations have something to join.
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  category VARCHAR(50) DEFAULT 'General',
  event_date DATE,
  venue VARCHAR(150),
  status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  poster VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- registrations ----------
-- Links students to events (many-to-many).
CREATE TABLE IF NOT EXISTS registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  event_id INT NOT NULL,
  status ENUM('registered','cancelled') NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_event (student_id, event_id),
  CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- audit_logs ----------
-- Records API-relevant actions for the admin dashboard (P14 re-use).
CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- seeds: users (EXACT hashes required) ----------
INSERT INTO users (username, email, password_hash, role, status) VALUES
('admin', 'admin@studenthub.local', '$2b$10$IxL73cChhqbhMu8GgUBcQOw97GGxgivtR9dx0qAs6W4vsJ110Bg0e', 'admin', 'active'),
('student', 'student@studenthub.local', '$2b$10$g57ksW2ZDcxFPn9wV7GqbenmxjZ3MSXTRmo29G6vs03cq7Levmtyy', 'student', 'active')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ---------- seeds: 8 students STU001-STU008 ----------
INSERT INTO students (full_name, email, phone, student_id, course, year, gender, status) VALUES
('Aarav Sharma',    'aarav.stu001@college.edu',   '9876500001', 'STU001', 'BCA', 1, 'Male',   'active'),
('Diya Patel',      'diya.stu002@college.edu',    '9876500002', 'STU002', 'BCA', 2, 'Female', 'active'),
('Rohan Verma',     'rohan.stu003@college.edu',   '9876500003', 'STU003', 'B.Tech IT', 1, 'Male', 'active'),
('Sneha Iyer',      'sneha.stu004@college.edu',   '9876500004', 'STU004', 'B.Sc IT', 2, 'Female', 'active'),
('Kabir Singh',     'kabir.stu005@college.edu',   '9876500005', 'STU005', 'BCA', 3, 'Male',   'active'),
('Ananya Gupta',    'ananya.stu006@college.edu',  '9876500006', 'STU006', 'B.Tech IT', 2, 'Female', 'active'),
('Vikram Mehta',    'vikram.stu007@college.edu',  '9876500007', 'STU007', 'B.Sc IT', 3, 'Male',   'inactive'),
('Priya Nair',      'priya.stu008@college.edu',   '9876500008', 'STU008', 'BCA', 1, 'Female', 'active')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

-- ---------- seeds: sample events ----------
INSERT INTO events (title, description, category, event_date, venue, status, poster) VALUES
('Tech Fest 2026', 'Annual coding and robotics fest.', 'Technical', '2026-11-20', 'Main Auditorium', 'upcoming', NULL),
('Web Design Workshop', 'Hands-on Bootstrap and Fetch API workshop.', 'Workshop', '2026-10-25', 'Lab 3', 'upcoming', NULL),
('Cultural Night', 'Music, dance and drama evening.', 'Cultural', '2026-12-05', 'Open Ground', 'upcoming', NULL);
