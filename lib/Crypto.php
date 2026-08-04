<?php
/**
 * Symmetric encryption for sensitive at-rest values (SMTP passwords).
 * AES-256-CBC with an HMAC tag (encrypt-then-MAC). Key derived from APP_KEY.
 */

declare(strict_types=1);

final class Crypto
{
    private const CIPHER = 'aes-256-cbc';

    private static function key(): string
    {
        // Normalise APP_KEY to a 32-byte key.
        return hash('sha256', APP_KEY, true);
    }

    public static function encrypt(string $plain): string
    {
        $key = self::key();
        $iv  = random_bytes(16);
        $ct  = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($ct === false) {
            throw new RuntimeException('Encryption failed.');
        }
        $mac = hash_hmac('sha256', $iv . $ct, $key, true);
        return base64_encode($iv . $mac . $ct);
    }

    public static function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 48) {
            return '';
        }
        $key = self::key();
        $iv  = substr($raw, 0, 16);
        $mac = substr($raw, 16, 32);
        $ct  = substr($raw, 48);

        $calc = hash_hmac('sha256', $iv . $ct, $key, true);
        if (!hash_equals($calc, $mac)) {
            return '';
        }

        $plain = openssl_decrypt($ct, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }
}
