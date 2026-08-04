<?php
/**
 * Campaign follow-up sequences (campaign_schedules).
 *
 * After the initial send, queues each follow-up step for recipients who were
 * sent the previous step at least `delay_days` ago and who have not triggered
 * a stop condition (opened / clicked / replied / unsubscribed).
 *
 * Run periodically by cron/followups.php.
 */

declare(strict_types=1);

final class FollowupEngine
{
    public static function run(): int
    {
        $queued = 0;

        $campaigns = db()->query(
            "SELECT * FROM campaigns WHERE enable_followup = 1 AND status IN ('running','completed')"
        )->fetchAll();

        foreach ($campaigns as $campaign) {
            foreach (CampaignSchedule::activeForCampaign((int) $campaign['id']) as $step) {
                $queued += self::queueStep($campaign, $step);
            }
        }
        return $queued;
    }

    private static function queueStep(array $campaign, array $step): int
    {
        $prevStep = (int) $step['step'] - 1;   // initial send is step 0
        $delay    = (int) $step['delay_days'];

        // Recipients who completed the previous step long enough ago and have
        // not yet been queued for this step.
        $stmt = db()->prepare(
            "SELECT q.contact_id, q.email
             FROM email_queue q
             WHERE q.campaign_id = ? AND q.step = ? AND q.status = 'sent'
               AND q.sent_at <= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND NOT EXISTS (
                   SELECT 1 FROM email_queue n
                   WHERE n.campaign_id = q.campaign_id AND n.contact_id = q.contact_id AND n.step = ?
               )"
        );
        $stmt->execute([(int) $campaign['id'], $prevStep, $delay, (int) $step['step']]);
        $recipients = $stmt->fetchAll();

        $count = 0;
        foreach ($recipients as $r) {
            $contactId = (int) $r['contact_id'];

            if (self::shouldStop($campaign, $step, $contactId, $r['email'])) {
                continue;
            }
            $contact = Contact::find($contactId);
            if (!$contact || $contact['status'] !== 'active') {
                continue;
            }

            EmailQueue::insert([
                'user_id'     => (int) $campaign['user_id'],
                'campaign_id' => (int) $campaign['id'],
                'schedule_id' => (int) $step['id'],
                'contact_id'  => $contactId,
                'step'        => (int) $step['step'],
                'email'       => $contact['email'],
                'subject'     => render_placeholders((string) $step['subject'], $contact),
                'body_html'   => render_placeholders((string) $step['body_html'], $contact),
                'status'      => 'queued',
                'tracking_id' => tracking_id(),
                'send_after'  => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }
        return $count;
    }

    private static function shouldStop(array $campaign, array $step, int $contactId, string $email): bool
    {
        $campaignId = (int) $campaign['id'];
        if (Unsubscribe::isSuppressed((int) $campaign['user_id'], $email, $campaignId)) {
            return true;
        }
        if ((int) $step['stop_if_replied'] === 1 && Tracking::hasReplied($contactId)) {
            return true;
        }
        if ((int) $step['stop_if_opened'] === 1 && Tracking::hasOpened($campaignId, $contactId)) {
            return true;
        }
        if ((int) $step['stop_if_clicked'] === 1 && Tracking::hasClicked($campaignId, $contactId)) {
            return true;
        }
        return false;
    }
}
