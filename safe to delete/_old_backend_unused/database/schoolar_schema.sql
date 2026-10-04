-- SCHOOLar database schema
-- Import this in phpMyAdmin, or run:
--   mysql -u root -p < schoolar_schema.sql
-- Safe to re-run: it drops and recreates the tables each time.

CREATE DATABASE IF NOT EXISTS schoolar_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE schoolar_db;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS viewed_scholarships;
DROP TABLE IF EXISTS saved_scholarships;
DROP TABLE IF EXISTS eligibility_criteria;
DROP TABLE IF EXISTS scholarships;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS schools;

-- ---------------------------------------------------------------
-- Reference data: schools and courses.
-- ids are strings ("c101", "crs-101"...) because they match the ids
-- School and course IDs are stored in the database and used by the application.
-- ---------------------------------------------------------------

CREATE TABLE schools (
  id VARCHAR(20) PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  acronym VARCHAR(20)
) ENGINE=InnoDB;

CREATE TABLE courses (
  id VARCHAR(20) PRIMARY KEY,
  school_id VARCHAR(20) NOT NULL,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(20),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Users — students and admins share this table, split by `role`.
-- gwa: 1.0-5.0 scale (matches the registration form).
-- annual_family_income: pesos per year.
-- municipality_code / barangay_code: PSGC codes, same ones the
-- frontend already gets from https://psgc.cloud
-- ---------------------------------------------------------------

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  birth_date DATE,
  municipality_code VARCHAR(15),
  barangay_code VARCHAR(15),
  school_id VARCHAR(20),
  course_id VARCHAR(20),
  year_level VARCHAR(20),
  gwa DECIMAL(3,2),
  annual_family_income DECIMAL(12,2),
  profile_photo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Scholarships — replaces the admin's localStorage records and the
-- hardcoded sampleScholarships object on the detail page.
-- ---------------------------------------------------------------

CREATE TABLE scholarships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  provider_name VARCHAR(150),
  benefits TEXT,
  requirements TEXT,
  deadline DATE,
  status ENUM('available', 'not available') NOT NULL DEFAULT 'available',
  municipality_code VARCHAR(15),
  barangay_code VARCHAR(15),
  municipality_name VARCHAR(100),
  barangay_name VARCHAR(100),
  contact_email VARCHAR(150),
  contact_phone VARCHAR(50),
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status_deadline (status, deadline)
) ENGINE=InnoDB;

-- Structured criteria a scholarship checks a student against — this is
-- what makes the Eligibility Checker actually work, instead of the old
-- freeform "eligibility" text box in the admin form.
CREATE TABLE eligibility_criteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  scholarship_id INT NOT NULL,
  min_gwa DECIMAL(3,2),
  max_gwa DECIMAL(3,2),
  max_annual_income DECIMAL(12,2),
  min_age INT,
  max_age INT,
  year_levels JSON,
  course_scope JSON,
  residency_required BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Saved / viewed scholarships, notifications
-- ---------------------------------------------------------------

