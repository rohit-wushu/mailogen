-- ---------------------------------------------------------------------
--  Amazon SES becomes a platform-level (super-admin) connection instead
--  of something every tenant configures themselves — matches how Brevo/
--  Mailchimp work: the platform owns the sending infrastructure, tenants
--  just verify their own domain. `ses_connections` becomes a singleton
--  table (at most one row); `user_id` is kept only to record which admin
--  last configured it, so it's made nullable and no longer unique/looked
--  up per-tenant.
-- ---------------------------------------------------------------------

ALTER TABLE `ses_connections`
  DROP FOREIGN KEY `fk_ses_user`,
  DROP INDEX `uq_ses_user`,
  MODIFY COLUMN `user_id` BIGINT UNSIGNED DEFAULT NULL,
  ADD CONSTRAINT `fk_ses_admin` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
