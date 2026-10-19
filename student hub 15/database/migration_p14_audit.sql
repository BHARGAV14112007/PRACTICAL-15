-- ============================================================================
-- StudentHub - Practical 14 migration: audit log for admin actions
-- ----------------------------------------------------------------------------
-- Creates `audit_logs` and records WHO did WHAT to WHICH record and WHEN.
-- No existing table is modified and no data is touched.
--
-- HOW TO APPLY (phpMyAdmin, run ONCE on an existing database):
--   1. Start Apache + MySQL, open http://localhost/phpmyadmin
--   2. Select `studenthub` > "Import" > choose this file > "Import".
--   (Fresh installs don't need this: database/studenthub.sql already
--   contains the table.)
-- ============================================================================

USE `studenthub`;

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
