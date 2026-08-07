<?php

declare(strict_types=1);

final class SmtpController extends BaseController
{
    /** Known provider presets (host/port/encryption). */
    public const PRESETS = [
        'google_workspace' => ['host' => 'smtp.gmail.com',          'port' => 587, 'encryption' => 'tls'],
        'gmail'            => ['host' => 'smtp.gmail.com',          'port' => 587, 'encryption' => 'tls'],
        'brevo'            => ['host' => 'smtp-relay.brevo.com',    'port' => 587, 'encryption' => 'tls'],
        'ses'              => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
        'mailgun'          => ['host' => 'smtp.mailgun.org',        'port' => 587, 'encryption' => 'tls'],
        'sendgrid'         => ['host' => 'smtp.sendgrid.net',       'port' => 587, 'encryption' => 'tls'],
        'custom'           => ['host' => '',                        'port' => 587, 'encryption' => 'tls'],
    ];

    public function index(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        $accounts = SmtpAccount::allForUser($this->uid());
        $groups   = SmtpGroup::withCounts($this->uid());
        $domains  = Domain::allForUser($this->uid());
        $rates = [];
        foreach ($accounts as $a) {
            $rates[(int) $a['id']] = Reputation::rates((int) $a['id']);
        }
        $this->render('smtp/index', [
            'accounts' => $accounts,
            'groups'   => $groups,
            'domains'  => $domains,
            'rates'    => $rates,
            'presets'  => self::PRESETS,
        ], 'SMTP Accounts');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $provider = str_input('provider', 'custom');
        if (!isset(self::PRESETS[$provider])) {
            $provider = 'custom';
        }

        $domainId = int_input('domain_id') ?: null;
        if ($domainId !== null && !Domain::findForUser($domainId, $this->uid())) {
            $domainId = null;
        }

        $data = [
            'user_id'    => $this->uid(),
            'group_id'   => int_input('group_id') ?: null,
            'domain_id'  => $domainId,
            'label'      => str_input('label'),
            'provider'   => $provider,
            'host'       => str_input('host'),
            'port'       => int_input('port', 587),
            'encryption' => in_array(str_input('encryption'), ['tls', 'ssl', 'none'], true) ? str_input('encryption') : 'tls',
            'username'   => str_input('username'),
            'password'   => Crypto::encrypt((string) input('password', '')),
            'from_email' => str_input('from_email'),
            'from_name'  => str_input('from_name'),
            'priority'   => int_input('priority', 10),
            'is_enabled' => input('is_enabled') ? 1 : 1,
        ];

        if ($data['label'] === '' || $data['host'] === '' || $data['username'] === ''
            || !filter_var($data['from_email'], FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please fill in all required SMTP fields with a valid From email.');
            $this->back('smtp');
        }

        $id = int_input('id');
        $existing = $id ? SmtpAccount::findForUser($id, $this->uid()) : null;
        $limitInput = max(1, int_input('daily_limit', 300));
        if (input('warmup_enabled')) {
            $data['warmup_enabled']      = 1;
            $data['warmup_target_limit'] = $limitInput;
            if (!$existing || (int) $existing['warmup_enabled'] !== 1) {
                // Freshly turning warm-up on — (re)start the ramp from the low starting point.
                $data['daily_limit']      = WarmupEngine::START_LIMIT;
                $data['warmup_day']       = 0;
                $data['warmup_last_step'] = null;
            }
            // else: already ramping — leave daily_limit/day/last_step alone, only the target moved.
        } else {
            $data['warmup_enabled']      = 0;
            $data['warmup_target_limit'] = null;
            $data['daily_limit']         = $limitInput;
        }

        if ($existing) {
            // Editing: keep existing password if left blank.
            if ((string) input('password', '') === '') {
                unset($data['password']);
            }
            unset($data['user_id']);
            SmtpAccount::update($id, $data);
            flash('success', 'SMTP account updated.');
        } else {
            if (!Billing::canAddSmtp($this->user)) {
                $plan = Billing::effectivePlan($this->user);
                flash('error', "Your plan allows {$plan['max_smtp']} SMTP account(s). Upgrade to connect more.");
                redirect('billing');
            }
            $data['webhook_token'] = bin2hex(random_bytes(16));
            SmtpAccount::insert($data);
            flash('success', 'SMTP account added.');
        }
        redirect('smtp');
    }

    public function test(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        $account = SmtpAccount::findForUser($id, $this->uid());
        if (!$account) {
            json_response(['ok' => false, 'error' => 'Account not found.'], 404);
        }

        $to = str_input('to') ?: $this->user['email'];
        $result = Mailer::sendNow(
            $account,
            $to,
            'SMTP test - ' . APP_NAME,
            '<p>This is a test email confirming your SMTP account <strong>' . e($account['label'])
            . '</strong> works correctly.</p>'
        );

        SmtpAccount::setStatus($id, $result['ok'] ? 'verified' : 'failed');
        json_response($result);
    }

    /**
     * Test SMTP credentials entered in the form WITHOUT saving them.
     * Used by the "Test Connection" button in the add/edit modal.
     */
    public function testConnection(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $host = str_input('host');
        $port = int_input('port', 587);
        $enc  = in_array(str_input('encryption'), ['tls', 'ssl', 'none'], true) ? str_input('encryption') : 'tls';
        $user = str_input('username');
        $pass = (string) input('password', '');

        // Editing with a blank password -> reuse the stored one.
        $id = int_input('id');
        if ($pass === '' && $id) {
            $acc = SmtpAccount::findForUser($id, $this->uid());
            if ($acc) {
                $pass = SmtpAccount::plainPassword($acc);
            }
        }

        if ($host === '' || $user === '') {
            json_response(['ok' => false, 'error' => 'Host and username are required to test.']);
        }
        if ($pass === '') {
            json_response(['ok' => false, 'error' => 'Enter the password to test the connection.']);
        }

        $fromEmail = str_input('from_email') ?: $user;
        $fromName  = str_input('from_name') ?: 'SMTP Test';
        $to        = str_input('to') ?: $this->user['email'];

        $mailer = new SmtpMailer($host, $port, $enc, $user, $pass);
        $ok = $mailer->send(
            ['email' => $fromEmail, 'name' => $fromName],
            [$to],
            'SMTP connection test - ' . APP_NAME,
            '<p>✅ Your SMTP settings are working. This is a test message from ' . e(APP_NAME) . '.</p>'
        );

        json_response([
            'ok'    => $ok,
            'error' => $ok ? '' : $mailer->lastError(),
            'to'    => $to,
        ]);
    }

    public function toggle(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        $account = SmtpAccount::findForUser($id, $this->uid());
        if ($account) {
            $enabling = (int) $account['is_enabled'] !== 1;
            $update = ['is_enabled' => $enabling ? 1 : 0];
            if ($enabling) {
                // Manually re-enabling clears any reputation auto-pause note.
                $update['auto_paused_at'] = null;
                $update['pause_reason'] = null;
            }
            SmtpAccount::update($id, $update);
        }
        $this->back('smtp');
    }

    public function delete(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        SmtpAccount::delete(int_input('id'), $this->uid());
        flash('success', 'SMTP account deleted.');
        redirect('smtp');
    }

    /** Rotate the webhook secret (e.g. if it was accidentally exposed). */
    public function rotateWebhook(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        if (SmtpAccount::findForUser($id, $this->uid())) {
            SmtpAccount::update($id, ['webhook_token' => bin2hex(random_bytes(16))]);
            flash('success', 'Webhook URL rotated — update it in your ESP\'s webhook settings.');
        }
        $this->back('smtp');
    }

    // ---- groups (rotation pools) -----------------------------------

    public function storeGroup(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $mode = str_input('rotation_mode', 'round_robin');
        if (!in_array($mode, ['round_robin', 'random', 'priority', 'failover'], true)) {
            $mode = 'round_robin';
        }
        $name = str_input('name');
        if ($name === '') {
            flash('error', 'Group name is required.');
            $this->back('smtp');
        }
        $id = int_input('id');
        if ($id && SmtpGroup::findForUser($id, $this->uid())) {
            SmtpGroup::update($id, ['name' => $name, 'rotation_mode' => $mode]);
        } else {
            SmtpGroup::insert(['user_id' => $this->uid(), 'name' => $name, 'rotation_mode' => $mode]);
        }
        flash('success', 'Rotation group saved.');
        redirect('smtp');
    }

    public function deleteGroup(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        SmtpGroup::delete(int_input('id'), $this->uid());
        flash('success', 'Rotation group deleted.');
        redirect('smtp');
    }
}
