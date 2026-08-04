-- Admin-editable pricing: add presentation fields to plans so the admin can
-- shape the public pricing cards (tagline, billing period, feature bullets,
-- CTA label, highlight, ordering) without touching code. Safe to run once.

ALTER TABLE `plans`
  ADD COLUMN `tagline`      VARCHAR(160) NULL          AFTER `name`,
  ADD COLUMN `price_period` VARCHAR(10)  NOT NULL DEFAULT 'month' AFTER `price_monthly`,
  ADD COLUMN `billed_note`  VARCHAR(120) NULL          AFTER `price_period`,
  ADD COLUMN `cta_label`    VARCHAR(60)  NOT NULL DEFAULT 'Subscribe' AFTER `billed_note`,
  ADD COLUMN `features`     TEXT         NULL          AFTER `cta_label`,
  ADD COLUMN `is_featured`  TINYINT(1)   NOT NULL DEFAULT 0 AFTER `features`,
  ADD COLUMN `sort_order`   INT          NOT NULL DEFAULT 0 AFTER `is_featured`;

UPDATE `plans` SET `sort_order` = `id` * 10 WHERE `sort_order` = 0;
