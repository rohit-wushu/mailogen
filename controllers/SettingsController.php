<?php

declare(strict_types=1);

final class SettingsController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $free = Billing::freePlan();
        $this->render('settings/index', [
            'plans'              => Plan::all(),
            'unsubscribes'       => Unsubscribe::forUser($this->uid()),
            'modeSmtpFeatures'   => Plan::modeCompareFeatures('smtp'),
            'modeDomainFeatures' => Plan::modeCompareFeatures('domain'),
            'freePlanName'       => $free['name'] ?? 'Free',
            'freeFeatures'       => Plan::featureList($free),
            'teamMembers'        => User::teamMembers($this->uid()),
            'pendingInvites'     => TeamInvite::pendingForOwner($this->uid()),
            'apiKeys'            => ApiKey::allForUser($this->uid()),
            'newApiKey'          => $this->takeNewApiKey(),
        ], 'Settings');
    }

    public function profile(): void
    {
        $this->requireAuth();
        csrf_guard();
        $name = str_input('name');
        if ($name !== '') {
            // Name is personal — belongs to whoever is actually logged in, even
            // inside a team member's session. Everything else here (company,
            // address, timezone) is tenant-wide, so it saves to the account.
            User::update($this->actorId(), ['name' => $name]);
            if ($this->teamRole() !== 'member') {
                User::update($this->uid(), [
                    'company'     => str_input('company') ?: null,
                    'phone'       => str_input('phone') ?: null,
                    'org_address' => str_input('org_address') ?: null,
                    'org_website' => str_input('org_website') ?: null,
                    'timezone'    => str_input('timezone') ?: 'Asia/Kolkata',
                ]);
            }
            flash('success', 'Profile updated.');
        }
        $this->back('settings');
    }

    /** Switch between BYO-SMTP and domain (Amazon SES) sending — changes which price track applies. */
    public function sendingMode(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $mode = str_input('sending_mode') === 'domain' ? 'domain' : 'smtp';
        User::update($this->uid(), ['sending_mode' => $mode]);
        flash('success', $mode === 'domain'
            ? 'Switched to Amazon SES (domain-based) sending.'
            : 'Switched to bring-your-own-SMTP sending.');
        $this->back('settings');
    }

    public function password(): void
    {
        $this->requireAuth();
        csrf_guard();
        $current = (string) input('current_password', '');
        $new     = (string) input('new_password', '');
        $fresh   = User::find($this->actorId());

        if (!password_verify($current, $fresh['password'])) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            User::updatePassword($this->actorId(), $new);
            flash('success', 'Password changed.');
        }
        $this->back('settings');
    }

    public function imap(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
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
        User::update($this->actorId(), ['theme' => $theme]);
        if ($this->wantsJson()) {
            json_response(['ok' => true, 'theme' => $theme]);
        }
        $this->back('settings');
    }

    private function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /** One-time read of a freshly generated API key (see ApiKeyController::store()). */
    private function takeNewApiKey(): ?string
    {
        $key = $_SESSION['_new_api_key'] ?? null;
        unset($_SESSION['_new_api_key']);
        return is_string($key) ? $key : null;
    }
}
