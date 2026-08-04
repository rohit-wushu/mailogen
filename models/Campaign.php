<?php

declare(strict_types=1);

final class Campaign extends Model
{
    protected static string $table = 'campaigns';

    public static function withStats(int $userId, string $orderBy = 'created_at DESC'): array
    {
        $stmt = db()->prepare(
            'SELECT c.*,
                (SELECT COUNT(*) FROM opens o  WHERE o.campaign_id = c.id) AS opens,
                (SELECT COUNT(*) FROM clicks k WHERE k.campaign_id = c.id) AS clicks
             FROM campaigns c WHERE c.user_id = ? ORDER BY ' . $orderBy
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        db()->prepare('UPDATE campaigns SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function incrementSent(int $id): void
    {
        db()->prepare('UPDATE campaigns SET sent_count = sent_count + 1 WHERE id = ?')->execute([$id]);
    }

    /** Campaigns whose schedule time has arrived and need queuing. */
    public static function dueForSending(): array
    {
        return db()->query(
            "SELECT * FROM campaigns
             WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()"
        )->fetchAll();
    }
}
