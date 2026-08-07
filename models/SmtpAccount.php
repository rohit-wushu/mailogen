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
               AND (domain_id IS NULL OR domain_id IN (SELECT id FROM domains WHERE is_verified = 1))
             ORDER BY priority ASC, id ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** True if this account is enabled, under quota, and (when linked) its domain is verified. */
    public static function isSendable(array $account): bool
    {
        if ((int) $account['is_enabled'] !== 1 || (int) $account['sent_today'] >= (int) $account['daily_limit']) {
            return false;
        }
        if (empty($account['domain_id'])) {
            return true;
        }
        $stmt = db()->prepare('SELECT is_verified FROM domains WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $account['domain_id']]);
        $row = $stmt->fetch();
        return $row !== false && (int) $row['is_verified'] === 1;
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
