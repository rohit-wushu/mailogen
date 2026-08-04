-- ---------------------------------------------------------------------
--  Billing + security migration (idempotent-ish; safe to run once).
--  Adds: rate_limits, payments tables and users.plan_expires_at.
-- ---------------------------------------------------------------------

-- Rate limiter buckets (brute-force / abuse throttling).
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bucket`     VARCHAR(190)    NOT NULL,
  `hits`       INT             NOT NULL DEFAULT 0,
  `expires_at` DATETIME        NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rl_bucket` (`bucket`),
  KEY `idx_rl_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment / subscription records (one row per checkout attempt).
CREATE TABLE IF NOT EXISTS `payments` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            BIGINT UNSIGNED NOT NULL,
  `plan_id`            BIGINT UNSIGNED NOT NULL,
  `amount`             DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `currency`           VARCHAR(10)     NOT NULL DEFAULT 'INR',
  `period_months`      INT             NOT NULL DEFAULT 1,
  `gateway`            VARCHAR(30)     NOT NULL DEFAULT 'manual',
  `gateway_order_id`   VARCHAR(160)    DEFAULT NULL,
  `gateway_payment_id` VARCHAR(160)    DEFAULT NULL,
  `status`             ENUM('created','paid','failed','refunded') NOT NULL DEFAULT 'created',
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at`            DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_user` (`user_id`),
  KEY `idx_pay_order` (`gateway_order_id`),
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscription expiry on the user (NULL = no expiry / free plan).
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plan_expires_at');
SET @sql := IF(@col = 0,
  'ALTER TABLE `users` ADD COLUMN `plan_expires_at` DATETIME NULL DEFAULT NULL AFTER `plan_id`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
