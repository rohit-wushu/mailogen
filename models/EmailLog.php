<?php

declare(strict_types=1);

final class EmailLog extends Model
{
    protected static string $table = 'email_logs';

    public static function record(array $data): void
    {
        self::insert($data);
    }

    public static function recent(int $userId, int $limit = 100): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM email_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
