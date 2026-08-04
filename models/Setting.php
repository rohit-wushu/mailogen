<?php

declare(strict_types=1);

/**
 * Simple admin-configurable key/value store (app_settings table).
 * Values are cached per-request.
 */
final class Setting extends Model
{
    protected static string $table = 'app_settings';

    /** @var array<string,?string> */
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, self::$cache)) {
            $v = db_one('SELECT v FROM app_settings WHERE k = ?', [$key]);
            self::$cache[$key] = $v === null ? null : (string) $v;
        }
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        db()->prepare('INSERT INTO app_settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute([$key, $value]);
        self::$cache[$key] = $value;
    }
}
