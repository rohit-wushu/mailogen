<?php
/**
 * Per-SMTP-account deliverability reputation: rolling bounce/complaint rate
 * over the account's most recent sends, with automatic pause when either
 * threshold is breached. Called after every bounce/complaint webhook event
 * (see public/webhooks/*.php) and safe to call from anywhere else too.
 *
 * Abuse-prevention note: this is the mechanism that keeps one tenant's bad
 * list (or a compromised account being used to blast spam) from burning the
 * platform's overall sender reputation across every other tenant sharing the
 * same outbound path/IP reputation pool.
 */

declare(strict_types=1);

final class Reputation
{
    /** Rolling window: judge each account on its most recent sends, not all-time history. */
    private const WINDOW = 500;
    /** Don't act on a handful of events — avoids pausing a brand-new account on 1 bounce out of 3 sends. */
    private const MIN_SAMPLE = 25;
    private const BOUNCE_THRESHOLD    = 0.05;   // 5%
    private const COMPLAINT_THRESHOLD = 0.001;  // 0.1%

    /** @return array{sample:int,bounce_rate:float,complaint_rate:float} */
    public static function rates(int $smtpId): array
    {
        $stmt = db()->prepare(
            "SELECT event, bounce_type FROM email_logs
             WHERE smtp_id = ? AND event IN ('sent','bounced','complained')
             ORDER BY id DESC LIMIT " . self::WINDOW
        );
        $stmt->execute([$smtpId]);
        $rows = $stmt->fetchAll();

        $total = count($rows);
        $hardBounces = 0;
        $complaints = 0;
        foreach ($rows as $r) {
            if ($r['event'] === 'bounced' && $r['bounce_type'] !== 'soft') {
                $hardBounces++;
            } elseif ($r['event'] === 'complained') {
                $complaints++;
            }
        }

        return [
            'sample'         => $total,
            'bounce_rate'    => $total > 0 ? $hardBounces / $total : 0.0,
            'complaint_rate' => $total > 0 ? $complaints / $total : 0.0,
        ];
    }

    /** Below MIN_SAMPLE there's not enough data to judge — treated as healthy so a brand-new account can ramp. */
    public static function isHealthy(int $smtpId): bool
    {
        $r = self::rates($smtpId);
        if ($r['sample'] < self::MIN_SAMPLE) {
            return true;
        }
        return $r['bounce_rate'] < self::BOUNCE_THRESHOLD && $r['complaint_rate'] < self::COMPLAINT_THRESHOLD;
    }

    /** Re-evaluate an account's reputation and auto-pause it if either threshold is breached. */
    public static function checkAndThrottle(int $smtpId): void
    {
        $account = SmtpAccount::find($smtpId);
        if (!$account || (int) $account['is_enabled'] === 0) {
            return; // already paused/disabled — nothing to do
        }

        $r = self::rates($smtpId);
        if ($r['sample'] < self::MIN_SAMPLE) {
            return;
        }

        $reason = null;
        if ($r['complaint_rate'] > self::COMPLAINT_THRESHOLD) {
            $reason = sprintf('Complaint rate %.2f%% exceeds the %.1f%% threshold (last %d sends).', $r['complaint_rate'] * 100, self::COMPLAINT_THRESHOLD * 100, $r['sample']);
        } elseif ($r['bounce_rate'] > self::BOUNCE_THRESHOLD) {
            $reason = sprintf('Bounce rate %.1f%% exceeds the %.0f%% threshold (last %d sends).', $r['bounce_rate'] * 100, self::BOUNCE_THRESHOLD * 100, $r['sample']);
        }
        if ($reason === null) {
            return;
        }

        SmtpAccount::update($smtpId, [
            'is_enabled'     => 0,
            'auto_paused_at' => date('Y-m-d H:i:s'),
            'pause_reason'   => $reason,
        ]);
        SystemLog::write('warning', 'reputation.autopause', "SMTP account #{$smtpId} auto-paused: {$reason}", (int) $account['user_id']);
    }

