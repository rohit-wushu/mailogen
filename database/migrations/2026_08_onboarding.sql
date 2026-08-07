-- ---------------------------------------------------------------------
--  Post-signup onboarding wizard (3 steps): structured address fields
--  (feeds the CAN-SPAM/GDPR footer requirement), a lightweight signup
--  survey, and a completion marker that gates the dashboard for new
--  accounts. Run once on an existing database (new installs get this via
--  schema.sql).
-- ---------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `city`                     VARCHAR(120) DEFAULT NULL AFTER `org_website`,
  ADD COLUMN `state`                    VARCHAR(120) DEFAULT NULL AFTER `city`,
  ADD COLUMN `zip`                      VARCHAR(20)  DEFAULT NULL AFTER `state`,
  ADD COLUMN `country`                  VARCHAR(80)  DEFAULT NULL AFTER `zip`,
  ADD COLUMN `onboarding_data`          JSON         DEFAULT NULL AFTER `country`,
  ADD COLUMN `onboarding_completed_at`  DATETIME     DEFAULT NULL AFTER `onboarding_data`;

-- Existing accounts (created before this feature) shouldn't suddenly be
-- forced through onboarding on their next login — only new signups from
-- here on out.
UPDATE `users` SET `onboarding_completed_at` = NOW() WHERE `onboarding_completed_at` IS NULL;
