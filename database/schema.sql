-- =====================================================================
--  Eventogen Mailer - Complete MySQL Schema
--  Brevo-style email marketing platform (BYO-SMTP)
--  PHP 8.2 / MySQL 5.7+ / 8.x  (Hostinger compatible)
--  Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
--  plans  (subscription tiers)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plans` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(100)    NOT NULL,
  `tagline`         VARCHAR(160)    DEFAULT NULL,            -- card subtitle
  `slug`            VARCHAR(100)    NOT NULL,
  `price_monthly`   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,   -- legacy, unused by new code (kept for old rows)
  `price_smtp`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,   -- price for BYO-SMTP accounts (platform bears no sending cost)
  `price_domain`    DECIMAL(10,2)   NOT NULL DEFAULT 0.00,   -- price for Amazon SES / domain-based sending accounts
  `price_period`    VARCHAR(10)     NOT NULL DEFAULT 'month',-- shown after the price (month / year)
  `billed_note`     VARCHAR(120)    DEFAULT NULL,            -- e.g. "$36 billed annually"
  `cta_label`       VARCHAR(60)     NOT NULL DEFAULT 'Subscribe',
  `features`        TEXT            DEFAULT NULL,            -- one per line; "-" prefix = excluded / struck-through
  `is_featured`     TINYINT(1)      NOT NULL DEFAULT 0,      -- highlight this card
  `sort_order`      INT             NOT NULL DEFAULT 0,
  `max_contacts`    INT             NOT NULL DEFAULT 1000,   -- -1 = unlimited
  `max_campaigns`   INT             NOT NULL DEFAULT 10,
  `max_smtp`        INT             NOT NULL DEFAULT 1,
  `monthly_emails`  INT             NOT NULL DEFAULT 5000,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plans_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id`       BIGINT UNSIGNED DEFAULT NULL,  -- NULL = tenant account; set = team-member login under that owner's account
  `name`           VARCHAR(150)    NOT NULL,
  `email`          VARCHAR(190)    NOT NULL,
  `password`       VARCHAR(255)    DEFAULT NULL,  -- NULL for Google-only accounts
  `google_id`      VARCHAR(64)     DEFAULT NULL,
  `company`        VARCHAR(190)    DEFAULT NULL,
  `phone`          VARCHAR(60)     DEFAULT NULL,
  `role`           ENUM('admin','user') NOT NULL DEFAULT 'user',
  `team_role`      ENUM('owner','admin','member') NOT NULL DEFAULT 'owner', -- tenant-level permission, distinct from `role` (platform super-admin)
  `sending_mode`   ENUM('smtp','domain') NOT NULL DEFAULT 'smtp', -- BYO-SMTP vs platform Amazon SES (different pricing)
  `org_address`    VARCHAR(255)    DEFAULT NULL,   -- physical postal address (email footer / CAN-SPAM)
  `org_website`    VARCHAR(190)    DEFAULT NULL,
  `city`           VARCHAR(120)    DEFAULT NULL,
  `state`          VARCHAR(120)    DEFAULT NULL,
  `zip`            VARCHAR(20)     DEFAULT NULL,
  `country`        VARCHAR(80)     DEFAULT NULL,
  `onboarding_data`         JSON     DEFAULT NULL,  -- signup survey answers (subscriber count, use case, referral...)
  `onboarding_completed_at` DATETIME DEFAULT NULL,  -- NULL = new account still needs the onboarding wizard
  `plan_id`        BIGINT UNSIGNED DEFAULT NULL,
  `plan_expires_at` DATETIME       NULL DEFAULT NULL,
  `theme`          ENUM('light','dark') NOT NULL DEFAULT 'light',
  `timezone`       VARCHAR(64)     NOT NULL DEFAULT 'Asia/Kolkata',
  `imap_host`      VARCHAR(190)    DEFAULT NULL,
  `imap_port`      INT             NOT NULL DEFAULT 993,
  `imap_user`      VARCHAR(190)    DEFAULT NULL,
  `imap_pass`      VARCHAR(512)    DEFAULT NULL,
  `imap_enabled`   TINYINT(1)      NOT NULL DEFAULT 0,
  `is_verified`    TINYINT(1)      NOT NULL DEFAULT 0,
  `verify_token`   VARCHAR(100)    DEFAULT NULL,
  `reset_token`    VARCHAR(100)    DEFAULT NULL,
  `reset_expires`  DATETIME        DEFAULT NULL,
  `status`         TINYINT(1)      NOT NULL DEFAULT 1,
  `last_login_at`  DATETIME        DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google` (`google_id`),
  KEY `idx_users_plan` (`plan_id`),
  KEY `idx_users_owner` (`owner_id`),
  CONSTRAINT `fk_users_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  team_invites  (pending invitations to join a tenant's account)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_invites` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id`    BIGINT UNSIGNED NOT NULL,
  `email`       VARCHAR(190)    NOT NULL,
  `team_role`   ENUM('admin','member') NOT NULL DEFAULT 'member',
  `token`       VARCHAR(64)     NOT NULL,
  `invited_by`  BIGINT UNSIGNED NOT NULL,
  `accepted_at` DATETIME        NULL,
  `expires_at`  DATETIME        NOT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_invites_token` (`token`),
  KEY `idx_team_invites_owner` (`owner_id`),
  CONSTRAINT `fk_team_invites_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  smtp_groups  (rotation pools)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `smtp_groups` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(150)    NOT NULL,
  `rotation_mode`  ENUM('round_robin','random','priority','failover') NOT NULL DEFAULT 'round_robin',
  `rr_cursor`      BIGINT UNSIGNED NOT NULL DEFAULT 0,  -- last smtp_id used (round robin)
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grp_user` (`user_id`),
  CONSTRAINT `fk_grp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  smtp_accounts
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `smtp_accounts` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `group_id`       BIGINT UNSIGNED DEFAULT NULL,
  `domain_id`      BIGINT UNSIGNED DEFAULT NULL,  -- optional: gate this account behind a verified sending domain
  `label`          VARCHAR(150)    NOT NULL,
  `provider`       ENUM('google_workspace','gmail','brevo','ses','mailgun','sendgrid','custom') NOT NULL DEFAULT 'custom',
  `host`           VARCHAR(190)    NOT NULL,
  `port`           INT             NOT NULL DEFAULT 587,
  `encryption`     ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
  `username`       VARCHAR(190)    NOT NULL,
  `password`       VARCHAR(512)    NOT NULL,  -- encrypted at rest
  `from_email`     VARCHAR(190)    NOT NULL,
  `from_name`      VARCHAR(150)    NOT NULL,
  `priority`       INT             NOT NULL DEFAULT 10,  -- lower = higher priority
  `daily_limit`    INT             NOT NULL DEFAULT 300,
  `warmup_enabled`      TINYINT(1) NOT NULL DEFAULT 0,     -- daily_limit ramps up automatically toward warmup_target_limit
  `warmup_target_limit` INT UNSIGNED DEFAULT NULL,
  `warmup_day`          INT UNSIGNED NOT NULL DEFAULT 0,
  `warmup_last_step`    DATE DEFAULT NULL,                 -- guards against advancing twice in one calendar day
  `sent_today`     INT             NOT NULL DEFAULT 0,
  `sent_total`     BIGINT          NOT NULL DEFAULT 0,
  `fail_total`     BIGINT          NOT NULL DEFAULT 0,
  `last_reset`     DATE            DEFAULT NULL,
  `last_status`    ENUM('unknown','verified','failed') NOT NULL DEFAULT 'unknown',
  `last_checked`   DATETIME        DEFAULT NULL,
  `webhook_token`  VARCHAR(64)     DEFAULT NULL,  -- identifies this account to /public/webhooks/*.php
  `auto_paused_at` DATETIME        DEFAULT NULL,  -- set when reputation auto-throttle disables the account
  `pause_reason`   VARCHAR(255)    DEFAULT NULL,
  `is_enabled`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_smtp_user` (`user_id`),
  KEY `idx_smtp_group` (`group_id`),
  KEY `idx_smtp_domain` (`domain_id`),
  UNIQUE KEY `uq_smtp_webhook_token` (`webhook_token`),
  CONSTRAINT `fk_smtp_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_smtp_group`  FOREIGN KEY (`group_id`)  REFERENCES `smtp_groups`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_smtp_domain` FOREIGN KEY (`domain_id`) REFERENCES `domains`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  domains  (SPF/DKIM/DMARC sending-domain authentication)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `domains` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED NOT NULL,
  `domain`           VARCHAR(190)    NOT NULL,
  `dkim_selector`    VARCHAR(60)     NOT NULL,
  `dkim_private_key` TEXT            NOT NULL,   -- encrypted at rest (Crypto)
  `dkim_public_key`  TEXT            NOT NULL,   -- raw base64, for the DNS TXT record
  `spf_verified`     TINYINT(1)      NOT NULL DEFAULT 0,
  `dkim_verified`    TINYINT(1)      NOT NULL DEFAULT 0,
  `dmarc_verified`   TINYINT(1)      NOT NULL DEFAULT 0,
  `is_verified`      TINYINT(1)      NOT NULL DEFAULT 0,
  `last_checked_at`  DATETIME        DEFAULT NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_domains_user_domain` (`user_id`,`domain`),
  CONSTRAINT `fk_domains_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platform-level Amazon SES API connection, configured once by the
-- super-admin (not per tenant) — every verified Sending Domain across every
-- tenant sends through this single connection, matching how Brevo/Mailchimp
-- own their own sending infrastructure. Singleton table (at most one row);
-- `user_id` just records which admin last configured it.
CREATE TABLE IF NOT EXISTS `ses_connections` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED DEFAULT NULL,
  `access_key`   VARCHAR(255)    NOT NULL,        -- encrypted at rest (Crypto)
  `secret_key`   TEXT            NOT NULL,        -- encrypted at rest (Crypto)
  `region`       VARCHAR(30)     NOT NULL DEFAULT 'us-east-1',
  `webhook_token` VARCHAR(64)    DEFAULT NULL,     -- authenticates the platform-wide SES/SNS bounce-complaint endpoint
  `verified_at`  DATETIME        DEFAULT NULL,
  `last_error`   VARCHAR(255)    DEFAULT NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ses_conn_webhook_token` (`webhook_token`),
  CONSTRAINT `fk_ses_admin` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platform-wide suppression list: a hard bounce or complaint from ANY tenant
-- lands here automatically (on top of that tenant's own suppression list),
-- protecting the shared SES sender reputation from any one tenant's bad list.
CREATE TABLE IF NOT EXISTS `global_suppressions` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`          VARCHAR(190)    NOT NULL,
  `reason`         VARCHAR(255)    DEFAULT NULL,
  `source_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_global_suppress_email` (`email`),
  CONSTRAINT `fk_global_suppress_user` FOREIGN KEY (`source_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved "From" sender identities (name + email on a verified domain) and
-- saved Reply-To addresses, so campaigns can pick from a short list.
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

-- ---------------------------------------------------------------------
--  contact_lists
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_lists` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(190)    NOT NULL,
  `description`    VARCHAR(255)    DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_list_user` (`user_id`),
  CONSTRAINT `fk_list_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  contacts
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `list_id`        BIGINT UNSIGNED DEFAULT NULL,
  `email`          VARCHAR(190)    NOT NULL,
  `first_name`     VARCHAR(120)    DEFAULT NULL,
  `last_name`      VARCHAR(120)    DEFAULT NULL,
  `company`        VARCHAR(190)    DEFAULT NULL,
  `phone`          VARCHAR(60)     DEFAULT NULL,
  `custom_fields`  JSON            DEFAULT NULL,
  `tags`           VARCHAR(500)    DEFAULT NULL,   -- comma separated denormalised cache
  `sector`         VARCHAR(120)    DEFAULT NULL,   -- industry / segment for targeting
  `location`       VARCHAR(120)    DEFAULT NULL,   -- city / state / country for targeting
  `status`         ENUM('active','unsubscribed','bounced') NOT NULL DEFAULT 'active',
  `verify_status`  ENUM('unverified','valid','invalid','risky','unknown') NOT NULL DEFAULT 'unverified',
  `verify_reason`  VARCHAR(60)     DEFAULT NULL,
  `verified_at`    DATETIME        DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contacts_user_email` (`user_id`,`email`),
  KEY `idx_contacts_list` (`list_id`),
  KEY `idx_contacts_status` (`status`),
  KEY `idx_contacts_verify` (`user_id`,`verify_status`),
  KEY `idx_contacts_sector` (`user_id`,`sector`),
  KEY `idx_contacts_location` (`user_id`,`location`),
  CONSTRAINT `fk_contacts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)         ON DELETE CASCADE,
  CONSTRAINT `fk_contacts_list` FOREIGN KEY (`list_id`) REFERENCES `contact_lists`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  contact_list_map  (pivot: a contact can belong to many lists)
--
--  Membership lives here, not in contacts.list_id — importing a sheet into
--  one list must never pull a contact out of the lists it is already in.
--  contacts.list_id is kept only as the legacy "first list" column for old
--  rows; nothing reads it for membership any more.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_list_map` (
  `contact_id`     BIGINT UNSIGNED NOT NULL,
  `list_id`        BIGINT UNSIGNED NOT NULL,
  `added_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`contact_id`,`list_id`),
  KEY `idx_clm_list` (`list_id`),
  CONSTRAINT `fk_clm_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_clm_list`    FOREIGN KEY (`list_id`)    REFERENCES `contact_lists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  contact_tags  (+ pivot)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_tags` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(100)    NOT NULL,
  `color`          VARCHAR(20)     NOT NULL DEFAULT '#4f46e5',
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag_user_name` (`user_id`,`name`),
  CONSTRAINT `fk_tag_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_tag_map` (
  `contact_id`     BIGINT UNSIGNED NOT NULL,
  `tag_id`         BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`contact_id`,`tag_id`),
  KEY `idx_ctm_tag` (`tag_id`),
  CONSTRAINT `fk_ctm_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_ctm_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `contact_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  templates
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `templates` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(190)    NOT NULL,
  `subject`        VARCHAR(255)    NOT NULL,
  `body_html`      MEDIUMTEXT      NOT NULL,
  `thumbnail`      VARCHAR(255)    DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tpl_user` (`user_id`),
  CONSTRAINT `fk_tpl_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  campaigns
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaigns` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(190)    NOT NULL,
  `list_id`        BIGINT UNSIGNED DEFAULT NULL,
  `source_type`    ENUM('list','sheet') NOT NULL DEFAULT 'list', -- recipient source
  `sheet_url`      VARCHAR(500)    DEFAULT NULL,   -- published Google Sheet (CSV) when source_type='sheet'
  `sector`         VARCHAR(120)    DEFAULT NULL,   -- optional sector/segment target
  `location`       VARCHAR(120)    DEFAULT NULL,   -- optional location/region target
  `smtp_group_id`  BIGINT UNSIGNED DEFAULT NULL,   -- legacy SMTP-rotation mode
  `smtp_id`        BIGINT UNSIGNED DEFAULT NULL,   -- legacy single-SMTP mode
  `domain_id`      BIGINT UNSIGNED DEFAULT NULL,   -- Sending Domain (sends via the account's SES connection)
  `from_name`      VARCHAR(150)    DEFAULT NULL,
  `from_email`     VARCHAR(190)    DEFAULT NULL,   -- must be on `domain_id`'s domain
  `reply_to`       VARCHAR(190)    DEFAULT NULL,
  `template_id`    BIGINT UNSIGNED DEFAULT NULL,
  `subject`        VARCHAR(255)    DEFAULT NULL,
  `ab_subject_b`   VARCHAR(255)    DEFAULT NULL,  -- subject-line A/B test, variant B (subject = variant A)
  `ab_test_pct`    TINYINT UNSIGNED DEFAULT NULL, -- % of recipients split across A/B before picking a winner
  `ab_test_hours`  TINYINT UNSIGNED DEFAULT NULL, -- how long to wait before deciding the winner
  `ab_started_at`  DATETIME        DEFAULT NULL,
  `ab_winner`      ENUM('a','b')   DEFAULT NULL,
  `ab_decided_at`  DATETIME        DEFAULT NULL,
  `body_html`      MEDIUMTEXT      DEFAULT NULL,
  `status`         ENUM('draft','scheduled','running','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `scheduled_at`   DATETIME        DEFAULT NULL,
  `throttle_per_hour` INT          NOT NULL DEFAULT 0,   -- 0 = unlimited
  `track_opens`    TINYINT(1)      NOT NULL DEFAULT 1,
  `track_clicks`   TINYINT(1)      NOT NULL DEFAULT 1,
  `enable_followup`   TINYINT(1)   NOT NULL DEFAULT 0,
  `total_recipients`  INT          NOT NULL DEFAULT 0,
  `sent_count`        INT          NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_camp_user` (`user_id`),
  KEY `idx_camp_status` (`status`),
  KEY `idx_camp_domain` (`domain_id`),
  CONSTRAINT `fk_camp_user`   FOREIGN KEY (`user_id`)       REFERENCES `users`(`id`)         ON DELETE CASCADE,
  CONSTRAINT `fk_camp_list`   FOREIGN KEY (`list_id`)       REFERENCES `contact_lists`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_camp_grp`    FOREIGN KEY (`smtp_group_id`) REFERENCES `smtp_groups`(`id`)   ON DELETE SET NULL,
  CONSTRAINT `fk_camp_smtp`   FOREIGN KEY (`smtp_id`)       REFERENCES `smtp_accounts`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_camp_domain` FOREIGN KEY (`domain_id`)     REFERENCES `domains`(`id`)       ON DELETE SET NULL,
  CONSTRAINT `fk_camp_tpl`    FOREIGN KEY (`template_id`)   REFERENCES `templates`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  campaign_contacts  (recipient snapshot per campaign)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaign_contacts` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`    BIGINT UNSIGNED NOT NULL,
  `contact_id`     BIGINT UNSIGNED NOT NULL,
  `status`         ENUM('pending','sent','opened','clicked','replied','unsubscribed','bounced','failed') NOT NULL DEFAULT 'pending',
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_camp_contact` (`campaign_id`,`contact_id`),
  KEY `idx_cc_contact` (`contact_id`),
  CONSTRAINT `fk_cc_camp`    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  campaign_schedules  (follow-up sequence steps per campaign)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaign_schedules` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`    BIGINT UNSIGNED NOT NULL,
  `step`           INT             NOT NULL DEFAULT 1,
  `delay_days`     INT             NOT NULL DEFAULT 3,
  `subject`        VARCHAR(255)    NOT NULL,
  `body_html`      MEDIUMTEXT      NOT NULL,
  `stop_if_opened`  TINYINT(1)     NOT NULL DEFAULT 0,
  `stop_if_clicked` TINYINT(1)     NOT NULL DEFAULT 0,
  `stop_if_replied` TINYINT(1)     NOT NULL DEFAULT 1,
  `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sched_camp` (`campaign_id`),
  CONSTRAINT `fk_sched_camp` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  email_queue  (one row per contact per campaign step)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,   -- NULL for automation emails
  `schedule_id`    BIGINT UNSIGNED DEFAULT NULL,
  `workflow_id`    BIGINT UNSIGNED DEFAULT NULL,
  `enrollment_id`  BIGINT UNSIGNED DEFAULT NULL,
  `contact_id`     BIGINT UNSIGNED NOT NULL,
  `step`           INT             NOT NULL DEFAULT 0,
  `email`          VARCHAR(190)    NOT NULL,
  `subject`        VARCHAR(255)    NOT NULL,
  `ab_variant`     ENUM('a','b','holdout') DEFAULT NULL, -- 'holdout' rows are excluded from sending until AbTestEngine decides a winner
  `body_html`      MEDIUMTEXT      NOT NULL,
  `status`         ENUM('queued','sending','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `attempts`       INT             NOT NULL DEFAULT 0,
  `smtp_id`        BIGINT UNSIGNED DEFAULT NULL,
  `ses_message_id` VARCHAR(100)    DEFAULT NULL,   -- SES MessageId, matched back to bounce/complaint SNS notifications
  `error`          VARCHAR(500)    DEFAULT NULL,
  `tracking_id`    VARCHAR(64)     NOT NULL,
  `send_after`     DATETIME        DEFAULT NULL,
  `sent_at`        DATETIME        DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_queue_tracking` (`tracking_id`),
  UNIQUE KEY `uq_queue_ses_msg` (`ses_message_id`),
  KEY `idx_queue_status` (`status`,`send_after`),
  KEY `idx_queue_ab_variant` (`ab_variant`),
  KEY `idx_queue_campaign` (`campaign_id`),
  KEY `idx_queue_contact` (`contact_id`),
  CONSTRAINT `fk_queue_camp`    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_queue_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  email_logs  (delivery / failed logs)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,
  `queue_id`       BIGINT UNSIGNED DEFAULT NULL,
  `contact_id`     BIGINT UNSIGNED DEFAULT NULL,
  `smtp_id`        BIGINT UNSIGNED DEFAULT NULL,
  `email`          VARCHAR(190)    NOT NULL,
  `event`          ENUM('sent','failed','bounced','complained') NOT NULL,
  `bounce_type`    ENUM('hard','soft') DEFAULT NULL,
  `message`        VARCHAR(500)    DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_campaign` (`campaign_id`),
  KEY `idx_logs_event` (`event`),
  KEY `idx_logs_created` (`created_at`),
  KEY `idx_logs_user_event_created` (`user_id`,`event`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  opens
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `opens` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,
  `queue_id`       BIGINT UNSIGNED DEFAULT NULL,
  `contact_id`     BIGINT UNSIGNED DEFAULT NULL,
  `tracking_id`    VARCHAR(64)     NOT NULL,
  `ip`             VARCHAR(64)     DEFAULT NULL,
  `device`         VARCHAR(40)     DEFAULT NULL,
  `browser`        VARCHAR(60)     DEFAULT NULL,
  `country`        VARCHAR(80)     DEFAULT NULL,
  `user_agent`     VARCHAR(255)    DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_opens_campaign` (`campaign_id`),
  KEY `idx_opens_tracking` (`tracking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  clicks
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clicks` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,
  `queue_id`       BIGINT UNSIGNED DEFAULT NULL,
  `contact_id`     BIGINT UNSIGNED DEFAULT NULL,
  `tracking_id`    VARCHAR(64)     NOT NULL,
  `url`            VARCHAR(1000)   NOT NULL,
  `ip`             VARCHAR(64)     DEFAULT NULL,
  `device`         VARCHAR(40)     DEFAULT NULL,
  `browser`        VARCHAR(60)     DEFAULT NULL,
  `country`        VARCHAR(80)     DEFAULT NULL,
  `user_agent`     VARCHAR(255)    DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clicks_campaign` (`campaign_id`),
  KEY `idx_clicks_tracking` (`tracking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  unsubscribes  (suppression list — global or per campaign)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `unsubscribes` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `email`          VARCHAR(190)    NOT NULL,
  `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,   -- NULL = global suppression
  `reason`         VARCHAR(255)    DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unsub` (`user_id`,`email`,`campaign_id`),
  KEY `idx_unsub_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  replies  (for "stop sequence if replied")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `replies` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,
  `contact_id`     BIGINT UNSIGNED DEFAULT NULL,
  `email`          VARCHAR(190)    NOT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_replies_contact` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  automation_workflows  (+ steps)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `automation_workflows` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(190)    NOT NULL,
  `list_id`        BIGINT UNSIGNED DEFAULT NULL,
  `smtp_group_id`  BIGINT UNSIGNED DEFAULT NULL,
  `trigger_type`   ENUM('manual','contact_added') NOT NULL DEFAULT 'manual',
  `status`         ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wf_user` (`user_id`),
  CONSTRAINT `fk_wf_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `workflow_steps` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_id`    BIGINT UNSIGNED NOT NULL,
  `step_order`     INT             NOT NULL DEFAULT 1,
  `step_type`      ENUM('email','wait') NOT NULL DEFAULT 'email',
  `wait_days`      INT             NOT NULL DEFAULT 0,
  `wait_hours`     INT             NOT NULL DEFAULT 0,
  `subject`        VARCHAR(255)    DEFAULT NULL,
  `body_html`      MEDIUMTEXT      DEFAULT NULL,
  `stop_if_opened`  TINYINT(1)     NOT NULL DEFAULT 0,
  `stop_if_clicked` TINYINT(1)     NOT NULL DEFAULT 0,
  `stop_if_replied` TINYINT(1)     NOT NULL DEFAULT 0,
  `stop_if_unsub`   TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wfs_wf` (`workflow_id`),
  CONSTRAINT `fk_wfs_wf` FOREIGN KEY (`workflow_id`) REFERENCES `automation_workflows`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- enrolment of a contact into a workflow (tracks progress)
CREATE TABLE IF NOT EXISTS `workflow_enrollments` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_id`    BIGINT UNSIGNED NOT NULL,
  `contact_id`     BIGINT UNSIGNED NOT NULL,
  `current_step`   INT             NOT NULL DEFAULT 0,
  `next_run_at`    DATETIME        DEFAULT NULL,
  `status`         ENUM('active','completed','stopped') NOT NULL DEFAULT 'active',
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wfe` (`workflow_id`,`contact_id`),
  KEY `idx_wfe_run` (`status`,`next_run_at`),
  CONSTRAINT `fk_wfe_wf`      FOREIGN KEY (`workflow_id`) REFERENCES `automation_workflows`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wfe_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`)              ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  system_logs  (admin)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED DEFAULT NULL,
  `level`          ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  `context`        VARCHAR(80)     DEFAULT NULL,
  `message`        VARCHAR(1000)   NOT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_syslog_level` (`level`),
  KEY `idx_syslog_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  rate_limits  (brute-force / abuse throttling)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bucket`     VARCHAR(190)    NOT NULL,
  `hits`       INT             NOT NULL DEFAULT 0,
  `expires_at` DATETIME        NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rl_bucket` (`bucket`),
  KEY `idx_rl_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  payments  (subscription / checkout records)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
--  api_keys  (tenant-facing REST API)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED NOT NULL,
  `label`        VARCHAR(150)    NOT NULL,
  `key_prefix`   VARCHAR(12)     NOT NULL,
  `key_hash`     CHAR(64)        NOT NULL,
  `created_by`   BIGINT UNSIGNED NOT NULL,
  `last_used_at` DATETIME        NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_keys_hash` (`key_hash`),
  KEY `idx_api_keys_user` (`user_id`),
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  app_settings  (admin-configurable global key/value settings)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_settings` (
  `k` VARCHAR(64) NOT NULL,
  `v` TEXT DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  plan_requests  (manual subscription approvals)
-- ---------------------------------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  Seed data
-- =====================================================================

INSERT INTO `plans`
  (`name`,`tagline`,`slug`,`price_monthly`,`price_smtp`,`price_domain`,`price_period`,`cta_label`,`features`,`is_featured`,`sort_order`,`max_contacts`,`max_campaigns`,`max_smtp`,`monthly_emails`) VALUES
  ('Starter','For getting started','starter',0.00,0.00,0.00,'month','Get started',
   'All basic features included\nOpen, click & response tracking\nUnsubscribe & bounce tracking\n-Scheduling\n"Powered by" branded footer\nUp to 1,000 emails / month\n1 user\nBest effort support',
   0,10,1000,10,1,1000),
  ('Growth','For steady senders','growth',1999.00,999.00,1999.00,'month','Subscribe',
   'All basic features included\nOpen, click & response tracking\nUnsubscribe & bounce tracking\nScheduling\nNo branded footer\nUp to 50,000 emails / month\n1 user\nPriority support',
   1,20,12000,50,3,50000),
  ('Professional','For growing senders','professional',2999.00,1499.00,2999.00,'month','Subscribe',
   'All basic features included\nOpen, click & response tracking\nUnsubscribe & bounce tracking\nScheduling\nNo branded footer\nUp to 100,000 emails / month\n1 user\nPriority support',
   0,30,25000,100,5,100000)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default global settings (admin-editable: free-plan email branding + site identity)
INSERT INTO `app_settings` (`k`,`v`) VALUES
  ('branding_text','Sent with Eventogen Mailer'),
  ('branding_logo',''),
  ('branding_link',''),
  ('site_name',''),
  ('site_logo',''),
  ('site_favicon',''),
  ('brand_display','both'),
  ('meta_title',''),
  ('meta_description',''),
  ('meta_keywords','')
ON DUPLICATE KEY UPDATE `k` = `k`;

-- Default admin user  (password: Admin@123  -> change after first login)
-- Hash generated with password_hash('Admin@123', PASSWORD_DEFAULT)
INSERT INTO `users` (`name`,`email`,`password`,`company`,`role`,`is_verified`,`plan_id`,`onboarding_completed_at`)
VALUES ('Administrator','admin@eventogen.com',
        '$2y$12$vo0HcdH7Vd.RQoG/srY/Be750V33vGnA65KiIpFFdZtevHIrr1hRW',
        'Eventogen','admin',1,
        (SELECT id FROM plans WHERE slug='agency' LIMIT 1), NOW())
ON DUPLICATE KEY UPDATE `email` = `email`;
