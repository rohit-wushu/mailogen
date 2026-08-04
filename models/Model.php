<?php
/**
 * Tiny active-record-ish base. Concrete models set $table.
 * All queries use prepared statements (SQL-injection safe).
 */

declare(strict_types=1);

abstract class Model
{
    protected static string $table = '';

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM `' . static::$table . '` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Find a row scoped to a user (ownership check). */
    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM `' . static::$table . '` WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array> */
    public static function allForUser(int $userId, string $orderBy = 'id DESC'): array
    {
        $stmt = db()->prepare('SELECT * FROM `' . static::$table . '` WHERE user_id = ? ORDER BY ' . $orderBy);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function countForUser(int $userId, string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM `' . static::$table . '` WHERE user_id = ?';
        if ($where !== '') {
            $sql .= ' AND ' . $where;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute(array_merge([$userId], $params));
        return (int) $stmt->fetchColumn();
    }

    /** Insert and return the new id. */
    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $place = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO `' . static::$table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . $place . ')';
        $stmt = db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data, ?int $userId = null): void
    {
        if ($data === []) {
            return;
        }
        $set = implode(', ', array_map(static fn ($c) => "`$c` = ?", array_keys($data)));
        $sql = 'UPDATE `' . static::$table . '` SET ' . $set . ' WHERE id = ?';
        $params = [...array_values($data), $id];
        // Optional tenant scope — defence-in-depth against a future IDOR regression.
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        db()->prepare($sql)->execute($params);
    }

    public static function delete(int $id, ?int $userId = null): void
    {
        $sql = 'DELETE FROM `' . static::$table . '` WHERE id = ?';
        $params = [$id];
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        db()->prepare($sql)->execute($params);
    }
}
