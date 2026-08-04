<?php
/**
 * High-level delivery engine.
 *
 * Ties together tracking injection, the SMTP rotation engine and the raw
 * SmtpMailer to deliver one queued email. Used by the queue cron and by the
 * "send test email" feature.
 */

declare(strict_types=1);

final class Mailer
{
    /**
     * Deliver a single email_queue row. Returns true on success.
     * Handles auto-failover across the rotation candidate chain.
     */
    /** @var array<int,?array> per-batch campaign cache (same campaign repeats across the queue). */
    private static array $campaignCache = [];

    public static function deliver(array $queue): bool
    {
        $userId = (int) $queue['user_id'];
        $cid    = (int) ($queue['campaign_id'] ?? 0);
        if ($cid && !array_key_exists($cid, self::$campaignCache)) {
            self::$campaignCache[$cid] = Campaign::find($cid);
        }
        $campaign = $cid ? self::$campaignCache[$cid] : null;

        // Respect the global / campaign suppression list.
        if (Unsubscribe::isSuppressed($userId, $queue['email'], $campaign['id'] ?? null)) {
            db()->prepare("UPDATE email_queue SET status='skipped', error='unsubscribed' WHERE id = ?")
                ->execute([(int) $queue['id']]);
            return false;
        }

        $trackOpens  = $campaign === null ? true : (int) $campaign['track_opens'] === 1;
        $trackClicks = $campaign === null ? true : (int) $campaign['track_clicks'] === 1;

        // Free-plan branding: a local logo is embedded (CID) so it shows in any
        // inbox; the footer HTML references it via src="cid:…".
        $brand = Branding::forEmail($userId);
        $org   = self::orgFooterMeta($userId);
        $org['branding'] = $brand['html'];
        $inlineImages = $brand['image'] ? [$brand['image']] : [];

        $html = self::injectTracking(
            $queue['body_html'],
            $queue['tracking_id'],
            $trackOpens,
            $trackClicks,
            $org
        );

        $candidates = SmtpRotator::candidates($campaign ?? [], $userId, (int) $queue['id']);
        if ($candidates === []) {
            EmailQueue::markFailed((int) $queue['id'], 'No available SMTP account (check limits / enabled status).', false);
            self::log($queue, null, 'failed', 'No available SMTP account');
            return false;
        }

        $lastError = 'Unknown error';
        foreach ($candidates as $account) {
            $mailer = new SmtpMailer(
                $account['host'],
                (int) $account['port'],
                $account['encryption'],
                $account['username'],
                SmtpAccount::plainPassword($account)
            );

            $ok = $mailer->send(
                ['email' => $account['from_email'], 'name' => $account['from_name']],
                [$queue['email']],
                $queue['subject'],
                $html,
                self::headers($queue),
                $inlineImages
            );

            if ($ok) {
                EmailQueue::markSent((int) $queue['id'], (int) $account['id']);
                SmtpAccount::recordSend((int) $account['id']);
                SmtpAccount::setStatus((int) $account['id'], 'verified');
                if ($campaign) {
                    Campaign::incrementSent((int) $campaign['id']);
                    CampaignContact::setStatus((int) $campaign['id'], (int) $queue['contact_id'], 'sent');
                }
                self::log($queue, (int) $account['id'], 'sent', 'Delivered via ' . $account['label']);
                return true;
            }

            $lastError = $mailer->lastError();
            SmtpAccount::recordFailure((int) $account['id']);
            SmtpAccount::setStatus((int) $account['id'], 'failed');
            // continue to next candidate (auto-failover)
        }

        // All candidates failed.
        $retry = ((int) $queue['attempts'] + 1) < MAX_ATTEMPTS;
        EmailQueue::markFailed((int) $queue['id'], $lastError, $retry);
        self::log($queue, null, 'failed', $lastError);
        return false;
    }

    /**
     * One-off send used by "Send test email" and SMTP verification.
     * Bypasses the queue.
     */
    public static function sendNow(array $account, string $toEmail, string $subject, string $html, array $inlineImages = []): array
    {
        $mailer = new SmtpMailer(
            $account['host'],
            (int) $account['port'],
            $account['encryption'],
            $account['username'],
            SmtpAccount::plainPassword($account)
        );
        $ok = $mailer->send(
            ['email' => $account['from_email'], 'name' => $account['from_name']],
            [$toEmail],
            $subject,
            $html,
            [],
            $inlineImages
        );
        return ['ok' => $ok, 'error' => $ok ? '' : $mailer->lastError()];
    }

    /**
     * Send a transactional/system email (verification, password reset).
     * Prefers a specific user's enabled SMTP, then any enabled SMTP on the
     * platform, and finally falls back to PHP mail().
     */
    public static function sendSystem(string $to, string $subject, string $html, ?int $userId = null): bool
    {
        $account = self::systemAccount($userId);
        if ($account !== null) {
            $res = self::sendNow($account, $to, $subject, $html);
            if ($res['ok']) {
                SystemLog::write('info', 'mail.system', "System email sent to {$to}: {$subject}");
                return true;
            }
            SystemLog::write('warning', 'mail.system', "SMTP system send failed ({$res['error']}); falling back to mail()");
        }

        // Fallback: PHP mail() (works on most hosts for low volume).
        $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n"
                 . 'From: ' . APP_NAME . ' <no-reply@' . $host . ">\r\n";
        return @mail($to, $subject, $html, $headers);
    }

