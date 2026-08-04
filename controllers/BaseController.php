<?php

declare(strict_types=1);

abstract class BaseController
{
    protected ?array $user = null;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    protected function uid(): int
    {
        return (int) ($this->user['id'] ?? 0);
    }

    protected function requireAuth(): void
    {
        Auth::require();
        // Force email verification for non-admins (configurable).
        if ($this->user && (int) $this->user['is_verified'] === 0 && $this->user['role'] !== 'admin') {
            // Allow access but the UI nudges them; uncomment to hard-gate:
            // redirect('verify-notice');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Forbidden - admins only.');
        }
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /** Render a dashboard view with the shared layout. */
    protected function render(string $template, array $data = [], string $title = ''): void
    {
        $data['user']      = $this->user;
        $data['pageTitle'] = $title;
        view($template, $data, $title);
    }

    protected function back(string $fallback = 'dashboard'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '' && str_contains($ref, $_SERVER['HTTP_HOST'] ?? '')) {
            redirect($ref);
        }
        redirect($fallback);
    }
}
