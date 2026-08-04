<?php
/**
 * Eventogen Mailer - Application bootstrap
 *
 * Loaded by every entry point (public/index.php, cron scripts, tracking
 * endpoints). Wires up configuration, the class autoloader, the session
 * and a couple of global helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// -------------------------------------------------------------------
//  Autoloader for lib/, models/, controllers/
// -------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $dirs = [
        BASE_PATH . '/lib/',
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// Files that define global functions must be loaded eagerly (the autoloader
// only triggers on class references, not function calls).
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/Database.php';

// -------------------------------------------------------------------
//  Global error handling (web only) — keep a clean 500 page in prod.
// -------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    set_exception_handler(static function (\Throwable $e): void {
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (APP_ENV === 'development') {
            echo '<pre style="padding:20px;font:14px monospace">'
               . htmlspecialchars((string) $e) . '</pre>';
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>Server error</title>'
               . '<div style="font-family:sans-serif;text-align:center;padding:60px">'
               . '<h1>Something went wrong</h1><p>Please try again in a moment.</p></div>';
        }
    });
}

// -------------------------------------------------------------------
//  Session
// -------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    // Keep sessions (and their CSRF tokens) alive across browser restarts and
    // idle tabs so users don't hit spurious "invalid CSRF token" errors when a
    // form is submitted a while after it loaded.
    $sessionTtl = 60 * 60 * 24 * 14;            // 14 days
    @ini_set('session.gc_maxlifetime', (string) $sessionTtl);
    // Detect HTTPS even behind a TLS-terminating proxy / load balancer
    // (common on Hostinger, Cloudflare, etc.) so the Secure flag is correct.
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    session_set_cookie_params([
        'lifetime' => $sessionTtl,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_name('EVENTOGEN_SESS');
    session_start();
}
