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

    public static function findByGoogleId(string $googleId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE google_id = ? LIMIT 1');
        $stmt->execute([$googleId]);
        return $stmt->fetch() ?: null;
    }

    /** Create a password-less account from a verified Google profile. */
    public static function createWithGoogle(string $name, string $email, string $googleId, ?string $company = null): int
    {
        $planId = db()->query("SELECT id FROM plans WHERE slug='starter' LIMIT 1")->fetchColumn();
        return self::insert([
            'name'        => $name !== '' ? $name : $email,
            'email'       => strtolower(trim($email)),
            'password'    => null,
            'google_id'   => $googleId,
            'company'     => $company,
            'role'        => 'user',
            'plan_id'     => $planId ?: null,
            'is_verified' => 1, // Google already verified this address.
        ]);
    }

    public static function create(string $name, string $email, string $password, ?string $company = null, ?string $phone = null, string $sendingMode = 'smtp'): int
    {
        $planId = db()->query("SELECT id FROM plans WHERE slug='starter' LIMIT 1")->fetchColumn();
        return self::insert([
            'name'         => $name,
            'email'        => strtolower(trim($email)),
            'password'     => password_hash($password, PASSWORD_DEFAULT),
            'company'      => $company,
            'phone'        => $phone,
            'role'         => 'user',
            'sending_mode' => in_array($sendingMode, ['smtp', 'domain'], true) ? $sendingMode : 'smtp',
            'plan_id'      => $planId ?: null,
            'is_verified'  => 0,
            'verify_token' => bin2hex(random_bytes(20)),
        ]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        self::update($id, ['password' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    /** Create a team-member login under an existing tenant's account — no plan/company of its own. */
    public static function createTeamMember(int $ownerId, string $name, string $email, string $password, string $teamRole): int
    {
        return self::insert([
            'owner_id'                => $ownerId,
            'name'                    => $name,
            'email'                   => strtolower(trim($email)),
            'password'                => password_hash($password, PASSWORD_DEFAULT),
            'role'                    => 'user',
            'team_role'               => in_array($teamRole, ['admin', 'member'], true) ? $teamRole : 'member',
            'is_verified'             => 1, // invite link already proved the email
            'onboarding_completed_at' => date('Y-m-d H:i:s'), // joins an already-onboarded tenant
        ]);
    }

    /** All team-member logins under a tenant (owner not included). */
    public static function teamMembers(int $ownerId): array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE owner_id = ? ORDER BY created_at ASC');
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    public static function all(string $orderBy = 'created_at DESC'): array
    {
        return db()->query('SELECT u.*, p.name AS plan_name FROM users u LEFT JOIN plans p ON p.id = u.plan_id ORDER BY ' . $orderBy)->fetchAll();
    }
}
