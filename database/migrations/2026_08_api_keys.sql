-- ---------------------------------------------------------------------
--  Tenant-facing REST API keys. Unlike the webhook_token pattern used
--  elsewhere (plaintext, shown repeatedly for pasting into a 3rd-party
--  webhook config), an API key is shown in full exactly once at creation
--  and only its SHA-256 hash is stored — a high-entropy random token
--  doesn't need bcrypt's slow hashing, just exact-match lookup.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED NOT NULL,  -- tenant/account id (owner), same convention as every other resource table
  `label`        VARCHAR(150)    NOT NULL,
  `key_prefix`   VARCHAR(12)     NOT NULL,  -- first few chars, shown in the UI so a key can be identified without re-showing it
  `key_hash`     CHAR(64)        NOT NULL,  -- sha256(raw key)
  `created_by`   BIGINT UNSIGNED NOT NULL,  -- actor id (which team-member login generated it)
  `last_used_at` DATETIME        NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_keys_hash` (`key_hash`),
  KEY `idx_api_keys_user` (`user_id`),
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
