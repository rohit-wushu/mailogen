<?php
/**
 * Builds the topbar notification feed from real account activity.
 *
 * There is no dedicated `notifications` table — instead we surface the events a
 * user actually cares about (failed sends, finished campaigns, SMTP problems,
 * an unverified email) straight from the existing tables. Cheap, always fresh,
 * nothing to keep in sync.
 */

declare(strict_types=1);

final class Notifications
{
    /**
     * Recent notifications for a user, newest first.
     *
     * @return array<int,array{type:string,icon:string,title:string,time:?string,url:string}>
     */
    public static function forUser(int $userId, bool $isVerified = true, int $limit = 8): array
    {
        $pdo   = db();
        $items = [];

        // --- Unverified email --------------------------------------------
        if (!$isVerified) {
            $items[] = [
                'type'  => 'warning',
                'icon'  => 'envelope-exclamation',
                'title' => 'Verify your email address to unlock sending.',
                'time'  => null,
                'url'   => url('settings'),
            ];
        }

        // --- Failed / bounced sends (last 7 days) ------------------------
        $st = $pdo->prepare(
            "SELECT COUNT(*) c, MAX(created_at) t
               FROM email_logs
              WHERE user_id = ? AND event IN ('failed','bounced')
                AND created_at >= (NOW() - INTERVAL 7 DAY)"
        );
        $st->execute([$userId]);
        $fail = $st->fetch();
        if ($fail && (int) $fail['c'] > 0) {
            $n = (int) $fail['c'];
            $items[] = [
                'type'  => 'error',
                'icon'  => 'exclamation-octagon',
                'title' => $n . ' email' . ($n === 1 ? '' : 's') . ' failed to deliver in the last 7 days.',
                'time'  => $fail['t'],
                'url'   => url('reports'),
            ];
        }

        // --- Recently completed campaigns --------------------------------
        $st = $pdo->prepare(
            "SELECT id, name, sent_count, updated_at
               FROM campaigns
              WHERE user_id = ? AND status = 'completed'
                AND updated_at >= (NOW() - INTERVAL 14 DAY)
              ORDER BY updated_at DESC LIMIT 3"
        );
        $st->execute([$userId]);
        foreach ($st->fetchAll() as $c) {
            $items[] = [
                'type'  => 'success',
                'icon'  => 'check-circle',
                'title' => 'Campaign “' . $c['name'] . '” finished — ' . (int) $c['sent_count'] . ' sent.',
                'time'  => $c['updated_at'],
                'url'   => url('campaigns/show?id=' . (int) $c['id']),
            ];
        }

        // --- SMTP accounts needing attention -----------------------------
        $st = $pdo->prepare(
            "SELECT from_email, last_status, is_enabled, sent_today, daily_limit, created_at
               FROM smtp_accounts
              WHERE user_id = ?
                AND (last_status = 'failed' OR is_enabled = 0 OR sent_today >= daily_limit)
              ORDER BY created_at DESC LIMIT 3"
        );
        $st->execute([$userId]);
        foreach ($st->fetchAll() as $s) {
            if ((int) $s['is_enabled'] === 0) {
                $title = 'SMTP account ' . $s['from_email'] . ' is disabled.';
            } elseif ($s['last_status'] === 'failed') {
                $title = 'SMTP account ' . $s['from_email'] . ' failed its last connection test.';
            } else {
                $title = 'SMTP account ' . $s['from_email'] . ' hit its daily send limit.';
            }
            $items[] = [
                'type'  => 'warning',
                'icon'  => 'hdd-network',
                'title' => $title,
                'time'  => $s['created_at'],
                'url'   => url('smtp'),
            ];
        }

        // --- Logged warnings / errors for this user ----------------------
        $st = $pdo->prepare(
            "SELECT level, message, created_at
               FROM system_logs
              WHERE user_id = ? AND level IN ('warning','error')
              ORDER BY created_at DESC LIMIT 3"
        );
        $st->execute([$userId]);
        foreach ($st->fetchAll() as $log) {
            $items[] = [
                'type'  => $log['level'] === 'error' ? 'error' : 'warning',
                'icon'  => 'info-circle',
                'title' => (string) $log['message'],
                'time'  => $log['created_at'],
                'url'   => url('dashboard'),
            ];
        }

        // Newest first, keeping the (timeless) verification nudge on top.
        usort($items, static function (array $a, array $b): int {
            if ($a['time'] === null) {
                return -1;
            }
            if ($b['time'] === null) {
                return 1;
            }
            return strcmp((string) $b['time'], (string) $a['time']);
        });

        return array_slice($items, 0, $limit);
    }

    /** Human-friendly relative time, e.g. "3h ago". */
    public static function ago(?string $ts): string
    {
        if ($ts === null || $ts === '') {
            return '';
        }
        $then = strtotime($ts);
        if ($then === false) {
            return '';
        }
        $diff = time() - $then;
        if ($diff < 60) {
            return 'just now';
        }
        foreach ([
            [31536000, 'y'],
            [2592000,  'mo'],
            [86400,    'd'],
            [3600,     'h'],
            [60,       'm'],
        ] as [$secs, $unit]) {
            if ($diff >= $secs) {
                return (int) floor($diff / $secs) . $unit . ' ago';
            }
        }
        return 'just now';
    }
}
