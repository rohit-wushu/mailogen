<?php

declare(strict_types=1);

abstract class BaseController
{
    protected ?array $user = null;

    /**
     * Personal fields that stay with the actual logged-in person even when
     * they're a team member acting inside someone else's tenant — everything
     * else on $this->user is the tenant OWNER's data (company, plan, SMTP
     * setup, etc.), so every existing `$this->uid()` / `allForUser($uid)`
     * call across the app keeps scoping correctly with no further changes.
     */
    private const ACTOR_FIELDS = [
        'name', 'email', 'password', 'google_id', 'role', 'team_role',
        'theme', 'is_verified', 'verify_token', 'reset_token', 'reset_expires',
        'last_login_at',
    ];

    public function __construct()
    {
        $raw = Auth::user();
        if ($raw && !empty($raw['owner_id'])) {
            $owner = User::find((int) $raw['owner_id']);
            $this->user = $owner ? self::mergeTeamIdentity($owner, $raw) : $raw;
        } else {
            $this->user = $raw;
        }
    }

    /** Merge a team member's personal identity onto the tenant owner's account data. */
    private static function mergeTeamIdentity(array $owner, array $actor): array
    {
        $merged = $owner;
        foreach (self::ACTOR_FIELDS as $field) {
            $merged[$field] = $actor[$field] ?? null;
        }
        $merged['actor_id'] = (int) $actor['id'];
        return $merged;
    }

    /** The tenant/account id — every resource (campaigns, contacts, SMTP...) is scoped to this. */
    protected function uid(): int
    {
        return (int) ($this->user['id'] ?? 0);
    }

    /** The real logged-in person's own id — for audit trails and the team page itself, never for data scoping. */
    protected function actorId(): int
    {
        return (int) ($this->user['actor_id'] ?? $this->user['id'] ?? 0);
    }

    /** This login's permission level within the tenant: owner, admin or member. Solo (non-team) accounts are always 'owner'. */
    protected function teamRole(): string
    {
        return (string) ($this->user['team_role'] ?? 'owner');
    }

    /** Owner/admin team-roles get full day-to-day access; only 'member' is restricted. */
    protected function requireTeamAdmin(): void
    {
        if ($this->teamRole() === 'member') {
            http_response_code(403);
            exit('Forbidden — ask an account owner or admin.');
        }
    }

    protected function requireAuth(): void
    {
        Auth::require();
        // Force email verification for non-admins (configurable).
        if ($this->user && (int) $this->user['is_verified'] === 0 && $this->user['role'] !== 'admin') {
            // Allow access but the UI nudges them; uncomment to hard-gate:
            // redirect('verify-notice');
        }
        // New accounts must finish the onboarding wizard (company/address —
        // feeds the CAN-SPAM footer requirement) before reaching anything else.
        if ($this->user && $this->user['role'] !== 'admin' && empty($this->user['onboarding_completed_at'])) {
            $route = (string) ($_GET['r'] ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/'));
            if (!str_starts_with($route, 'onboarding')) {
                redirect('onboarding/step1');
            }
        }
        // The super admin runs the platform, not a tenant workspace — keep them
        // out of tenant-only areas (campaigns, contacts, etc.) entirely. Only
        // the admin panel and their own account settings (profile/password/
        // theme) stay reachable.
        if ($this->user && $this->user['role'] === 'admin') {
            $route = (string) ($_GET['r'] ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/'));
            $allowed = ['admin', 'settings', 'logout'];
            $isAllowed = false;
            foreach ($allowed as $prefix) {
                if ($route === $prefix || str_starts_with($route, $prefix . '/')) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                redirect('admin');
            }
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
