-- ============================================================================
-- StudentHub - Practical 10 migration: role-based login support
-- ----------------------------------------------------------------------------
-- Smallest necessary schema change for server-side authentication:
--   1. Adds `role` to the existing `students` table (no new users table;
--      student accounts ARE the user accounts, registered in Practical 9).
--   2. Seeds one administrator account (password is 'Admin123', bcrypt hash).
--
-- HOW TO APPLY (phpMyAdmin, run ONCE on an existing Practical 8 database):
--   1. Start Apache + MySQL in XAMPP, open http://localhost/phpmyadmin
--   2. Select the `studenthub` database > "Import" > choose this file
--   3. Click "Import". (Fresh installs don't need this: database/studenthub.sql
--      already contains the role column and the admin seed.)
-- ============================================================================

USE `studenthub`;

-- 1. Role column: every existing row becomes a 'student' automatically.
ALTER TABLE `students`
    ADD COLUMN `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student'
    AFTER `semester`;

-- 2. Administrator seed account (login: admin@studenthub.edu / Admin123).
--    Only the bcrypt hash is stored - never the plain password.
INSERT INTO `students`
    (`full_name`, `email`, `mobile`, `course`, `academic_year`,
     `gender`, `password_hash`, `department`, `semester`, `role`)
VALUES
    ('Site Administrator', 'admin@studenthub.edu', '9000000001',
     'B.Tech', 'Final Year', 'Prefer not to say',
     '$2y$10$PfWEyjR3Uzs.wk7xmkMV7OoRK79Ggxp3Tcvv8U4NFMNim7XMH7J3O',
     'Administration', 8, 'admin');
