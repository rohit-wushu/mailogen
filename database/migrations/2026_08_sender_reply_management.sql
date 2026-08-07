-- ---------------------------------------------------------------------
--  Sender Management + Reply IDs.
--
--  Saved "From" identities (name + email, tied to a verified Sending
--  Domain) and saved Reply-To addresses, so campaigns can pick from a
--  short list instead of typing raw From/Reply-To values every time.
--  Purely additive: campaigns.from_name/from_email still work exactly as
--  before if left blank.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `senders` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `domain_id`  BIGINT UNSIGNED NOT NULL,
  `name`       VARCHAR(150)    NOT NULL,
  `email`      VARCHAR(190)    NOT NULL,
  `is_default` TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sender_user_email` (`user_id`,`email`),
  KEY `idx_sender_domain` (`domain_id`),
  CONSTRAINT `fk_sender_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_sender_domain` FOREIGN KEY (`domain_id`) REFERENCES `domains`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reply_ids` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `email`      VARCHAR(190)    NOT NULL,
  `label`      VARCHAR(150)    DEFAULT NULL,
  `is_default` TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reply_user_email` (`user_id`,`email`),
  CONSTRAINT `fk_reply_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `campaigns`
  ADD COLUMN `reply_to` VARCHAR(190) DEFAULT NULL AFTER `from_email`;
