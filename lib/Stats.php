<?php
/**
 * Aggregated metrics for dashboards and reports.
 */

declare(strict_types=1);

final class Stats
{
    /**
     * First-run setup checklist (used by the dashboard timeline and the
     * sidebar's "Basic setup" progress widget).
     */
    public static function onboardingProgress(array $user): array
    {
        $uid  = (int) $user['id'];
        $sent = (int) db_one("SELECT COUNT(*) FROM email_logs WHERE user_id = ? AND event = 'sent'", [$uid]);

        // Sending is domain-based now (verified domain, delivered through the
        // platform's own Amazon SES connection — an admin-managed, platform-wide
        // setting, not a per-tenant one); a legacy SMTP account still counts so
        // existing setups aren't penalised.
        $domainVerified = (int) db_one('SELECT COUNT(*) FROM domains WHERE user_id = ? AND is_verified = 1', [$uid]) > 0;
        $smtpConnected  = (int) db_one('SELECT COUNT(*) FROM smtp_accounts WHERE user_id = ?', [$uid]) > 0;
        $sendingReady   = $domainVerified || $smtpConnected;
        $sendStep = $domainVerified
            ? ['title' => 'Set up sending', 'desc' => 'Verify a domain to send from.', 'url' => 'domains']
            : ['title' => 'Verify a sending domain', 'desc' => 'Add SPF/DKIM/DMARC for the domain you send from.', 'url' => 'domains'];
        $steps = [
            ['done' => $sendingReady, 'title' => $sendStep['title'], 'desc' => $sendStep['desc'], 'url' => $sendStep['url'], 'icon' => 'patch-check'],
            ['done' => (int) db_one('SELECT COUNT(*) FROM contacts WHERE user_id = ?', [$uid]) > 0,
             'title' => 'Add contacts', 'desc' => 'Import a CSV or add them manually.', 'url' => 'contacts/import', 'icon' => 'people'],
            ['done' => trim((string) ($user['org_address'] ?? '')) !== '',
             'title' => 'Set your mailing address', 'desc' => 'Required by law on every email footer.', 'url' => 'settings', 'icon' => 'geo-alt'],
            ['done' => (int) db_one('SELECT COUNT(*) FROM campaigns WHERE user_id = ?', [$uid]) > 0,
             'title' => 'Create a campaign', 'desc' => 'Design your first email.', 'url' => 'campaigns/edit', 'icon' => 'megaphone'],
            ['done' => $sent > 0,
             'title' => 'Send your first email', 'desc' => 'Launch a campaign or send a test.', 'url' => 'campaigns', 'icon' => 'send'],
        ];
        $doneCount = count(array_filter($steps, fn ($s) => $s['done']));
        return ['steps' => $steps, 'done' => $doneCount, 'total' => count($steps), 'complete' => $doneCount === count($steps)];
    }

