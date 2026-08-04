<?php

declare(strict_types=1);

final class SmtpGroup extends Model
{
    protected static string $table = 'smtp_groups';

    public static function withCounts(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT g.*, (SELECT COUNT(*) FROM smtp_accounts s WHERE s.group_id = g.id) AS smtp_count
             FROM smtp_groups g WHERE g.user_id = ? ORDER BY g.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Enabled accounts that still have daily quota, ordered by priority. */
    public static function availableAccounts(int $groupId): array
    {
        $stmt = db()->prepare(
            "SELECT * FROM smtp_accounts
             WHERE group_id = ? AND is_enabled = 1 AND sent_today < daily_limit
             ORDER BY priority ASC, id ASC"
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }
}
