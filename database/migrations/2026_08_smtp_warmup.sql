-- ---------------------------------------------------------------------
--  SMTP account warm-up: `daily_limit` ramps up automatically from a low
--  starting point to a target ceiling, pausing (not resetting) on any day
--  where Reputation::isHealthy() looks bad — reuses the same bounce/
--  complaint window Reputation.php already tracks, no new signal invented.
-- ---------------------------------------------------------------------

ALTER TABLE `smtp_accounts`
  ADD COLUMN `warmup_enabled`      TINYINT(1) NOT NULL DEFAULT 0 AFTER `daily_limit`,
  ADD COLUMN `warmup_target_limit` INT UNSIGNED NULL AFTER `warmup_enabled`,
  ADD COLUMN `warmup_day`          INT UNSIGNED NOT NULL DEFAULT 0 AFTER `warmup_target_limit`,
  ADD COLUMN `warmup_last_step`    DATE NULL AFTER `warmup_day`;