    public static function dashboard(int $userId): array
    {
        $pdo = db();

        $one = static function (string $sql, array $p) use ($pdo): int {
            $st = $pdo->prepare($sql);
            $st->execute($p);
            return (int) $st->fetchColumn();
        };

        $sent       = $one("SELECT COUNT(*) FROM email_logs WHERE user_id = ? AND event = 'sent'", [$userId]);
        $failed     = $one("SELECT COUNT(*) FROM email_logs WHERE user_id = ? AND event = 'failed'", [$userId]);
        $opensTotal = $one("SELECT COUNT(*) FROM opens WHERE user_id = ?", [$userId]);
        $clicksTot  = $one("SELECT COUNT(*) FROM clicks WHERE user_id = ?", [$userId]);
        // Unique opens/clicks for accurate rates.
        $uOpens  = $one("SELECT COUNT(DISTINCT contact_id) FROM opens WHERE user_id = ?", [$userId]);
        $uClicks = $one("SELECT COUNT(DISTINCT contact_id) FROM clicks WHERE user_id = ?", [$userId]);

        $delivered   = max(0, $sent);
        $unsubCount  = $one("SELECT COUNT(*) FROM unsubscribes WHERE user_id = ?", [$userId]);

        return [
            'contacts'     => $one("SELECT COUNT(*) FROM contacts WHERE user_id = ?", [$userId]),
            'campaigns'    => $one("SELECT COUNT(*) FROM campaigns WHERE user_id = ?", [$userId]),
            'sent'         => $sent,
            'delivered'    => $delivered,
            'failed'       => $failed,
            'opens'        => $opensTotal,
            'clicks'       => $clicksTot,
            'unsubscribed' => $unsubCount,
            'active_smtp'  => $one("SELECT COUNT(*) FROM smtp_accounts WHERE user_id = ? AND is_enabled = 1", [$userId]),
            'open_rate'    => $delivered > 0 ? round($uOpens / $delivered * 100, 1) : 0.0,
            'click_rate'   => $delivered > 0 ? round($uClicks / $delivered * 100, 1) : 0.0,
            'unsub_rate'   => $delivered > 0 ? round($unsubCount / $delivered * 100, 1) : 0.0,
            'bounce_rate'  => $sent > 0 ? round($failed / $sent * 100, 1) : 0.0,
            'queue_pending'=> EmailQueue::pendingCount($userId),
        ];
    }

    /**
     * Rich stat cards for the dashboard: value, % delta vs the previous
     * period, and a sparkline series — one entry per card.
     */
    public static function dashboardCards(int $userId): array
    {
        $d = self::dashboard($userId);

        $sent   = self::dailyCounts($userId, 'email_logs', 'sent', 14);
        $opens  = self::dailyCounts($userId, 'opens', null, 14);
        $clicks = self::dailyCounts($userId, 'clicks', null, 14);
        $unsub  = self::dailyCounts($userId, 'unsubscribes', null, 14);
        $newC   = self::dailyCounts($userId, 'contacts', null, 14);
        $newCmp = self::dailyCounts($userId, 'campaigns', null, 14);
        $newSmtp = self::dailyCounts($userId, 'smtp_accounts', null, 14);

        // Per-day open/click rate sparkline (guard divide-by-zero).
        $rate = static function (array $num, array $den): array {
            $out = [];
            foreach ($num as $i => $n) {
                $out[] = ($den[$i] ?? 0) > 0 ? round($n / $den[$i] * 100, 1) : 0;
            }
            return $out;
        };

        $delta = static function (array $s): float {
            $half = (int) (count($s) / 2);
            $prev = array_sum(array_slice($s, 0, $half));
            $now  = array_sum(array_slice($s, $half));
            if ($prev <= 0) {
                return $now > 0 ? 100.0 : 0.0;
            }
            return round(($now - $prev) / $prev * 100, 0);
        };

        $cumulative = static function (array $daily, int $total): array {
            // Build a rising line ending at the current total.
            $base = $total - array_sum($daily);
            $run = $base; $out = [];
            foreach ($daily as $v) { $run += $v; $out[] = max(0, $run); }
            return $out;
        };

        return [
            ['key' => 'contacts',  'label' => 'Total Contacts', 'display' => (string) $d['contacts'],  'delta' => $delta($newC),   'spark' => $cumulative($newC, $d['contacts']),   'icon' => 'people-fill',         'grad' => 'violet'],
            ['key' => 'campaigns', 'label' => 'Campaigns',      'display' => (string) $d['campaigns'], 'delta' => $delta($newCmp), 'spark' => $cumulative($newCmp, $d['campaigns']), 'icon' => 'send-fill',           'grad' => 'pink'],
            ['key' => 'sent',      'label' => 'Emails Sent',    'display' => (string) $d['sent'],      'delta' => $delta($sent),   'spark' => $sent,                                'icon' => 'send-check-fill',     'grad' => 'green'],
            ['key' => 'delivered', 'label' => 'Delivered',      'display' => (string) $d['delivered'], 'delta' => $delta($sent),   'spark' => $sent,                                'icon' => 'envelope-check-fill', 'grad' => 'blue'],
            ['key' => 'open_rate', 'label' => 'Open Rate',      'display' => $d['open_rate'] . '%',    'delta' => $delta($opens),  'spark' => $rate($opens, $sent),                 'icon' => 'eye-fill',            'grad' => 'orange'],
            ['key' => 'click_rate','label' => 'Click Rate',     'display' => $d['click_rate'] . '%',   'delta' => $delta($clicks), 'spark' => $rate($clicks, $sent),                'icon' => 'cursor-fill',         'grad' => 'sky'],
            ['key' => 'unsub',     'label' => 'Unsubscribed',   'display' => (string) $d['unsubscribed'], 'delta' => $delta($unsub), 'spark' => $unsub,                            'icon' => 'person-dash-fill',    'grad' => 'red'],
            ['key' => 'smtp',      'label' => 'Active SMTP',    'display' => (string) $d['active_smtp'],  'delta' => $delta($newSmtp), 'spark' => $cumulative($newSmtp, $d['active_smtp']), 'icon' => 'hdd-network-fill',    'grad' => 'teal'],
        ];
    }

