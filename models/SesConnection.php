<?php

declare(strict_types=1);

/**
 * Platform-level Amazon SES connection — configured once by the super-admin,
 * not per tenant (a singleton row, not scoped by user). Every tenant's
 * domain-based campaigns send through it; `user_id` on the row just records
 * which admin last configured it, for audit purposes.
 */
final class SesConnection extends Model
{
    protected static string $table = 'ses_connections';

    public static function platform(): ?array
    {
        $row = db()->query('SELECT * FROM `ses_connections` ORDER BY id ASC LIMIT 1')->fetch();
        return $row ?: null;
    }

    /** Decrypted credentials, ready to hand to Ses::sendRaw()/verify(). */
    public static function credentials(array $conn): array
    {
        return [
            'access_key' => Crypto::decrypt($conn['access_key']),
            'secret_key' => Crypto::decrypt($conn['secret_key']),
            'region'     => $conn['region'],
        ];
    }

    /** Create or replace the platform's single SES connection. */
    public static function save(int $adminUserId, string $accessKey, string $secretKey, string $region): int
    {
        $existing = self::platform();
        $data = [
            'user_id'     => $adminUserId,
            'access_key'  => Crypto::encrypt($accessKey),
            'secret_key'  => Crypto::encrypt($secretKey),
            'region'      => $region,
            'verified_at' => null,
            'last_error'  => null,
        ];
        if ($existing) {
            self::update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        $data['webhook_token'] = bin2hex(random_bytes(16));
        return self::insert($data);
    }

    public static function markVerified(int $id, bool $ok, string $error = ''): void
    {
        self::update($id, [
            'verified_at' => $ok ? date('Y-m-d H:i:s') : null,
            'last_error'  => $ok ? null : mb_substr($error, 0, 255),
        ]);
    }
}
