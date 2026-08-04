<?php

declare(strict_types=1);

final class ContactTag extends Model
{
    protected static string $table = 'contact_tags';

    public static function withCounts(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, (SELECT COUNT(*) FROM contact_tag_map m WHERE m.tag_id = t.id) AS contact_count
             FROM contact_tags t WHERE t.user_id = ? ORDER BY t.name ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function firstOrCreate(int $userId, string $name): int
    {
        $name = trim($name);
        $stmt = db()->prepare('SELECT id FROM contact_tags WHERE user_id = ? AND name = ? LIMIT 1');
        $stmt->execute([$userId, $name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        return self::insert(['user_id' => $userId, 'name' => $name]);
    }

    public static function attach(int $contactId, int $tagId): void
    {
        db()->prepare('INSERT IGNORE INTO contact_tag_map (contact_id, tag_id) VALUES (?, ?)')
            ->execute([$contactId, $tagId]);
    }

    public static function forContact(int $contactId): array
    {
        $stmt = db()->prepare(
            'SELECT t.* FROM contact_tags t
             JOIN contact_tag_map m ON m.tag_id = t.id
             WHERE m.contact_id = ?'
        );
        $stmt->execute([$contactId]);
        return $stmt->fetchAll();
    }
}