    /**
     * Rich stat cards for the Contacts page — same shape as dashboardCards()
     * (gradient icon, delta vs prior period, sparkline) so the two pages
     * match visually.
     */
    public static function contactsCards(int $userId): array
    {
        $totalContacts = Contact::countForUser($userId);
        $totalLists    = (int) db_one('SELECT COUNT(*) FROM contact_lists WHERE user_id = ?', [$userId]);
        $totalTags     = (int) db_one('SELECT COUNT(*) FROM contact_tags WHERE user_id = ?', [$userId]);
        $totalUnsub    = (int) db_one("SELECT COUNT(*) FROM contacts WHERE user_id = ? AND status = 'unsubscribed'", [$userId]);

        $newContacts = self::dailyCounts($userId, 'contacts', null, 14);
        $newLists    = self::dailyCounts($userId, 'contact_lists', null, 14);
        $newTags     = self::dailyCounts($userId, 'contact_tags', null, 14);
        $newUnsub    = self::dailyCounts($userId, 'unsubscribes', null, 14);

        $delta = static function (array $s): float {
            $half = (int) (count($s) / 2);
            $prev = array_sum(array_slice($s, 0, $half));
            $now  = array_sum(array_slice($s, $half));
            if ($prev <= 0) {
                return $now > 0 ? 100.0 : 0.0;
            }
            return round(($now - $prev) / $prev * 100, 0);
        };

        $cumulative = static function (array $daily, int $total): array {
            $base = $total - array_sum($daily);
            $run = $base; $out = [];
            foreach ($daily as $v) { $run += $v; $out[] = max(0, $run); }
            return $out;
        };

        return [
            ['key' => 'contacts', 'label' => 'Total Contacts', 'display' => (string) $totalContacts, 'delta' => $delta($newContacts), 'spark' => $cumulative($newContacts, $totalContacts), 'icon' => 'people-fill',      'grad' => 'violet'],
            ['key' => 'lists',    'label' => 'Lists',          'display' => (string) $totalLists,    'delta' => $delta($newLists),    'spark' => $cumulative($newLists, $totalLists),       'icon' => 'collection-fill',  'grad' => 'blue'],
            ['key' => 'tags',     'label' => 'Tags',           'display' => (string) $totalTags,     'delta' => $delta($newTags),     'spark' => $cumulative($newTags, $totalTags),         'icon' => 'tags-fill',        'grad' => 'orange'],
            ['key' => 'unsub',    'label' => 'Unsubscribed',   'display' => (string) $totalUnsub,    'delta' => $delta($newUnsub),    'spark' => $newUnsub,                                 'icon' => 'person-dash-fill', 'grad' => 'red'],
        ];
    }

