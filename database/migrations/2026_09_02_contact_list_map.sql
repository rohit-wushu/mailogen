-- ---------------------------------------------------------------------
--  Many-to-many contact lists
--
--  Before this, a contact carried a single contacts.list_id, so importing a
--  sheet into list B silently moved every already-known contact out of the
--  list it was in. Membership now lives in contact_list_map, which an import
--  only ever adds to.
--
--  Safe to re-run: the table is created IF NOT EXISTS and the backfill uses
--  INSERT IGNORE, so existing rows are left alone.
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

-- Backfill every existing single-list membership into the pivot.
INSERT IGNORE INTO `contact_list_map` (`contact_id`, `list_id`, `added_at`)
SELECT c.`id`, c.`list_id`, c.`created_at`
FROM `contacts` c
WHERE c.`list_id` IS NOT NULL;
