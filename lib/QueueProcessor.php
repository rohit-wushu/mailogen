<?php
/**
 * Drains the email queue in batches. Invoked by cron/send-emails.php.
 */

declare(strict_types=1);

final class QueueProcessor
{
    /**
     * Process up to $batchSize queued emails. Returns a small summary.
     */
    public static function run(int $batchSize = BATCH_SIZE): array
    {
        SmtpAccount::resetDailyIfNeeded();

        $batch = EmailQueue::claimBatch($batchSize);
        $sent = 0;
        $failed = 0;

        foreach ($batch as $queue) {
            $ok = Mailer::deliver($queue);
            $ok ? $sent++ : $failed++;

            // Gentle pacing so a shared host isn't hammered.
            if (SEND_DELAY_MS > 0) {
                usleep(SEND_DELAY_MS * 1000);
            }
        }

        // Mark campaigns whose queue is fully drained as completed.
        self::completeFinishedCampaigns();

        return ['processed' => count($batch), 'sent' => $sent, 'failed' => $failed];
    }

    private static function completeFinishedCampaigns(): void
    {
        db()->query(
            "UPDATE campaigns c
             SET c.status = 'completed'
             WHERE c.status = 'running'
               AND NOT EXISTS (
                   SELECT 1 FROM email_queue q
                   WHERE q.campaign_id = c.id AND q.status IN ('queued','sending')
               )"
        );
    }
}
