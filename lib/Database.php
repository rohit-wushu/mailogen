<?php
/**
 * PDO database singleton.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Sync MySQL's session clock with PHP's timezone so NOW() matches
            // the times the app writes (e.g. the send queue's send_after).
            // Without this, a UTC database never sees IST-stamped rows as "due".
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                throw $e;
            }
            // Don't leak credentials; callers / the global handler decide how
            // to present this (throwing, not exiting, so try/catch still works).
            throw new RuntimeException('Database connection failed.');
        }

        return self::$pdo;
    }
}

/**
 * Convenience accessor used throughout the app.
 */
function db(): PDO
{
    return Database::pdo();
}

/** Run a query and return the first column of the first row (scalar). */
function db_one(string $sql, array $params = []): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}
