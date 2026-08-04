-- ---------------------------------------------------------------------
--  Email verification (list cleaning) — adds verify_* columns to contacts.
--  Run once on an existing database (new installs get these via schema.sql).
-- ---------------------------------------------------------------------

ALTER TABLE `contacts`
  ADD COLUMN `verify_status` ENUM('unverified','valid','invalid','risky','unknown') NOT NULL DEFAULT 'unverified' AFTER `status`,
  ADD COLUMN `verify_reason` VARCHAR(60) DEFAULT NULL AFTER `verify_status`,
  ADD COLUMN `verified_at`   DATETIME    DEFAULT NULL AFTER `verify_reason`,
  ADD INDEX `idx_contacts_verify` (`user_id`,`verify_status`);
