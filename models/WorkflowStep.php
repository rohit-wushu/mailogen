<?php

declare(strict_types=1);

final class WorkflowStep extends Model
{
    protected static string $table = 'workflow_steps';

    public static function forWorkflow(int $workflowId): array
    {
        $stmt = db()->prepare('SELECT * FROM workflow_steps WHERE workflow_id = ? ORDER BY step_order ASC');
        $stmt->execute([$workflowId]);
        return $stmt->fetchAll();
    }

    public static function deleteForWorkflow(int $workflowId): void
    {
        db()->prepare('DELETE FROM workflow_steps WHERE workflow_id = ?')->execute([$workflowId]);
    }
}
