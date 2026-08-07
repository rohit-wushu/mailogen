<?php

declare(strict_types=1);

final class ApiKey extends Model
{
    protected static string $table = 'api_keys';

    /** Generate + store a new key for a tenant. Returns [row, rawKey] — the raw key is only ever available here. */
    public static function generate(int $userId, int $actorId, string $label): array
    {
        $raw = 'mgk_' . bin2hex(random_bytes(24));
        $id = self::insert([
            'user_id'    => $userId,
            'label'      => $label !== '' ? $label : 'API key',
            'key_prefix' => substr($raw, 0, 10),
            'key_hash'   => hash('sha256', $raw),
            'created_by' => $actorId,
        ]);
        return [self::find($id), $raw];
    }

    /** Look up the tenant a raw key belongs to, touching last_used_at. Null if invalid. */
    public static function resolve(string $rawKey): ?array
    {
        $stmt = db()->prepare('SELECT * FROM api_keys WHERE key_hash = ? LIMIT 1');
        $stmt->execute([hash('sha256', $rawKey)]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        db()->prepare('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
        return $row;
    }
}
