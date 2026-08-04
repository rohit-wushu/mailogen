<?php

declare(strict_types=1);

final class SmtpAccount extends Model
{
    protected static string $table = 'smtp_accounts';

    /** Decrypt the stored password for use by the mailer. */
    public static function plainPassword(array $account): string
    {
        return Crypto::decrypt($account['password']);
    }

    public static function availableForUser(int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT * FROM smtp_accounts
             WHERE user_id = ? AND is_enabled = 1 AND sent_today < daily_limit
             ORDER BY priority ASC, id ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Reset daily counters when the date rolls over (also run by cron). */
    public static function resetDailyIfNeeded(): void
    {
        db()->query(
            "UPDATE smtp_accounts SET sent_today = 0, last_reset = CURDATE()
             WHERE last_reset IS NULL OR last_reset < CURDATE()"
        );
    }

    public static function recordSend(int $smtpId): void
    {
        db()->prepare('UPDATE smtp_accounts SET sent_today = sent_today + 1, sent_total = sent_total + 1 WHERE id = ?')
            ->execute([$smtpId]);
    }

    public static function recordFailure(int $smtpId): void
    {
        db()->prepare('UPDATE smtp_accounts SET fail_total = fail_total + 1 WHERE id = ?')
            ->execute([$smtpId]);
    }

    public static function setStatus(int $smtpId, string $status): void
    {
        db()->prepare('UPDATE smtp_accounts SET last_status = ?, last_checked = NOW() WHERE id = ?')
            ->execute([$status, $smtpId]);
    }
}
