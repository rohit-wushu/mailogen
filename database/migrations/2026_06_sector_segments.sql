-- ---------------------------------------------------------------------
--  Sector segmentation — adds a `sector` field to contacts (captured on
--  import) and to campaigns (target filter). Run once on an existing DB.
-- ---------------------------------------------------------------------

ALTER TABLE `contacts`
  ADD COLUMN `sector` VARCHAR(120) DEFAULT NULL AFTER `company`,
  ADD INDEX `idx_contacts_sector` (`user_id`, `sector`);

ALTER TABLE `campaigns`
  ADD COLUMN `sector` VARCHAR(120) DEFAULT NULL AFTER `list_id`;
