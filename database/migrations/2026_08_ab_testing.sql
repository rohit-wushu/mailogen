-- ---------------------------------------------------------------------
--  Subject-line A/B testing. A small slice of the audience gets subject
--  A or B (`email_queue.ab_variant`); the rest ("holdout") are queued
--  with ab_variant='holdout' so QueueProcessor skips them regardless of
--  send_after until AbTestEngine picks a winner by open rate and flips
--  their ab_variant + subject to the winning variant, at which point
--  they're ordinary queued rows again.
-- ---------------------------------------------------------------------

ALTER TABLE `campaigns`
  ADD COLUMN `ab_subject_b`  VARCHAR(255) NULL AFTER `subject`,
  ADD COLUMN `ab_test_pct`   TINYINT UNSIGNED NULL AFTER `ab_subject_b`,
  ADD COLUMN `ab_test_hours` TINYINT UNSIGNED NULL AFTER `ab_test_pct`,
  ADD COLUMN `ab_started_at` DATETIME NULL AFTER `ab_test_hours`,
  ADD COLUMN `ab_winner`     ENUM('a','b') NULL AFTER `ab_started_at`,
  ADD COLUMN `ab_decided_at` DATETIME NULL AFTER `ab_winner`;

ALTER TABLE `email_queue`
  ADD COLUMN `ab_variant` ENUM('a','b','holdout') NULL AFTER `subject`,
  ADD INDEX `idx_queue_ab_variant` (`ab_variant`);
