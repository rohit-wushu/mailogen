<?php

declare(strict_types=1);

final class PlanRequest extends Model
{
    protected static string $table = 'plan_requests';

    /** Number of requests awaiting an admin decision (for the nav badge). */
    public static function countPending(): int
    {
        return (int) db_one("SELECT COUNT(*) FROM plan_requests WHERE status = 'pending'");
    }

    /** The user's current pending request, if any (blocks duplicates + shows a banner). */
    public static function pendingForUser(int $userId): ?array
    {
        $stmt = db()->prepare("SELECT pr.*, pl.name AS plan_name
            FROM plan_requests pr JOIN plans pl ON pl.id = pr.plan_id
            WHERE pr.user_id = ? AND pr.status = 'pending'
            ORDER BY pr.created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Requests for the admin list, newest first. Optionally filter by status.
     * Joins the requesting user and the target plan for display.
     */
    public static function withDetails(?string $status = null, int $limit = 200): array
    {
        $sql = "SELECT pr.*, u.name AS user_name, u.email AS user_email, pl.name AS plan_name, pl.price_monthly
                FROM plan_requests pr
                JOIN users u ON u.id = pr.user_id
                JOIN plans pl ON pl.id = pr.plan_id";
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE pr.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY (pr.status = "pending") DESC, pr.created_at DESC LIMIT ' . (int) $limit;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