CREATE TABLE saved_scholarships (
  user_id INT NOT NULL,
  scholarship_id INT NOT NULL,
  saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, scholarship_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE viewed_scholarships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  scholarship_id INT NOT NULL,
  viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
  INDEX idx_user_viewed (user_id, viewed_at)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  scholarship_id INT,
  type ENUM('new_match', 'deadline_reminder', 'info_updated') NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed reference data: schools and courses actually offered in the
-- app's school and course dropdowns will use these database tables.
-- ---------------------------------------------------------------

INSERT INTO schools (id, name, acronym) VALUES
  ('c101', 'University of Northern Philippines', 'UNP'),
  ('c102', 'Ilocos Sur Polytechnic State College', 'ISPSC'),
  ('c103', 'Divine Word College of Vigan', 'DWCV'),
  ('c104', 'Ilocos Sur Community College', 'ISCC');

INSERT INTO courses (id, school_id, name, code) VALUES
  ('crs-101', 'c101', 'Bachelor of Science in Nursing', 'BSN'),
  ('crs-102', 'c101', 'Doctor of Medicine', 'MD'),
  ('crs-103', 'c101', 'Bachelor of Science in Civil Engineering', 'BSCE'),
  ('crs-104', 'c101', 'Bachelor of Science in Electrical Engineering', 'BSEE'),
  ('crs-105', 'c101', 'Bachelor of Science in Mechanical Engineering', 'BSME'),
  ('crs-106', 'c101', 'Bachelor of Science in Sanitary Engineering', 'BSSE'),
  ('crs-107', 'c101', 'Bachelor of Science in Computer Engineering', 'BSCpE'),
  ('crs-108', 'c101', 'Bachelor of Elementary Education', 'BEED'),
  ('crs-109', 'c101', 'Bachelor of Secondary Education', 'BSED'),
  ('crs-110', 'c101', 'Bachelor of Physical Education', 'BPED'),
  ('crs-111', 'c101', 'Bachelor of Science in Criminology', 'BS Criminology'),
  ('crs-112', 'c101', 'Bachelor of Science in Business Administration', 'BSBA'),
  ('crs-113', 'c101', 'Bachelor of Science in Accountancy', 'BSA'),
  ('crs-114', 'c101', 'Bachelor of Science in Accounting Information System', 'BSAIS'),
  ('crs-115', 'c101', 'Bachelor of Science in Hospitality Management', 'BSHM'),
  ('crs-116', 'c101', 'Bachelor of Science in Tourism Management', 'BSTM'),
  ('crs-117', 'c101', 'Bachelor of Science in Information Technology', 'BSIT'),
  ('crs-118', 'c101', 'Bachelor of Science in Computer Science', 'BSCS'),
  ('crs-119', 'c101', 'Bachelor of Science in Architecture', 'BS Archi'),
  ('crs-120', 'c101', 'Bachelor of Science in Social Work', 'BSSW'),
  ('crs-121', 'c101', 'Bachelor of Arts in Communication', 'BA Comm'),
  ('crs-122', 'c101', 'Bachelor of Arts in Political Science', 'AB PolSci'),
  ('crs-123', 'c101', 'Bachelor of Science in Psychology', 'BS Psych'),
  ('crs-124', 'c101', 'Juris Doctor', 'JD'),
  ('crs-125', 'c102', 'Bachelor of Science in Agriculture', 'BS Agri'),
  ('crs-126', 'c102', 'Bachelor of Agricultural Technology', 'BAT'),
  ('crs-127', 'c102', 'Bachelor of Science in Information Technology', 'BSIT'),
  ('crs-128', 'c102', 'Bachelor of Elementary Education', 'BEED'),
  ('crs-129', 'c102', 'Bachelor of Secondary Education', 'BSED'),
  ('crs-130', 'c102', 'Bachelor of Technology and Livelihood Education', 'BTLEd'),
  ('crs-131', 'c102', 'Bachelor of Science in Industrial Technology', 'BS IndTech'),
  ('crs-132', 'c102', 'Bachelor of Science in Hospitality Management', 'BSHM'),
  ('crs-133', 'c102', 'Bachelor of Science in Business Administration', 'BSBA'),
  ('crs-134', 'c102', 'Bachelor of Science in Criminology', 'BS Criminology'),
  ('crs-135', 'c102', 'Bachelor of Science in Computer Science', 'BSCS'),
  ('crs-136', 'c102', 'Bachelor of Science in Marine Biology', 'BSMB'),
  ('crs-137', 'c103', 'Bachelor of Science in Business Administration', 'BSBA'),
  ('crs-138', 'c103', 'Bachelor of Science in Accountancy', 'BSA'),
  ('crs-139', 'c103', 'Bachelor of Science in Management Accounting', 'BSMA'),
  ('crs-140', 'c103', 'Bachelor of Science in Hospitality Management', 'BSHM'),
  ('crs-141', 'c103', 'Bachelor of Science in Tourism Management', 'BSTM'),
  ('crs-142', 'c103', 'Bachelor of Elementary Education', 'BEED'),
  ('crs-143', 'c103', 'Bachelor of Secondary Education', 'BSED'),
  ('crs-144', 'c103', 'Bachelor of Science in Information Technology', 'BSIT'),
  ('crs-145', 'c103', 'Bachelor of Science in Computer Science', 'BSCS'),
  ('crs-146', 'c103', 'Bachelor of Science in Criminology', 'BS Criminology'),
  ('crs-147', 'c103', 'Bachelor of Arts in Communication', 'BA Comm'),
  ('crs-148', 'c103', 'Bachelor of Arts in Political Science', 'AB PolSci'),
  ('crs-149', 'c104', 'Bachelor of Science in Business Administration', 'BSBA'),
  ('crs-150', 'c104', 'Bachelor of Science in Office Administration', 'BSOA'),
  ('crs-151', 'c104', 'Bachelor of Science in Hospitality Management', 'BSHM'),
  ('crs-152', 'c104', 'Bachelor of Science in Tourism Management', 'BSTM'),
  ('crs-153', 'c104', 'Bachelor of Elementary Education', 'BEED'),
  ('crs-154', 'c104', 'Bachelor of Secondary Education', 'BSED'),
  ('crs-155', 'c104', 'Bachelor of Science in Information Technology', 'BSIT'),
  ('crs-156', 'c104', 'Associate in Computer Technology', 'ACT');

-- ---------------------------------------------------------------
-- Seed data: one admin account so the admin portal is usable right
-- after import. Email: admin@schoolar.local  Password: admin123
-- CHANGE THIS PASSWORD (or this row) before you defend/deploy this.
-- ---------------------------------------------------------------

INSERT INTO users (full_name, email, password_hash, role)
VALUES (
  'SCHOOLar Admin',
  'admin@schoolar.local',
  '$2y$10$7/NHSaX4uIiTA7ctSUHH5OZnDJwrps.itzCOz9ivlw6qy2hoR2fWi',
  'admin'
);


-- Demo regular user account.
-- Email: student@schoolar.local | Password: User123!
INSERT INTO users (full_name, email, password_hash, role)
VALUES (
  'SCHOOLar Student',
  'student@schoolar.local',
  '$2y$12$wBDcldmaGLXoMHEg18LIFuqSty3dYAly6UCmsackmwyC5mYHJ7b2a',
  'user'
);