    // ---- Platform-wide (super-admin deliverability dashboard) -----------
    //
    // Domain-based (SES) sends have no single "account" to pause the way an
    // SMTP account can be, and they all share the platform's one SES
    // connection — so instead of auto-throttling a tenant, risky tenants are
    // just flagged for the admin to review on the Deliverability page.

    /** Bounce/complaint rate across every tenant's sends, most recent window first. */
    public static function platformRates(int $window = 2000): array
    {
        return self::ratesFromWhere('1=1', [], $window);
    }

    /** One tenant's bounce/complaint rate across ALL their sends (SMTP + SES). */
    public static function ratesByUser(int $userId, int $window = self::WINDOW): array
    {
        return self::ratesFromWhere('user_id = ?', [$userId], $window);
    }

    /**
     * Tenants whose recent bounce/complaint rate breaches the same
     * thresholds used for SMTP auto-pause, worst first. Read-only — nothing
     * is paused automatically for domain-based sends.
     *
     * @return array<int,array{user_id:int,name:string,email:string,sample:int,bounce_rate:float,complaint_rate:float}>
     */
    public static function riskyTenants(int $limit = 10): array
    {
        $stmt = db()->query(
            "SELECT DISTINCT user_id FROM email_logs WHERE event IN ('bounced','complained')
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $risky = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            $r = self::ratesByUser((int) $uid);
            if ($r['sample'] < self::MIN_SAMPLE) {
                continue;
            }
            if ($r['complaint_rate'] > self::COMPLAINT_THRESHOLD || $r['bounce_rate'] > self::BOUNCE_THRESHOLD) {
                $user = User::find((int) $uid);
                if (!$user) {
                    continue;
                }
                $risky[] = [
                    'user_id'        => (int) $uid,
                    'name'           => $user['name'],
                    'email'          => $user['email'],
                    'sample'         => $r['sample'],
                    'bounce_rate'    => $r['bounce_rate'],
                    'complaint_rate' => $r['complaint_rate'],
                ];
            }
        }
        usort($risky, static fn ($a, $b) => ($b['complaint_rate'] + $b['bounce_rate']) <=> ($a['complaint_rate'] + $a['bounce_rate']));
        return array_slice($risky, 0, $limit);
    }

    /** Log a warning if a tenant just crossed a threshold — called after a fresh bounce/complaint event. */
    public static function checkAndFlagTenant(int $userId): void
    {
        $r = self::ratesByUser($userId);
        if ($r['sample'] < self::MIN_SAMPLE) {
            return;
        }
        $reason = null;
        if ($r['complaint_rate'] > self::COMPLAINT_THRESHOLD) {
            $reason = sprintf('Complaint rate %.2f%% exceeds the %.1f%% threshold (last %d sends).', $r['complaint_rate'] * 100, self::COMPLAINT_THRESHOLD * 100, $r['sample']);
        } elseif ($r['bounce_rate'] > self::BOUNCE_THRESHOLD) {
            $reason = sprintf('Bounce rate %.1f%% exceeds the %.0f%% threshold (last %d sends).', $r['bounce_rate'] * 100, self::BOUNCE_THRESHOLD * 100, $r['sample']);
        }
        if ($reason !== null) {
            SystemLog::write('warning', 'reputation.tenant_risk', "User #{$userId} sending domain risk: {$reason}", $userId);
        }
    }

    private static function ratesFromWhere(string $where, array $params, int $window): array
    {
        $stmt = db()->prepare(
            "SELECT event, bounce_type FROM email_logs
             WHERE $where AND event IN ('sent','bounced','complained')
             ORDER BY id DESC LIMIT " . (int) $window
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $total = count($rows);
        $hardBounces = 0;
        $complaints = 0;
        foreach ($rows as $r) {
            if ($r['event'] === 'bounced' && $r['bounce_type'] !== 'soft') {
                $hardBounces++;
            } elseif ($r['event'] === 'complained') {
                $complaints++;
            }
        }

        return [
            'sample'         => $total,
            'bounce_rate'    => $total > 0 ? $hardBounces / $total : 0.0,
            'complaint_rate' => $total > 0 ? $complaints / $total : 0.0,
        ];
    }
}
