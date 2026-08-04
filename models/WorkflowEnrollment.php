<?php

declare(strict_types=1);

final class WorkflowEnrollment extends Model
{
    protected static string $table = 'workflow_enrollments';

    public static function enroll(int $workflowId, int $contactId, string $nextRunAt): void
    {
        db()->prepare(
            'INSERT IGNORE INTO workflow_enrollments (workflow_id, contact_id, current_step, next_run_at, status)
             VALUES (?, ?, 0, ?, "active")'
        )->execute([$workflowId, $contactId, $nextRunAt]);
    }

    /** Enrollments whose next step is due. */
    public static function due(int $limit = 200): array
    {
        $stmt = db()->prepare(
            "SELECT e.*, w.user_id, w.smtp_group_id
             FROM workflow_enrollments e
             JOIN automation_workflows w ON w.id = e.workflow_id
             WHERE e.status = 'active' AND w.status = 'active'
               AND e.next_run_at IS NOT NULL AND e.next_run_at <= NOW()
             ORDER BY e.next_run_at ASC LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function advance(int $id, int $nextStep, ?string $nextRunAt): void
    {
        db()->prepare('UPDATE workflow_enrollments SET current_step = ?, next_run_at = ? WHERE id = ?')
            ->execute([$nextStep, $nextRunAt, $id]);
    }

    public static function finish(int $id, string $status = 'completed'): void
    {
        db()->prepare('UPDATE workflow_enrollments SET status = ?, next_run_at = NULL WHERE id = ?')
            ->execute([$status, $id]);
    }
}
