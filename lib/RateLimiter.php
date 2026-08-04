<?php
/**
 * Lightweight DB-backed rate limiter (fixed window). Dependency-free, works on
 * shared hosting. Used to throttle login/register/forgot and other abusable
 * endpoints against brute-force and automated abuse.
 */

declare(strict_types=1);

final class RateLimiter
{
    /**
     * Record a hit for $key and report whether the limit is now exceeded.
     * Returns true when the caller should be BLOCKED.
     */
    public static function tooMany(string $key, int $max, int $windowSeconds): bool
    {
        $now = time();
        $db  = db();

        $stmt = $db->prepare('SELECT hits, UNIX_TIMESTAMP(expires_at) AS exp FROM rate_limits WHERE bucket = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        // No record, or the previous window has expired → start a fresh window.
        if (!$row || (int) $row['exp'] < $now) {
            $exp = date('Y-m-d H:i:s', $now + $windowSeconds);
            $db->prepare(
                'INSERT INTO rate_limits (bucket, hits, expires_at) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE hits = 1, expires_at = VALUES(expires_at)'
            )->execute([$key, $exp]);
            return false;
        }

        if ((int) $row['hits'] >= $max) {
            return true;
        }
        $db->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?')->execute([$key]);
        return false;
    }

    /** Clear a bucket (e.g. after a successful login). */
    public static function clear(string $key): void
    {
        db()->prepare('DELETE FROM rate_limits WHERE bucket = ?')->execute([$key]);
    }

    /** Best-effort housekeeping; call occasionally from cron. */
    public static function purgeExpired(): void
    {
        db()->prepare('DELETE FROM rate_limits WHERE expires_at < NOW()')->execute();
    }
}
