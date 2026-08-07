<?php

declare(strict_types=1);

final class GlobalSuppression extends Model
{
    protected static string $table = 'global_suppressions';

    public static function isSuppressed(string $email): bool
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM global_suppressions WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function add(string $email, ?string $reason = null, ?int $sourceUserId = null): void
    {
        db()->prepare(
            'INSERT IGNORE INTO global_suppressions (email, reason, source_user_id) VALUES (?, ?, ?)'
        )->execute([strtolower(trim($email)), $reason, $sourceUserId]);
    }

    /** Auto-add on a hard bounce or complaint from any tenant's send. No-op for soft bounces. */
    public static function recordFromEvent(string $email, string $event, ?string $bounceType, int $sourceUserId): void
    {
        if ($event === 'complained') {
            self::add($email, 'complaint', $sourceUserId);
        } elseif ($event === 'bounced' && $bounceType !== 'soft') {
            self::add($email, 'bounce', $sourceUserId);
        }
    }

    public static function search(string $q, int $limit, int $offset): array
    {
        $sql = 'SELECT gs.*, u.name AS source_name, u.email AS source_email
                FROM global_suppressions gs
                LEFT JOIN users u ON u.id = gs.source_user_id';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE gs.email LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY gs.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(string $q = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM global_suppressions';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE email LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
