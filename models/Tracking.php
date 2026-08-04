<?php

declare(strict_types=1);

/**
 * Read/write helper for the opens, clicks and replies tables.
 */
final class Tracking
{
    public static function recordOpen(array $queue, array $meta): void
    {
        db()->prepare(
            'INSERT INTO opens (user_id, campaign_id, queue_id, contact_id, tracking_id, ip, device, browser, country, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $queue['user_id'], $queue['campaign_id'], $queue['id'], $queue['contact_id'], $queue['tracking_id'],
            $meta['ip'], $meta['device'], $meta['browser'], $meta['country'], $meta['user_agent'],
        ]);
        CampaignContact::setStatus((int) $queue['campaign_id'], (int) $queue['contact_id'], 'opened');
    }

    public static function recordClick(array $queue, string $targetUrl, array $meta): void
    {
        db()->prepare(
            'INSERT INTO clicks (user_id, campaign_id, queue_id, contact_id, tracking_id, url, ip, device, browser, country, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $queue['user_id'], $queue['campaign_id'], $queue['id'], $queue['contact_id'], $queue['tracking_id'], $targetUrl,
            $meta['ip'], $meta['device'], $meta['browser'], $meta['country'], $meta['user_agent'],
        ]);
        CampaignContact::setStatus((int) $queue['campaign_id'], (int) $queue['contact_id'], 'clicked');
    }

    public static function hasOpened(int $campaignId, int $contactId): bool
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM opens WHERE campaign_id = ? AND contact_id = ?');
        $stmt->execute([$campaignId, $contactId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasClicked(int $campaignId, int $contactId): bool
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM clicks WHERE campaign_id = ? AND contact_id = ?');
        $stmt->execute([$campaignId, $contactId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasReplied(int $contactId): bool
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM replies WHERE contact_id = ?');
        $stmt->execute([$contactId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
