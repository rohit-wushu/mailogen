-- ---------------------------------------------------------------------
--  Domain-based sending via Amazon SES.
--
--  Moves the primary campaign-sending model from "pick an SMTP account"
--  to "pick a verified Sending Domain" (Brevo/Mailchimp-style). Delivery
--  goes out through the account's single Amazon SES API connection; DKIM
--  is still signed by the platform using the domain's own key (unchanged
--  from the existing deliverability-core signing path). Existing
--  SMTP-account campaigns keep working untouched — this is additive.
-- ---------------------------------------------------------------------

-- One SES API connection per user (Access Key / Secret Key / Region).
CREATE TABLE IF NOT EXISTS `ses_connections` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED NOT NULL,
  `access_key`   VARCHAR(255)    NOT NULL,        -- encrypted at rest (Crypto)
  `secret_key`   TEXT            NOT NULL,        -- encrypted at rest (Crypto)
  `region`       VARCHAR(30)     NOT NULL DEFAULT 'us-east-1',
  `verified_at`  DATETIME        DEFAULT NULL,
  `last_error`   VARCHAR(255)    DEFAULT NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ses_user` (`user_id`),
  CONSTRAINT `fk_ses_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaigns can now point at a verified Sending Domain instead of an SMTP
-- account/group. `from_email` must be on that domain (validated in the
-- controller). Old smtp_group_id / smtp_id columns are left intact so
-- existing campaigns keep sending exactly as before.
ALTER TABLE `campaigns`
  ADD COLUMN `domain_id`  BIGINT UNSIGNED DEFAULT NULL AFTER `smtp_id`,
  ADD COLUMN `from_name`  VARCHAR(150)    DEFAULT NULL AFTER `domain_id`,
  ADD COLUMN `from_email` VARCHAR(190)    DEFAULT NULL AFTER `from_name`,
  ADD KEY `idx_camp_domain` (`domain_id`),
  ADD CONSTRAINT `fk_camp_domain` FOREIGN KEY (`domain_id`) REFERENCES `domains`(`id`) ON DELETE SET NULL;
