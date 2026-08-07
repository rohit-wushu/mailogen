<?php
/**
 * Subscription plans, usage metering and quota enforcement.
 *
 * A user's effective plan is their assigned plan while it is active. If the
 * subscription has expired they fall back to the free "starter" limits. A
 * limit value of -1 means unlimited.
 */

declare(strict_types=1);

final class Billing
{
    /** Free fallback used when a user has no plan / an expired plan. */
    public static function freePlan(): array
    {
        static $free = null;
        if ($free === null) {
            $stmt = db()->prepare('SELECT * FROM plans ORDER BY price_smtp ASC, id ASC LIMIT 1');
            $stmt->execute();
            $free = $stmt->fetch() ?: [
                'id' => 0, 'name' => 'Starter', 'slug' => 'starter',
                'price_monthly' => 0, 'price_smtp' => 0, 'price_domain' => 0,
                'max_contacts' => 1000, 'max_campaigns' => 10, 'max_smtp' => 1, 'monthly_emails' => 1000,
            ];
        }
        return $free;
    }

    /** Whether the user's paid subscription is currently active (not expired). */
    public static function isActive(array $user): bool
    {
        if (empty($user['plan_id'])) {
            return false;                       // on the free plan
        }
        if (empty($user['plan_expires_at'])) {
            return true;                        // paid plan with no expiry set
        }
        return strtotime((string) $user['plan_expires_at']) >= time();
    }

    /**
     * The plan whose LIMITS currently apply to this user. `price_monthly` on
     * the returned array is normalized to what THIS user actually pays (their
     * chosen sending_mode), so every existing caller that reads price_monthly
     * off an effectivePlan() result keeps working without changes.
     */
    public static function effectivePlan(array $user): array
    {
        $plan = null;
        if (self::isActive($user) && !empty($user['plan_id'])) {
            $plan = Plan::find((int) $user['plan_id']);
        }
        $plan = $plan ?: self::freePlan();
        $plan['price_monthly'] = Plan::priceFor($plan, (string) ($user['sending_mode'] ?? 'smtp'));
        return $plan;
    }

    /**
     * Apply a plan to a user. Paid renewals extend from the later of now or the
     * existing expiry so time stacks; $months = 0 means no expiry (free plan).
     * Shared by user self-service (free switch) and admin approval.
     */
    public static function activate(int $userId, int $planId, int $months): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }
        $expires = null;
        if ($months > 0) {
            $base = !empty($user['plan_expires_at']) && strtotime((string) $user['plan_expires_at']) > time()
                ? strtotime((string) $user['plan_expires_at'])
                : time();
            $expires = date('Y-m-d H:i:s', strtotime("+{$months} months", $base));
        }
        User::update($userId, ['plan_id' => $planId, 'plan_expires_at' => $expires]);
    }

    // ---- Usage meters -------------------------------------------------
    public static function contactCount(int $uid): int
    {
        return (int) db_one('SELECT COUNT(*) FROM contacts WHERE user_id = ?', [$uid]);
    }

    public static function campaignCount(int $uid): int
    {
        return (int) db_one('SELECT COUNT(*) FROM campaigns WHERE user_id = ?', [$uid]);
    }

    public static function smtpCount(int $uid): int
    {
        return (int) db_one('SELECT COUNT(*) FROM smtp_accounts WHERE user_id = ?', [$uid]);
    }

    /** Emails actually sent in the current calendar month. */
    public static function emailsThisMonth(int $uid): int
    {
        return (int) db_one(
            "SELECT COUNT(*) FROM email_logs
             WHERE user_id = ? AND event = 'sent'
               AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",
            [$uid]
        );
    }

    // ---- Quota checks (true = allowed) --------------------------------
    private static function within(int $used, int $limit, int $add = 1): bool
    {
        return $limit < 0 || ($used + $add) <= $limit;   // -1 = unlimited
    }

    public static function canAddContacts(array $user, int $n = 1): bool
    {
        return self::within(self::contactCount((int) $user['id']), (int) self::effectivePlan($user)['max_contacts'], $n);
    }

    public static function canCreateCampaign(array $user): bool
    {
        return self::within(self::campaignCount((int) $user['id']), (int) self::effectivePlan($user)['max_campaigns']);
    }

    public static function canAddSmtp(array $user): bool
    {
        return self::within(self::smtpCount((int) $user['id']), (int) self::effectivePlan($user)['max_smtp']);
    }

    public static function canSendEmails(array $user, int $n): bool
    {
        return self::within(self::emailsThisMonth((int) $user['id']), (int) self::effectivePlan($user)['monthly_emails'], $n);
    }

    /** Remaining monthly-email allowance (PHP_INT_MAX when unlimited). */
    public static function remainingEmails(array $user): int
    {
        $limit = (int) self::effectivePlan($user)['monthly_emails'];
        return $limit < 0 ? PHP_INT_MAX : max(0, $limit - self::emailsThisMonth((int) $user['id']));
    }

    /** Snapshot used by the billing page UI. */
    public static function usage(array $user): array
    {
        $uid  = (int) $user['id'];
        $plan = self::effectivePlan($user);
        $rows = [
            ['key' => 'contacts',  'label' => 'Contacts',        'used' => self::contactCount($uid),    'limit' => (int) $plan['max_contacts'],   'icon' => 'people'],
            ['key' => 'emails',    'label' => 'Emails this month','used' => self::emailsThisMonth($uid), 'limit' => (int) $plan['monthly_emails'], 'icon' => 'send'],
            ['key' => 'campaigns', 'label' => 'Campaigns',        'used' => self::campaignCount($uid),    'limit' => (int) $plan['max_campaigns'],  'icon' => 'megaphone'],
            ['key' => 'smtp',      'label' => 'SMTP accounts',    'used' => self::smtpCount($uid),        'limit' => (int) $plan['max_smtp'],       'icon' => 'hdd-network'],
        ];
        foreach ($rows as &$r) {
            $r['unlimited'] = $r['limit'] < 0;
            $r['pct'] = $r['unlimited'] || $r['limit'] === 0 ? 0 : min(100, (int) round($r['used'] / $r['limit'] * 100));
        }
        return $rows;
    }
}
