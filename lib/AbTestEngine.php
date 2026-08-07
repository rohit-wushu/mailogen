<?php
/**
 * Decides subject-line A/B tests once their test window has elapsed, then
 * releases the holdout batch (the majority who didn't get a test subject)
 * with the winning subject. See CampaignBuilder::maybeSetUpAbTest() for how
 * the split happens at launch time.
 */

declare(strict_types=1);

final class AbTestEngine
{
    /** Decide every campaign whose test window has elapsed. Returns how many were decided. */
    public static function run(): int
    {
        $stmt = db()->query(
            "SELECT * FROM campaigns
             WHERE ab_subject_b IS NOT NULL AND ab_winner IS NULL AND ab_started_at IS NOT NULL
               AND ab_started_at <= DATE_SUB(NOW(), INTERVAL ab_test_hours HOUR)"
        );
        $decided = 0;
        foreach ($stmt->fetchAll() as $campaign) {
            self::decide($campaign);
            $decided++;
        }
        return $decided;
    }

    private static function decide(array $campaign): void
    {
        $cid = (int) $campaign['id'];
        $rateA = self::openRate($cid, 'a');
        $rateB = self::openRate($cid, 'b');
        $winner = $rateB > $rateA ? 'b' : 'a'; // ties (including 0 opens both sides) default to A

        Campaign::update($cid, ['ab_winner' => $winner, 'ab_decided_at' => date('Y-m-d H:i:s')]);
        self::releaseHoldout($campaign, $winner);

        SystemLog::write(
            'info',
            'campaign.ab_test_decided',
            sprintf(
                "Campaign #%d A/B test decided: variant %s won (A: %.1f%% opens, B: %.1f%% opens).",
                $cid,
                strtoupper($winner),
                $rateA * 100,
                $rateB * 100
            ),
            (int) $campaign['user_id']
        );
    }

    /** Open rate (0..1) for a variant: distinct opened queue rows / sent-or-queued queue rows. */
    private static function openRate(int $campaignId, string $variant): float
    {
        $total = (int) db_one(
            "SELECT COUNT(*) FROM email_queue WHERE campaign_id = ? AND ab_variant = ?",
            [$campaignId, $variant]
        );
        if ($total === 0) {
            return 0.0;
        }
        $opened = (int) db_one(
            "SELECT COUNT(DISTINCT o.queue_id) FROM opens o
             INNER JOIN email_queue q ON q.id = o.queue_id
             WHERE q.campaign_id = ? AND q.ab_variant = ?",
            [$campaignId, $variant]
        );
        return $opened / $total;
    }

    /** Flip the holdout batch's subject to the winner's and unlock them for sending. */
    private static function releaseHoldout(array $campaign, string $winner): void
    {
        $cid = (int) $campaign['id'];
        $template = $winner === 'b' ? (string) $campaign['ab_subject_b'] : (string) $campaign['subject'];

        $stmt = db()->prepare("SELECT id, contact_id FROM email_queue WHERE campaign_id = ? AND ab_variant = 'holdout'");
        $stmt->execute([$cid]);
        foreach ($stmt->fetchAll() as $row) {
            $contact = Contact::find((int) $row['contact_id']);
            $subject = $contact ? render_placeholders($template, $contact) : $template;
            db()->prepare("UPDATE email_queue SET subject = ?, ab_variant = ? WHERE id = ?")
                ->execute([$subject, $winner, (int) $row['id']]);
        }
    }
}
