-- ---------------------------------------------------------------------
--  Per-campaign Google Sheet recipient source (YAMM-style).
--  Adds: campaigns.source_type ('list' | 'sheet') and campaigns.sheet_url.
--  When source_type = 'sheet', recipients are pulled from a published
--  Google Sheet (CSV export) at send time and columns map to merge tags.
-- ---------------------------------------------------------------------

ALTER TABLE `campaigns`
  ADD COLUMN IF NOT EXISTS `source_type` ENUM('list','sheet') NOT NULL DEFAULT 'list' AFTER `list_id`,
  ADD COLUMN IF NOT EXISTS `sheet_url`   VARCHAR(500) DEFAULT NULL AFTER `source_type`;
