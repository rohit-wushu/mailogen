<?php

declare(strict_types=1);

final class SystemLog extends Model
{
    protected static string $table = 'system_logs';

    public static function write(string $level, string $context, string $message, ?int $userId = null): void
    {
        try {
            self::insert([
                'user_id' => $userId,
                'level'   => $level,
                'context' => $context,
                'message' => mb_substr($message, 0, 1000),
            ]);
        } catch (\Throwable) {
            // never let logging break the request
        }
    }

    public static function recent(int $limit = 200): array
    {
        return db()->query('SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ' . (int) $limit)->fetchAll();
    }
}
