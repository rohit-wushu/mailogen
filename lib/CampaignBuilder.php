<?php
/**
 * Expands a campaign into individual email_queue rows (the initial send),
 * applying personalisation, suppression filtering and rate limiting.
 *
 * Recipients come from either a contact list (source_type='list') or a
 * published Google Sheet (source_type='sheet'). Sheet rows are upserted into
 * contacts first so tracking, suppression, follow-ups and stats all behave
 * the same as a list send.
 */

declare(strict_types=1);

final class CampaignBuilder
{
    /**
     * Build the queue for a campaign's first email. Returns recipient count.
     */
    public static function enqueue(array $campaign): int
    {
        $userId = (int) $campaign['user_id'];
        $leads  = self::resolveRecipients($campaign);

        $campaign = self::maybeAutoUpgradeToDomain($campaign, count($leads));
        [$abAssignments, $abHoldoutSendAfter] = self::maybeSetUpAbTest($campaign, $leads);

        $throttle = (int) $campaign['throttle_per_hour'];
        $interval = $throttle > 0 ? (int) floor(3600 / max(1, $throttle)) : 0;
        $base = !empty($campaign['scheduled_at']) ? strtotime($campaign['scheduled_at']) : time();

        $count = 0;
        foreach ($leads as $contact) {
            if (empty($contact['id']) || empty($contact['email'])) {
                continue;
            }
            if (Unsubscribe::isSuppressed($userId, $contact['email'], (int) $campaign['id'])) {
                continue;
            }
            if (GlobalSuppression::isSuppressed($contact['email'])) {
                continue;
            }
            $sendAfter = $interval > 0
                ? date('Y-m-d H:i:s', $base + ($count * $interval))
                : date('Y-m-d H:i:s', $base);

            // A/B test: variant A/B get their own subject and send immediately;
            // the holdout majority is queued but excluded from sending (see
            // EmailQueue::claimBatch()) until AbTestEngine picks a winner.
            $variant = $abAssignments[(int) $contact['id']] ?? null;
            $subjectTemplate = $variant === 'b' ? (string) $campaign['ab_subject_b'] : (string) $campaign['subject'];
            if ($variant === 'holdout') {
                $sendAfter = $abHoldoutSendAfter;
            }

            $subject = render_placeholders($subjectTemplate, $contact);
            $body    = render_placeholders((string) $campaign['body_html'], $contact);

            EmailQueue::insert([
                'user_id'     => $userId,
                'campaign_id' => (int) $campaign['id'],
                'contact_id'  => (int) $contact['id'],
                'step'        => 0,
                'email'       => $contact['email'],
                'subject'     => $subject,
                'ab_variant'  => $variant,
                'body_html'   => $body,
                'status'      => 'queued',
                'tracking_id' => tracking_id(),
                'send_after'  => $sendAfter,
            ]);
            CampaignContact::add((int) $campaign['id'], (int) $contact['id']);
            $count++;
        }

        Campaign::update((int) $campaign['id'], [
            'total_recipients' => $count,
            'status'           => 'running',
        ]);

        return $count;
    }

    /**
     * If this campaign has an A/B subject test configured, split recipients
     * into variant A, variant B (a small slice each, decided by open rate)
     * and a holdout (the rest, held back until AbTestEngine picks a winner).
     * Also stamps `ab_started_at` so the test's decision window is measured
     * from actual launch time, not campaign creation.
     *
     * @return array{0: array<int,string>, 1: ?string} [contact_id => variant, holdout send_after]
     */
    private static function maybeSetUpAbTest(array $campaign, array $leads): array
    {
        if (empty($campaign['ab_subject_b']) || (int) ($campaign['ab_test_pct'] ?? 0) <= 0) {
            return [[], null];
        }

        $ids = array_values(array_unique(array_map(static fn ($c) => (int) $c['id'], $leads)));
        if (count($ids) < 4) {
            return [[], null]; // too small a list for a meaningful split — send subject A to everyone
        }
        shuffle($ids);

        $testPct = min(90, max(2, (int) $campaign['ab_test_pct']));
        $testCount = max(2, (int) ceil(count($ids) * $testPct / 100));
        $testCount = min($testCount, count($ids) - 1); // always leave at least one for the holdout

        $testIds = array_slice($ids, 0, $testCount);
        $holdoutIds = array_slice($ids, $testCount);
        $half = (int) ceil(count($testIds) / 2);

        $assignments = [];
        foreach (array_slice($testIds, 0, $half) as $id) {
            $assignments[$id] = 'a';
        }
        foreach (array_slice($testIds, $half) as $id) {
            $assignments[$id] = 'b';
        }
        foreach ($holdoutIds as $id) {
            $assignments[$id] = 'holdout';
        }

        $hours = max(1, (int) ($campaign['ab_test_hours'] ?? 4));
        $holdoutSendAfter = date('Y-m-d H:i:s', time() + $hours * 3600);
        Campaign::update((int) $campaign['id'], ['ab_started_at' => date('Y-m-d H:i:s')]);

        return [$assignments, $holdoutSendAfter];
    }

