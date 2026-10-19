-- ============================================================================
-- StudentHub - Practical 12 migration: event posters support
-- ----------------------------------------------------------------------------
-- Minimum schema change for the event management module:
--   1. `description` TEXT (event details shown/stored with the event).
--   2. `poster_path` VARCHAR(255) (relative path of the uploaded poster
--      image under uploads/posters/, NULL when an event has no poster).
-- Existing rows keep working: both columns are NULL-able, no data is touched.
--
-- HOW TO APPLY (phpMyAdmin, run ONCE on an existing database):
--   1. Start Apache + MySQL, open http://localhost/phpmyadmin
--   2. Select `studenthub` > "Import" > choose this file > "Import".
--   (Fresh installs don't need this: database/studenthub.sql already
--   contains both columns.)
-- ============================================================================

USE `studenthub`;

ALTER TABLE `events`
    ADD COLUMN `description` TEXT NULL DEFAULT NULL AFTER `title`,
    ADD COLUMN `poster_path` VARCHAR(255) NULL DEFAULT NULL AFTER `organizer`;
