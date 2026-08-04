<?php

declare(strict_types=1);

final class ContactList extends Model
{
    protected static string $table = 'contact_lists';

    /** Lists with their active contact counts. */
    public static function withCounts(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT l.*, (SELECT COUNT(*) FROM contacts c WHERE c.list_id = l.id) AS contact_count
             FROM contact_lists l WHERE l.user_id = ? ORDER BY l.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