    /** Daily counts for a table over the last N days, oldest→newest (length N). */
    private static function dailyCounts(int $userId, string $table, ?string $event, int $days): array
    {
        $buckets = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $buckets[date('Y-m-d', strtotime("-$i day"))] = 0;
        }
        $where = $event !== null ? "AND event = " . db()->quote($event) : '';
        $sql = "SELECT DATE(created_at) d, COUNT(*) c FROM `$table`
                WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) $where
                GROUP BY DATE(created_at)";
        $st = db()->prepare($sql);
        $st->execute([$userId, $days]);
        foreach ($st->fetchAll() as $row) {
            if (isset($buckets[$row['d']])) {
                $buckets[$row['d']] = (int) $row['c'];
            }
        }
        return array_values($buckets);
    }

    /** Daily time-series for the last N days: sends, opens, clicks. */
    public static function timeseries(int $userId, int $days = 14): array
    {
        $pdo = db();
        $labels = [];
        $map = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $labels[] = date('d M', strtotime($d));
            $map[$d] = ['sent' => 0, 'opens' => 0, 'clicks' => 0];
        }

        $collect = static function (string $table, string $event, string $key) use ($pdo, $userId, $days, &$map): void {
            $where = $table === 'email_logs' ? "AND event = '$event'" : '';
            $sql = "SELECT DATE(created_at) d, COUNT(*) c FROM $table
                    WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) $where
                    GROUP BY DATE(created_at)";
            $st = $pdo->prepare($sql);
            $st->execute([$userId, $days]);
            foreach ($st->fetchAll() as $row) {
                if (isset($map[$row['d']])) {
                    $map[$row['d']][$key] = (int) $row['c'];
                }
            }
        };
        $collect('email_logs', 'sent', 'sent');
        $collect('opens', '', 'opens');
        $collect('clicks', '', 'clicks');

        return [
            'labels' => $labels,
            'sent'   => array_column(array_values($map), 'sent'),
            'opens'  => array_column(array_values($map), 'opens'),
            'clicks' => array_column(array_values($map), 'clicks'),
        ];
    }

    /** Per-campaign performance for the reports page. */
    public static function campaignReport(int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT c.id, c.name, c.status, c.total_recipients, c.sent_count,
                (SELECT COUNT(*) FROM email_logs l WHERE l.campaign_id = c.id AND l.event='sent')   AS sent,
                (SELECT COUNT(*) FROM email_logs l WHERE l.campaign_id = c.id AND l.event='failed') AS failed,
                (SELECT COUNT(DISTINCT o.contact_id) FROM opens o  WHERE o.campaign_id = c.id)        AS opened,
                (SELECT COUNT(DISTINCT k.contact_id) FROM clicks k WHERE k.campaign_id = c.id)        AS clicked,
                (SELECT COUNT(*) FROM unsubscribes u WHERE u.campaign_id = c.id)                      AS unsubscribed
             FROM campaigns c WHERE c.user_id = ? ORDER BY c.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function smtpReport(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT id, label, provider, sent_total, fail_total, sent_today, daily_limit, last_status, is_enabled
             FROM smtp_accounts WHERE user_id = ? ORDER BY sent_total DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Admin-wide totals. */
    public static function admin(): array
    {
        $pdo = db();
        $q = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();
        return [
            'users'      => $q('SELECT COUNT(*) FROM users'),
            'campaigns'  => $q('SELECT COUNT(*) FROM campaigns'),
            'sent'       => $q("SELECT COUNT(*) FROM email_logs WHERE event='sent'"),
            'smtp'       => $q('SELECT COUNT(*) FROM smtp_accounts'),
            'contacts'   => $q('SELECT COUNT(*) FROM contacts'),
            'revenue'    => (float) $pdo->query(
                "SELECT COALESCE(SUM(CASE WHEN u.sending_mode = 'domain' THEN p.price_domain ELSE p.price_smtp END),0)
                 FROM users u JOIN plans p ON p.id = u.plan_id"
            )->fetchColumn(),
        ];
    }
}
