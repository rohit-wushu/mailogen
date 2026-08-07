-- ---------------------------------------------------------------------
--  Team members: multiple logins sharing one tenant's data.
--
--  `owner_id` is NULL for a primary/tenant account (unchanged behaviour
--  for every existing user) and points at the tenant owner's users.id
--  for an invited team-member login. All resource tables still key off
--  a single `user_id` column — BaseController::uid() now resolves to
--  `owner_id ?? id` (the tenant/account id), so every existing
--  allForUser()/findForUser() call scopes correctly with zero changes.
--  BaseController::actorId() returns the raw logged-in id for the few
--  places that need the real person, not the tenant (audit logs, the
--  team page itself, per-person theme).
--
--  `team_role` is deliberately separate from the existing `role` column
--  (`admin`/`user`), which already means "platform super-admin" vs
--  "tenant" — reusing it for tenant-level permissions would collide.
--    owner  — the original account; billing, team, SMTP/domains, everything.
--    admin  — same day-to-day access as owner, minus billing/danger-zone.
--    member — campaigns/contacts/templates/automations/reports only.
-- ---------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `owner_id`  BIGINT UNSIGNED NULL AFTER `id`,
  ADD COLUMN `team_role` ENUM('owner','admin','member') NOT NULL DEFAULT 'owner' AFTER `role`,
  ADD CONSTRAINT `fk_users_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_users_owner` (`owner_id`);

CREATE TABLE IF NOT EXISTS `team_invites` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id`   BIGINT UNSIGNED NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `team_role`  ENUM('admin','member') NOT NULL DEFAULT 'member',
  `token`      VARCHAR(64) NOT NULL,
  `invited_by` BIGINT UNSIGNED NOT NULL,
  `accepted_at` DATETIME NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_invites_token` (`token`),
  KEY `idx_team_invites_owner` (`owner_id`),
  CONSTRAINT `fk_team_invites_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
