<?php
/**
 * Session-based authentication.
 */

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user === null || (int) $user['status'] !== 1 || $user['password'] === null) {
            return false; // null password = Google-only account, no password to check
        }
        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Re-hash if the algorithm/cost changed.
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    private static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (self::$cachedUser === null) {
            self::$cachedUser = User::find(self::id());
            if (self::$cachedUser === null) {
                self::logout();
            }
        }
        return self::$cachedUser;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    /** Require an authenticated user or redirect to login. */
    public static function require(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect('login');
        }
    }
}
