-- ---------------------------------------------------------------------
--  Advanced super-admin panel: platform-wide SES bounce/complaint
--  attribution, a cross-tenant suppression list, and impersonation
--  audit trail (reuses `system_logs`, no schema needed for that part).
-- ---------------------------------------------------------------------

-- SES returns a MessageId on every SendRawEmail call; storing it lets the
-- platform-level SNS webhook match an inbound bounce/complaint notification
-- back to the exact queue row (and therefore the tenant) without relying on
-- per-tenant webhook tokens, which don't exist for the shared SES connection.
ALTER TABLE `email_queue`
  ADD COLUMN `ses_message_id` VARCHAR(100) DEFAULT NULL AFTER `smtp_id`,
  ADD UNIQUE KEY `uq_queue_ses_msg` (`ses_message_id`);

-- One webhook token for the platform's single SES connection (mirrors the
-- per-account token on smtp_accounts, but there's only ever one row here).
ALTER TABLE `ses_connections`
  ADD COLUMN `webhook_token` VARCHAR(64) DEFAULT NULL AFTER `region`,
  ADD UNIQUE KEY `uq_ses_conn_webhook_token` (`webhook_token`);
UPDATE `ses_connections` SET `webhook_token` = SUBSTRING(MD5(RAND()), 1, 32) WHERE `webhook_token` IS NULL;

-- Platform-wide suppression list: any hard bounce or spam complaint from ANY
-- tenant lands here automatically (on top of that tenant's own suppression
-- list), so no tenant can keep mailing an address that already hurt the
-- shared SES sender reputation. Admin can also add/remove entries by hand.
CREATE TABLE IF NOT EXISTS `global_suppressions` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`          VARCHAR(190)    NOT NULL,
  `reason`         VARCHAR(255)    DEFAULT NULL,
  `source_user_id` BIGINT UNSIGNED DEFAULT NULL,  -- the tenant whose send triggered this, if any
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_global_suppress_email` (`email`),
  CONSTRAINT `fk_global_suppress_user` FOREIGN KEY (`source_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
