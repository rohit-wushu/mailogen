<?php

declare(strict_types=1);

final class Payment extends Model
{
    protected static string $table = 'payments';

    public static function findByOrder(string $orderId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM payments WHERE gateway_order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array> */
    public static function forUser(int $userId, int $limit = 20): array
    {
        $stmt = db()->prepare('SELECT p.*, pl.name AS plan_name FROM payments p
            JOIN plans pl ON pl.id = p.plan_id
            WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
