<?php

declare(strict_types=1);

final class ContactList extends Model
{
    protected static string $table = 'contact_lists';

    /** Lists with their contact counts (membership lives in contact_list_map). */
    public static function withCounts(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT l.*, (SELECT COUNT(*) FROM contact_list_map m WHERE m.list_id = l.id) AS contact_count
             FROM contact_lists l WHERE l.user_id = ? ORDER BY l.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Put a contact in a list. Adding is additive on purpose — a contact
     * stays in every other list it already belongs to, so importing a sheet
     * into one list never empties another.
     */
    public static function addMember(int $contactId, ?int $listId): void
    {
        if (!$listId) {
            return;
        }
        db()->prepare('INSERT IGNORE INTO contact_list_map (contact_id, list_id) VALUES (?, ?)')
            ->execute([$contactId, $listId]);
    }

    public static function removeMember(int $contactId, int $listId): void
    {
        db()->prepare('DELETE FROM contact_list_map WHERE contact_id = ? AND list_id = ?')
            ->execute([$contactId, $listId]);
    }

    /**
     * Replace a contact's list membership with exactly $listIds. Used by the
     * contact edit form, where the multi-select is the full intended set.
     * Only lists owned by $userId are accepted.
     *
     * @param array<int|string> $listIds
     */
    public static function setMemberships(int $contactId, int $userId, array $listIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $listIds))));

        db()->prepare('DELETE FROM contact_list_map WHERE contact_id = ?')->execute([$contactId]);
        if ($ids === []) {
            return;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $owned = db()->prepare("SELECT id FROM contact_lists WHERE user_id = ? AND id IN ($in)");
        $owned->execute(array_merge([$userId], $ids));

        $ins = db()->prepare('INSERT IGNORE INTO contact_list_map (contact_id, list_id) VALUES (?, ?)');
        foreach ($owned->fetchAll(PDO::FETCH_COLUMN) as $listId) {
            $ins->execute([$contactId, (int) $listId]);
        }
    }

    /** List ids a contact belongs to. @return int[] */
    public static function idsForContact(int $contactId): array
    {
        $stmt = db()->prepare('SELECT list_id FROM contact_list_map WHERE contact_id = ?');
        $stmt->execute([$contactId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * List ids keyed by contact id, to pre-select the edit form's multi-select
     * without an N+1 query per row.
     *
     * @param int[] $contactIds
     * @return array<int, int[]>
     */
    public static function idsByContact(array $contactIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $contactIds))));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT contact_id, list_id FROM contact_list_map WHERE contact_id IN ($in)");
        $stmt->execute($ids);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['contact_id']][] = (int) $row['list_id'];
        }
        return $out;
    }

    /**
     * List names keyed by contact id, for rendering a contacts table without
     * an N+1 query per row.
     *
     * @param int[] $contactIds
     * @return array<int, string[]>
     */
    public static function namesByContact(array $contactIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $contactIds))));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT m.contact_id, l.name
             FROM contact_list_map m
             JOIN contact_lists l ON l.id = m.list_id
             WHERE m.contact_id IN ($in)
             ORDER BY l.name"
        );
        $stmt->execute($ids);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['contact_id']][] = (string) $row['name'];
        }
        return $out;
    }
}
