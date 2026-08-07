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

    /** Most recent bounce/complaint events across every tenant, for the admin Deliverability page. */
    public static function recentBounceComplaint(int $limit = 30): array
    {
        $stmt = db()->query(
            "SELECT el.*, u.name AS user_name, u.email AS user_email
             FROM email_logs el
             JOIN users u ON u.id = el.user_id
             WHERE el.event IN ('bounced','complained')
             ORDER BY el.id DESC LIMIT " . (int) $limit
        );
        return $stmt->fetchAll();
    }
}
