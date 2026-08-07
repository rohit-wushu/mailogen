<?php
/**
 * Shared logic for the provider webhook endpoints in public/webhooks/*.php
 * (SES/SNS, Mailgun, SendGrid, Brevo bounce & complaint notifications).
 *
 * Security note: each account has its own unguessable `webhook_token` and
 * that token is the ONLY authentication for these endpoints (see per-file
 * comments for which providers additionally support signing, currently none
 * verified here). Anyone who obtains a token can forge bounce/complaint
 * events for that account, which — because it feeds Reputation::checkAndThrottle()
 * — can be used to force-suppress a victim's contacts or auto-pause their
 * sending. Treat the token like a secret (it's shown once in the SMTP
 * account UI); rotating an account regenerates it.
 */

declare(strict_types=1);

final class WebhookHelpers
{
    public static function resolveAccount(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $stmt = db()->prepare('SELECT * FROM smtp_accounts WHERE webhook_token = ? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Record a bounce/complaint event for $email against $account (a
     * smtp_accounts row — the legacy per-account webhook path), suppress the
     * address on hard bounces / complaints, and re-check that account's
     * reputation.
     */
    public static function recordEvent(array $account, string $email, string $event, ?string $bounceType, string $message): void
    {
        self::recordEventRaw((int) $account['user_id'], (int) $account['id'], null, null, null, $email, $event, $bounceType, $message);
        Reputation::checkAndThrottle((int) $account['id']);
    }

    /**
     * Same recording logic, but for events attributed by tenant/campaign/queue
     * row instead of an smtp_accounts row — used by the platform-wide SES
     * webhook, where there is no per-tenant account to key off of. Callers
     * are responsible for any reputation re-check (a plain smtp-account
     * reputation check doesn't apply here).
     */
    public static function recordEventRaw(int $userId, ?int $smtpId, ?int $campaignId, ?int $queueId, ?int $contactId, string $email, string $event, ?string $bounceType, string $message): void
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        EmailLog::record([
            'user_id'     => $userId,
            'campaign_id' => $campaignId,
            'queue_id'    => $queueId,
            'contact_id'  => $contactId,
            'smtp_id'     => $smtpId,
            'email'       => $email,
            'event'       => $event,
            'bounce_type' => $bounceType,
            'message'     => mb_substr($message, 0, 500),
        ]);

        if ($event === 'complained') {
            Unsubscribe::add($userId, $email, null, 'complaint');
            Contact::markStatusByEmail($userId, $email, 'unsubscribed');
        } elseif ($event === 'bounced' && $bounceType !== 'soft') {
            Unsubscribe::add($userId, $email, null, 'bounce');
            Contact::markStatusByEmail($userId, $email, 'bounced');
        }
        // Any tenant's hard bounce/complaint protects the shared SES
        // reputation for every other tenant, regardless of send transport.
        GlobalSuppression::recordFromEvent($email, $event, $bounceType, $userId);
    }
}
