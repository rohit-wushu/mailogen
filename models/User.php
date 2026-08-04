<?php

declare(strict_types=1);

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function findByVerifyToken(string $token): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE verify_token = ? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function findByResetToken(string $token): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $password, ?string $company = null): int
    {
        $planId = db()->query("SELECT id FROM plans WHERE slug='starter' LIMIT 1")->fetchColumn();
        return self::insert([
            'name'         => $name,
            'email'        => strtolower(trim($email)),
            'password'     => password_hash($password, PASSWORD_DEFAULT),
            'company'      => $company,
            'role'         => 'user',
            'plan_id'      => $planId ?: null,
            'is_verified'  => 0,
            'verify_token' => bin2hex(random_bytes(20)),
        ]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        self::update($id, ['password' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    public static function all(string $orderBy = 'created_at DESC'): array
    {
        return db()->query('SELECT u.*, p.name AS plan_name FROM users u LEFT JOIN plans p ON p.id = u.plan_id ORDER BY ' . $orderBy)->fetchAll();
    }
}
