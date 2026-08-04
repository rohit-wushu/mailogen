<?php

declare(strict_types=1);

final class Unsubscribe extends Model
{
    protected static string $table = 'unsubscribes';

    /** Is this email suppressed globally (or for this campaign)? */
    public static function isSuppressed(int $userId, string $email, ?int $campaignId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM unsubscribes WHERE user_id = ? AND email = ? AND (campaign_id IS NULL';
        $params = [$userId, strtolower(trim($email))];
        if ($campaignId !== null) {
            $sql .= ' OR campaign_id = ?';
            $params[] = $campaignId;
        }
        $sql .= ')';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function add(int $userId, string $email, ?int $campaignId = null, ?string $reason = null): void
    {
        db()->prepare(
            'INSERT IGNORE INTO unsubscribes (user_id, email, campaign_id, reason) VALUES (?, ?, ?, ?)'
        )->execute([$userId, strtolower(trim($email)), $campaignId, $reason]);
    }

    public static function forUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM unsubscribes WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
