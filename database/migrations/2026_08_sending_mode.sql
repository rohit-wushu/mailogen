-- ---------------------------------------------------------------------
--  Per-account sending mode (BYO-SMTP vs platform Amazon SES) and the
--  two price tracks that go with it — domain/SES sending costs the
--  platform real AWS sending fees, so it's priced higher than BYO-SMTP,
--  which costs the platform nothing.
-- ---------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `sending_mode` ENUM('smtp','domain') NOT NULL DEFAULT 'smtp' AFTER `role`;

ALTER TABLE `plans`
  ADD COLUMN `price_smtp`   DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `price_monthly`,
  ADD COLUMN `price_domain` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `price_smtp`;

-- Backfill: existing price_monthly becomes the baseline for both tracks,
-- then the two known tiers get their real split (Starter stays free either way).
UPDATE `plans` SET `price_smtp` = `price_monthly`, `price_domain` = `price_monthly`;
UPDATE `plans` SET `price_smtp` = 999,  `price_domain` = 1999 WHERE `slug` = 'growth';
UPDATE `plans` SET `price_smtp` = 1499, `price_domain` = 2999 WHERE `slug` = 'professional';
