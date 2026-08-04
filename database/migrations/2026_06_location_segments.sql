-- Location-based segmentation: store a location (city/state/country) per
-- contact and let a campaign target a single location. Mirrors the sector
-- segmentation feature. Safe to run once on an existing database.

ALTER TABLE `contacts`
  ADD COLUMN `location` VARCHAR(120) DEFAULT NULL AFTER `sector`,
  ADD KEY `idx_contacts_location` (`user_id`, `location`);

ALTER TABLE `campaigns`
  ADD COLUMN `location` VARCHAR(120) DEFAULT NULL AFTER `sector`;
