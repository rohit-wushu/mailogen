<?php

declare(strict_types=1);

final class Workflow extends Model
{
    protected static string $table = 'automation_workflows';

    public static function withStepCounts(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT w.*,
                (SELECT COUNT(*) FROM workflow_steps s WHERE s.workflow_id = w.id) AS step_count,
                (SELECT COUNT(*) FROM workflow_enrollments e WHERE e.workflow_id = w.id AND e.status = "active") AS active_count
             FROM automation_workflows w WHERE w.user_id = ? ORDER BY w.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function activeWorkflows(): array
    {
        return db()->query("SELECT * FROM automation_workflows WHERE status = 'active'")->fetchAll();
    }
}
