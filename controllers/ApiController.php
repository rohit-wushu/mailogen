<?php

declare(strict_types=1);

/**
 * Base for the tenant-facing REST API — bearer-key auth instead of the
 * session/CSRF flow BaseController uses, but the SAME `uid()` scoping
 * convention, so every existing `Model::allForUser($this->uid())` call
 * works unchanged when reused from here.
 */
abstract class ApiController
{
    protected array $tenant;
    protected int $keyId;

    public function __construct()
    {
        $raw = self::extractKey();
        $record = $raw ? ApiKey::resolve($raw) : null;
        if (!$record) {
            self::fail(401, 'Invalid or missing API key. Pass it as "Authorization: Bearer <key>".');
        }
        $tenant = User::find((int) $record['user_id']);
        if (!$tenant || (int) $tenant['status'] !== 1) {
            self::fail(403, 'This account is disabled.');
        }
        $this->tenant = $tenant;
        $this->keyId  = (int) $record['id'];
    }

    protected function uid(): int
    {
        return (int) $this->tenant['id'];
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected static function ok(array $data = [], int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true] + $data);
        exit;
    }

    protected static function fail(int $code, string $error): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $error]);
        exit;
    }

    /** Accepts the key via Authorization: Bearer, X-Api-Key, or ?api_key= (fallback for hosts that strip auth headers). */
    private static function extractKey(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($auth === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                if (strcasecmp($k, 'Authorization') === 0) {
                    $auth = $v;
                    break;
                }
            }
        }
        if (preg_match('/^Bearer\s+(\S+)/i', $auth, $m)) {
            return $m[1];
        }
        $xApiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($xApiKey !== '') {
            return $xApiKey;
        }
        $qs = (string) ($_GET['api_key'] ?? '');
        return $qs !== '' ? $qs : null;
    }
}
