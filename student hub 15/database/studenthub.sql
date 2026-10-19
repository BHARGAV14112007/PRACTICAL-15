-- ============================================================================
-- StudentHub - MySQL Schema (Practical 8)
-- Database design: normalized schema with ER relationships, PDO-ready.
-- ============================================================================
-- ER MODEL (summary):
--
--   students  1 ----< registrations >---- 1  events
--
--   * One student  can have MANY registrations (student_id FK).
--   * One event    can have MANY registrations (event_id FK).
--   * One registration references EXACTLY ONE valid student AND ONE valid
--     event (enforced by FOREIGN KEY constraints).
--   * UNIQUE(student_id, event_id) prevents duplicate registrations for the
--     same student and event.
--
-- HOW TO IMPORT (phpMyAdmin):
--   1. Start Apache + MySQL in XAMPP Control Panel.
--   2. Open http://localhost/phpmyadmin
--   3. Click "Import" (top menu) > Choose File > select database/studenthub.sql
--   4. Leave format as SQL, click "Import". Database `studenthub` is created.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `studenthub`;

-- ----------------------------------------------------------------------------
-- Table: students
-- One row per registered student. Email is unique (one account per email).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `student_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name`     VARCHAR(60) NOT NULL,
    `email`         VARCHAR(120) NOT NULL,
    `mobile`        CHAR(10) NOT NULL,
    `course`        ENUM('BCA', 'BBA', 'B.Tech', 'Other') NOT NULL,
    `academic_year` ENUM('First Year', 'Second Year', 'Third Year', 'Final Year') NOT NULL,
    `gender`        ENUM('Male', 'Female', 'Prefer not to say') NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `department`    VARCHAR(80) NULL DEFAULT NULL,
    `semester`      TINYINT UNSIGNED NULL DEFAULT NULL,
    `role`          ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`),
    UNIQUE KEY `uq_students_email` (`email`),
    CONSTRAINT `chk_students_mobile` CHECK (`mobile` REGEXP '^[6-9][0-9]{9}$'),
    CONSTRAINT `chk_students_name_len` CHECK (CHAR_LENGTH(`full_name`) >= 2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: events
-- One row per campus event. Mirrors the fields of data/events.json.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
    `event_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(150) NOT NULL,
    `description`     TEXT NULL DEFAULT NULL,
    `category`        ENUM('Workshop', 'Technical', 'Cultural', 'Sports', 'Seminar', 'Other') NOT NULL DEFAULT 'Other',
    `event_date`      DATE NOT NULL,
    `venue`           VARCHAR(120) NOT NULL DEFAULT '',
    `organizer`       VARCHAR(120) NULL DEFAULT NULL,
    `poster_path`     VARCHAR(255) NULL DEFAULT NULL,
    `seats_available` INT UNSIGNED NOT NULL DEFAULT 0,
    `status`          ENUM('Upcoming', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Upcoming',
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`event_id`),
    KEY `idx_events_date` (`event_date`),
    KEY `idx_events_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: registrations
-- Links students to events (many-to-many via foreign keys).
-- UNIQUE(student_id, event_id) blocks duplicate sign-ups.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registrations` (
    `registration_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      INT UNSIGNED NOT NULL,
    `event_id`        INT UNSIGNED NOT NULL,
    `status`          ENUM('Registered', 'Cancelled') NOT NULL DEFAULT 'Registered',
    `registered_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`registration_id`),
    UNIQUE KEY `uq_registrations_student_event` (`student_id`, `event_id`),
    KEY `idx_registrations_event` (`event_id`),
    CONSTRAINT `fk_registrations_student`
        FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_registrations_event`
        FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Sample data (subset of data/students.json and data/events.json)
-- Sample passwords are all 'Secret123' (bcrypt hashes, never plain text).
-- ----------------------------------------------------------------------------
INSERT INTO `students`
    (`full_name`, `email`, `mobile`, `course`, `academic_year`, `gender`, `password_hash`, `department`, `semester`)
VALUES
    ('Aarav Sharma', 'aarav.sharma@college.edu', '9876543210', 'B.Tech', 'Third Year', 'Male',   '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Science', 5),
    ('Priya Verma',  'priya.verma@college.edu',  '9812345678', 'B.Tech', 'Third Year', 'Female', '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Information Technology', 5),
    ('Rohan Patel',  'rohan.patel@college.edu',  '9723456789', 'B.Tech', 'Second Year', 'Male',  '$2y$10$h2R1AGbC2NaemZeWskL7keZvHHmnCKsBvh96hynIm03IwYxikSv76', 'Mechanical Engineering', 3),
    ('Neha Gupta',   'neha.gupta@college.edu',   '9856789012', 'BCA',    'Second Year', 'Female','$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Applications', 3),
    ('Sneha Reddy',  'sneha.reddy@college.edu',  '9878901234', 'BBA',    'Second Year', 'Female','$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Business Administration', 3);

-- Administrator seed account for Practical 10 server-side login.
-- Login: admin@studenthub.edu / Admin123 (bcrypt hash stored, never plain text).
INSERT INTO `students`
    (`full_name`, `email`, `mobile`, `course`, `academic_year`,
     `gender`, `password_hash`, `department`, `semester`, `role`)
VALUES
    ('Site Administrator', 'admin@studenthub.edu', '9000000001',
     'B.Tech', 'Final Year', 'Prefer not to say',
     '$2y$10$PfWEyjR3Uzs.wk7xmkMV7OoRK79Ggxp3Tcvv8U4NFMNim7XMH7J3O',
     'Administration', 8, 'admin');

-- ----------------------------------------------------------------------------
-- Table: audit_logs (Practical 14)
-- Append-only record of administrative actions (who / what / which / when).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `log_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id`    INT UNSIGNED NULL DEFAULT NULL,
    `actor_email` VARCHAR(120) NULL DEFAULT NULL,
    `action`      VARCHAR(60) NOT NULL,
    `target_type` VARCHAR(30) NULL DEFAULT NULL,
    `target_id`   INT UNSIGNED NULL DEFAULT NULL,
    `details`     VARCHAR(500) NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `idx_audit_created` (`created_at`),
    KEY `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `events`
    (`title`, `category`, `event_date`, `venue`, `organizer`, `seats_available`, `status`)
VALUES
    ('Freshers Welcome and Cultural Fest 2026', 'Cultural', '2026-08-05', 'Main Auditorium',     'Student Council', 350, 'Upcoming'),
    ('Python and Data Science Hands-on Workshop', 'Workshop', '2026-08-12', 'Computer Lab 2',    'CSE Department',   60, 'Upcoming'),
    ('Annual Inter-College Cricket Championship', 'Sports',   '2026-08-18', 'College Sports Ground', 'Sports Committee', 120, 'Upcoming'),
    ('UI UX and Web Design Sprint Competition', 'Technical',  '2026-08-24', 'Innovation Lab A',  'IT Department',     50, 'Upcoming');

-- Valid registrations: every (student_id, event_id) pair references real rows.
INSERT INTO `registrations` (`student_id`, `event_id`, `status`)
VALUES
    (1, 1, 'Registered'),
    (1, 2, 'Registered'),
    (2, 2, 'Registered'),
    (3, 3, 'Registered'),
    (4, 4, 'Registered');
