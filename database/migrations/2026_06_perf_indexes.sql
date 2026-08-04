-- Performance: composite index for the monthly-usage count
-- (SELECT COUNT(*) FROM email_logs WHERE user_id=? AND event='sent' AND created_at>=…),
-- used by plan-quota checks and the dashboard. Safe to run once.

ALTER TABLE `email_logs`
  ADD KEY `idx_logs_user_event_created` (`user_id`, `event`, `created_at`);
