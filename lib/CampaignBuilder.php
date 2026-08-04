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
            $sendAfter = $interval > 0
                ? date('Y-m-d H:i:s', $base + ($count * $interval))
                : date('Y-m-d H:i:s', $base);

            $subject = render_placeholders((string) $campaign['subject'], $contact);
            $body    = render_placeholders((string) $campaign['body_html'], $contact);

            EmailQueue::insert([
                'user_id'     => $userId,
                'campaign_id' => (int) $campaign['id'],
                'contact_id'  => (int) $contact['id'],
                'step'        => 0,
                'email'       => $contact['email'],
                'subject'     => $subject,
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
