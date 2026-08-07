<?php

declare(strict_types=1);

final class Sender extends Model
{
    protected static string $table = 'senders';

    /** @return array<int,array> senders joined with their domain name */
    public static function withDomain(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT s.*, d.domain AS domain_name, d.is_verified AS domain_verified
             FROM senders s JOIN domains d ON d.id = s.domain_id
             WHERE s.user_id = ? ORDER BY s.is_default DESC, s.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function clearDefault(int $userId): void
    {
        db()->prepare('UPDATE senders SET is_default = 0 WHERE user_id = ?')->execute([$userId]);
    }
}
