-- Admin-configurable global settings (key/value). First use: the "powered by"
-- branding footer shown on emails sent by users on the free plan. Safe to run once.

CREATE TABLE IF NOT EXISTS `app_settings` (
  `k` VARCHAR(64) NOT NULL,
  `v` TEXT DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`k`,`v`) VALUES
  ('branding_text','Sent with Eventogen Mailer'),
  ('branding_logo',''),
  ('branding_link','')
ON DUPLICATE KEY UPDATE `k` = `k`;
