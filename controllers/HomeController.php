<?php

declare(strict_types=1);

/**
 * Public marketing landing page (no auth). Logged-in visitors are sent
 * straight to their dashboard; everyone else sees the marketing site.
 */
final class HomeController extends BaseController
{
    public function index(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }

        view_bare('home/landing', [
            'plans' => Plan::active(),
        ], 'Bulk email marketing on your own SMTP');
    }
}
