<?php
/**
 * Automation workflow runner.
 *
 *   Email #1  ->  Wait 3 days  ->  Follow-up #1  ->  Wait 5 days  ->  ...
 *
 * Enrolled contacts advance one step per due tick. Wait steps push the next
 * run time forward; email steps queue a message (unless a stop condition is
 * met). Run by cron/automations.php.
 */

declare(strict_types=1);

final class AutomationEngine
{
    /** Enrol every active contact of the workflow's list. */
    public static function enrollList(array $workflow): int
    {
        if (empty($workflow['list_id'])) {
            return 0;
        }
        $contacts = Contact::activeInList((int) $workflow['user_id'], (int) $workflow['list_id']);
        $now = date('Y-m-d H:i:s');
        $n = 0;
        foreach ($contacts as $c) {
            WorkflowEnrollment::enroll((int) $workflow['id'], (int) $c['id'], $now);
            $n++;
        }
        return $n;
    }

    public static function run(): int
    {
        $processed = 0;
        foreach (WorkflowEnrollment::due() as $enr) {
            self::advance($enr);
            $processed++;
        }
        return $processed;
    }

    private static function advance(array $enr): void
    {
        $steps = WorkflowStep::forWorkflow((int) $enr['workflow_id']);
        $idx   = (int) $enr['current_step'];

        if (!isset($steps[$idx])) {
            WorkflowEnrollment::finish((int) $enr['id'], 'completed');
            return;
        }

        $step    = $steps[$idx];
        $contact = Contact::find((int) $enr['contact_id']);
        if (!$contact) {
            WorkflowEnrollment::finish((int) $enr['id'], 'stopped');
            return;
        }

        if ($step['step_type'] === 'wait') {
            $next = date('Y-m-d H:i:s', time() + ((int) $step['wait_days'] * 86400) + ((int) $step['wait_hours'] * 3600));
            WorkflowEnrollment::advance((int) $enr['id'], $idx + 1, $next);
            return;
        }

        // email step — evaluate stop conditions
        if (self::shouldStop($step, $contact, (int) $enr['workflow_id'])) {
            WorkflowEnrollment::finish((int) $enr['id'], 'stopped');
            return;
        }

        EmailQueue::insert([
            'user_id'       => (int) $enr['user_id'],
            'campaign_id'   => null,
            'workflow_id'   => (int) $enr['workflow_id'],
            'enrollment_id' => (int) $enr['id'],
            'contact_id'    => (int) $contact['id'],
            'step'          => (int) $step['step_order'],
            'email'         => $contact['email'],
            'subject'       => render_placeholders((string) $step['subject'], $contact),
            'body_html'     => render_placeholders((string) $step['body_html'], $contact),
            'status'        => 'queued',
            'tracking_id'   => tracking_id(),
            'send_after'    => date('Y-m-d H:i:s'),
        ]);

        // Move to next step immediately; a following wait step will space it out.
        WorkflowEnrollment::advance((int) $enr['id'], $idx + 1, date('Y-m-d H:i:s'));
    }

    private static function shouldStop(array $step, array $contact, int $workflowId): bool
    {
        $userId    = (int) $contact['user_id'];
        $contactId = (int) $contact['id'];

        if ((int) $step['stop_if_unsub'] === 1 && Unsubscribe::isSuppressed($userId, $contact['email'])) {
            return true;
        }
        if ((int) $step['stop_if_replied'] === 1 && Tracking::hasReplied($contactId)) {
            return true;
        }
        // Opens/clicks scoped to this workflow's automation emails.
        if ((int) $step['stop_if_opened'] === 1 && self::engaged('opens', $contactId, $workflowId)) {
            return true;
        }
        if ((int) $step['stop_if_clicked'] === 1 && self::engaged('clicks', $contactId, $workflowId)) {
            return true;
        }
        return false;
    }

    private static function engaged(string $table, int $contactId, int $workflowId): bool
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM $table t
             JOIN email_queue q ON q.id = t.queue_id
             WHERE t.contact_id = ? AND q.workflow_id = ?"
        );
        $stmt->execute([$contactId, $workflowId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