    /** Pick an enabled SMTP account to send transactional mail through. */
    private static function systemAccount(?int $userId): ?array
    {
        if ($userId !== null) {
            $stmt = db()->prepare("SELECT * FROM smtp_accounts WHERE user_id = ? AND is_enabled = 1 ORDER BY priority ASC LIMIT 1");
            $stmt->execute([$userId]);
            if ($row = $stmt->fetch()) {
                return $row;
            }
        }
        // Any enabled account on the platform (e.g. the admin's).
        $row = db()->query("SELECT * FROM smtp_accounts WHERE is_enabled = 1 ORDER BY priority ASC LIMIT 1")->fetch();
        return $row ?: null;
    }

    // ----------------------------------------------------------------

    private static function headers(array $queue): array
    {
        $unsub = url('unsubscribe.php?t=' . $queue['tracking_id']);
        return [
            'List-Unsubscribe'      => '<' . $unsub . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'X-Mailer'              => APP_NAME,
        ];
    }

    /** Inject the open pixel, rewrite links for click tracking, add unsub footer. */
    public static function injectTracking(string $html, string $trackingId, bool $opens, bool $clicks, array $org = []): string
    {
        if ($clicks) {
            $html = preg_replace_callback(
                '/<a\b([^>]*?)href=(["\'])(https?:\/\/[^"\']+)\2/i',
                static function ($m) use ($trackingId) {
                    $target = $m[3];
                    $tracked = url('track/click.php?t=' . $trackingId . '&u=' . self::b64url($target));
                    return '<a' . $m[1] . 'href=' . $m[2] . $tracked . $m[2];
                },
                $html
            );
        }

        $footer = self::complianceFooter($trackingId, $org);
        // Free-plan "powered by" branding (empty string for paid plans).
        $footer .= (string) ($org['branding'] ?? '');
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $footer . '</body>', $html);
        } else {
            $html .= $footer;
        }

        if ($opens) {
            $pixel = '<img src="' . url('track/open.php?t=' . $trackingId) . '" width="1" height="1" alt="" style="display:none">';
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $pixel . '</body>', $html);
            } else {
                $html .= $pixel;
            }
        }

        return $html;
    }

    /** Sender org details (company, postal address, website) for the footer. */
    private static array $orgMetaCache = [];
    public static function orgFooterMeta(int $userId): array
    {
        if (isset(self::$orgMetaCache[$userId])) {
            return self::$orgMetaCache[$userId];
        }
        $u = User::find($userId);
        return self::$orgMetaCache[$userId] = [
            'company'  => $u['company'] ?? '',
            'address'  => $u['org_address'] ?? '',
            'website'  => $u['org_website'] ?? '',
            'branding' => Branding::footerFor($userId),
        ];
    }

    /**
     * CAN-SPAM / GDPR-friendly footer: sender identity + physical mailing
     * address + one-click unsubscribe. Always appended to every campaign send.
     */
    public static function complianceFooter(string $trackingId, array $org = []): string
    {
        $unsub   = url('unsubscribe.php?t=' . $trackingId);
        $company = trim((string) ($org['company'] ?? ''));
        $address = trim((string) ($org['address'] ?? ''));
        $website = trim((string) ($org['website'] ?? ''));

        $identity = [];
        if ($company !== '') {
            $identity[] = '<strong>' . e($company) . '</strong>';
        }
        if ($address !== '') {
            $identity[] = e($address);
        }
        if ($website !== '') {
            $w = preg_match('#^https?://#', $website) ? $website : 'https://' . $website;
            $identity[] = '<a href="' . e($w) . '" style="color:#888">' . e(preg_replace('#^https?://#', '', $website)) . '</a>';
        }
        $identityLine = $identity ? implode(' &nbsp;·&nbsp; ', $identity) . '<br>' : '';
        $who = $company !== '' ? e($company) : 'our list';

        return '<div style="margin-top:24px;padding-top:14px;border-top:1px solid #eee;font:12px Arial,sans-serif;color:#999;text-align:center;line-height:1.6">'
            . $identityLine
            . 'You received this email because you subscribed to ' . $who . '.<br>'
            . '<a href="' . $unsub . '" style="color:#888;text-decoration:underline">Unsubscribe</a>'
            . ' from these emails.'
            . '</div>';
    }

    public static function b64url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/')) ?: '';
    }

    private static function log(array $queue, ?int $smtpId, string $event, string $message): void
    {
        EmailLog::record([
            'user_id'     => (int) $queue['user_id'],
            'campaign_id' => $queue['campaign_id'] ?? null,
            'queue_id'    => (int) $queue['id'],
            'contact_id'  => (int) $queue['contact_id'],
            'smtp_id'     => $smtpId,
            'email'       => $queue['email'],
            'event'       => $event,
            'message'     => mb_substr($message, 0, 500),
        ]);
    }
}
