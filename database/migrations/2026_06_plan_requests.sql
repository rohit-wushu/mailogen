-- Manual subscription approval: when a user subscribes to a paid plan a
-- pending request is created here and surfaced in the admin panel; an admin
-- approves (activates the plan) or rejects it. Safe to run once.

CREATE TABLE IF NOT EXISTS `plan_requests` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `plan_id`       BIGINT UNSIGNED NOT NULL,
  `period_months` INT             NOT NULL DEFAULT 1,
  `amount`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `currency`      VARCHAR(10)     NOT NULL DEFAULT 'INR',
  `status`        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `note`          VARCHAR(255)    DEFAULT NULL,
  `decided_by`    BIGINT UNSIGNED DEFAULT NULL,
  `decided_at`    DATETIME        DEFAULT NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_user` (`user_id`),
  KEY `idx_pr_status` (`status`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