    /**
     * If this campaign is about to launch on BYO-SMTP with a recipient count
     * over the admin-configured threshold, silently switch the account to
     * domain (SES) sending — but only when the tenant already has a verified
     * Sending Domain to send from; otherwise there's nothing to switch to and
     * the campaign proceeds on SMTP unchanged. Runs on every launch path
     * (immediate send + cron-scheduled) since both funnel through enqueue().
     */
    private static function maybeAutoUpgradeToDomain(array $campaign, int $recipientCount): array
    {
        if (!empty($campaign['domain_id'])) {
            return $campaign; // already sending via a domain — nothing to do
        }
        if (Setting::get('auto_ses_enabled', '0') !== '1') {
            return $campaign;
        }
        $threshold = (int) (Setting::get('auto_ses_threshold', '5000') ?: 5000);
        if ($recipientCount < $threshold || !SesConnection::platform()) {
            return $campaign;
        }

        $userId = (int) $campaign['user_id'];
        $sender = null;
        foreach (Sender::withDomain($userId) as $s) {
            if ((int) $s['domain_verified'] === 1) {
                $sender = $s;
                break;
            }
        }
        if ($sender === null) {
            return $campaign; // no verified domain yet — can't switch, stays on SMTP
        }

        $user = User::find($userId);
        if (($user['sending_mode'] ?? 'smtp') !== 'domain') {
            User::update($userId, ['sending_mode' => 'domain']);
        }

        $updates = [
            'domain_id'     => (int) $sender['domain_id'],
            'from_email'    => $campaign['from_email'] ?: $sender['email'],
            'from_name'     => $campaign['from_name'] ?: $sender['name'],
            'smtp_group_id' => null,
            'smtp_id'       => null,
        ];
        Campaign::update((int) $campaign['id'], $updates);
        $campaign = array_merge($campaign, $updates);

        SystemLog::write(
            'info',
            'campaign.auto_upgrade',
            "Campaign #{$campaign['id']} ({$recipientCount} recipients, threshold {$threshold}) auto-switched the account from SMTP to domain (SES) sending.",
            $userId
        );

        $html = '<p>Hi ' . e($user['name'] ?? '') . ',</p>'
              . "<p>Your campaign #{$campaign['id']} has <strong>{$recipientCount} recipients</strong> — above the volume where BYO-SMTP accounts typically start hitting provider limits and deliverability problems.</p>"
              . "<p>We've automatically switched your account to <strong>domain-based sending</strong> via <strong>{$sender['domain_name']}</strong>, our managed Amazon SES infrastructure, for this and future campaigns. Pricing for domain-based sending applies going forward.</p>"
              . '<p>You can switch back to BYO-SMTP anytime from Settings.</p>';
        Mailer::sendSystem((string) $user['email'], 'Your account moved to managed sending — high-volume campaign detected', $html, $userId);

        return $campaign;
    }

    /**
     * Resolve recipient records for a campaign. Each record carries an `id`
     * (contact id) and `email`, plus any sheet columns for merge rendering.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function resolveRecipients(array $campaign): array
    {
        if (($campaign['source_type'] ?? 'list') === 'sheet') {
            return self::recipientsFromSheet($campaign);
        }

        $userId = (int) $campaign['user_id'];
        return Contact::activeInList(
            $userId,
            $campaign['list_id'] ? (int) $campaign['list_id'] : null,
            $campaign['sector'] ?? null,
            $campaign['location'] ?? null
        );
    }

    /**
     * Fetch the campaign's Google Sheet, upsert each row into contacts (dedupe
     * by email) and return contact records merged with the live sheet columns.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function recipientsFromSheet(array $campaign): array
    {
        $userId = (int) $campaign['user_id'];
        $result = GoogleSheet::fetch((string) ($campaign['sheet_url'] ?? ''));
        if (!$result['ok']) {
            return [];
        }

        $leads = [];
        foreach (GoogleSheet::recipients($result['rows']) as $rec) {
            $res = Contact::upsert($userId, [
                'email'         => $rec['email'],
                'first_name'    => $rec['first_name'],
                'last_name'     => $rec['last_name'],
                'company'       => $rec['company'],
                'sector'        => $rec['sector'],
                'location'      => $rec['location'],
                'phone'         => $rec['phone'],
                'custom_fields' => $rec['custom_fields'] ?? [],
            ]);
            // Keep the live sheet columns on the record so merge tags use the
            // freshest values; just graft on the resolved contact id.
            $rec['id'] = $res['id'];
            $leads[] = $rec;
        }
        return $leads;
    }
}
