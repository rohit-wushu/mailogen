-- ---------------------------------------------------------------------
--  Google Sign-In: link a user to their Google account id and allow
--  password-less accounts (Google-only signups). Run once on an existing
--  database (new installs get this via schema.sql).
-- ---------------------------------------------------------------------

ALTER TABLE `users`
  MODIFY COLUMN `password` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN `google_id` VARCHAR(64) DEFAULT NULL AFTER `password`,
  ADD UNIQUE KEY `uq_users_google` (`google_id`);
