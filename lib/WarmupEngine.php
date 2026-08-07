<?php
/**
 * Ramps a warm-up-enabled SMTP account's `daily_limit` up automatically
 * from a low starting point toward its target ceiling, one step per day.
 * Holds (doesn't advance or reduce) on any day where Reputation::isHealthy()
 * looks bad — reuses the same bounce/complaint window Reputation.php
 * already tracks for auto-pause, rather than inventing a second signal.
 * Run once daily (see cron/warmup.php).
 */

declare(strict_types=1);

final class WarmupEngine
{
    public const START_LIMIT    = 50;
    private const GROWTH_FACTOR = 1.5; // ~50% more per day until the target is reached

    /** Step every warm-up-enabled account forward one day. Returns how many actually advanced. */
    public static function run(): int
    {
        $stmt = db()->query('SELECT * FROM smtp_accounts WHERE warmup_enabled = 1');
        $advanced = 0;
        foreach ($stmt->fetchAll() as $account) {
            if (self::step($account)) {
                $advanced++;
            }
        }
        return $advanced;
    }

    private static function step(array $account): bool
    {
        $id      = (int) $account['id'];
        $userId  = (int) $account['user_id'];
        $current = (int) $account['daily_limit'];
        $target  = (int) ($account['warmup_target_limit'] ?: $current);

        if (($account['warmup_last_step'] ?? null) === date('Y-m-d')) {
            return false; // already stepped today — one advance per calendar day
        }

        if ($target <= 0 || $current >= $target) {
            SmtpAccount::update($id, ['warmup_enabled' => 0]);
            SystemLog::write('info', 'smtp.warmup_complete', "SMTP account #{$id} finished warm-up at {$current}/day.", $userId);
            return false;
        }

        if (!Reputation::isHealthy($id)) {
            SystemLog::write('warning', 'smtp.warmup_paused', "SMTP account #{$id} warm-up held at {$current}/day — recent bounce/complaint rate looks unhealthy.", $userId);
            return false;
        }

        $next = $current <= 0 ? self::START_LIMIT : (int) ceil($current * self::GROWTH_FACTOR);
        $next = min($next, $target);
        SmtpAccount::update($id, [
            'daily_limit'      => $next,
            'warmup_day'       => (int) $account['warmup_day'] + 1,
            'warmup_last_step' => date('Y-m-d'),
        ]);
        SystemLog::write('info', 'smtp.warmup_step', "SMTP account #{$id} warm-up day " . ((int) $account['warmup_day'] + 1) . ": daily limit {$current} -> {$next}.", $userId);
        return true;
    }
}
