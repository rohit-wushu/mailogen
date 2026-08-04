<?php

declare(strict_types=1);

final class SettingsController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->render('settings/index', [
            'plans'        => Plan::all(),
            'unsubscribes' => Unsubscribe::forUser($this->uid()),
        ], 'Settings');
    }

    public function profile(): void
    {
        $this->requireAuth();
        csrf_guard();
        $name = str_input('name');
        if ($name !== '') {
            User::update($this->uid(), [
                'name'        => $name,
                'company'     => str_input('company') ?: null,
                'phone'       => str_input('phone') ?: null,
                'org_address' => str_input('org_address') ?: null,
                'org_website' => str_input('org_website') ?: null,
                'timezone'    => str_input('timezone') ?: 'Asia/Kolkata',
            ]);
            flash('success', 'Profile updated.');
        }
        $this->back('settings');
    }

    public function password(): void
    {
        $this->requireAuth();
        csrf_guard();
        $current = (string) input('current_password', '');
        $new     = (string) input('new_password', '');
        $fresh   = User::find($this->uid());

        if (!password_verify($current, $fresh['password'])) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            User::updatePassword($this->uid(), $new);
            flash('success', 'Password changed.');
        }
        $this->back('settings');
    }

    public function imap(): void
    {
        $this->requireAuth();
        csrf_guard();
        $data = [
            'imap_host'    => str_input('imap_host') ?: null,
            'imap_port'    => int_input('imap_port', 993),
            'imap_user'    => str_input('imap_user') ?: null,
            'imap_enabled' => input('imap_enabled') ? 1 : 0,
        ];
        // Only overwrite the password when a new one is supplied.
        $pass = (string) input('imap_pass', '');
        if ($pass !== '') {
            $data['imap_pass'] = Crypto::encrypt($pass);
        }
        User::update($this->uid(), $data);
        flash('success', 'Inbox (IMAP) settings saved. Bounces and replies will be processed by the cron.');
        $this->back('settings');
    }

    public function theme(): void
    {
        $this->requireAuth();
        csrf_guard();
        $theme = str_input('theme') === 'dark' ? 'dark' : 'light';
        User::update($this->uid(), ['theme' => $theme]);
        if ($this->wantsJson()) {
            json_response(['ok' => true, 'theme' => $theme]);
        }
        $this->back('settings');
    }

    private function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
