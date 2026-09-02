<?php

declare(strict_types=1);

final class CampaignController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        // Not requireVerified() — this is the safe-harbor page that
        // requireVerified() itself redirects blocked actions back to, so
        // it must render rather than redirect. The list shows fine either
        // way; the "Verify your email" modal (auto-opened by the layout
        // when unverified) blocks interacting with it further.
        $this->render('campaigns/index', [
            'campaigns' => Campaign::withStats($this->uid()),
        ], 'Campaigns');
    }

    public function edit(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        $id = int_input('id');
        $campaign = $id ? Campaign::findForUser($id, $this->uid()) : null;

        $domains = array_values(array_filter(Domain::allForUser($this->uid()), static fn ($d) => (int) $d['is_verified'] === 1));

        $this->render('campaigns/edit', [
            'campaign'     => $campaign,
            'lists'        => ContactList::withCounts($this->uid()),
            'domains'      => $domains,
            'sesConnected' => (bool) SesConnection::platform(),
            'senders'      => Sender::withDomain($this->uid()),
            'replyIds'     => ReplyId::allForUser($this->uid(), 'is_default DESC, created_at DESC'),
            'groups'       => SmtpGroup::withCounts($this->uid()),
            'accounts'     => SmtpAccount::allForUser($this->uid()),
            'templates'    => Template::allForUser($this->uid()),
            'sectors'      => Contact::sectors($this->uid()),
            'locations'    => Contact::locations($this->uid()),
            'schedules'    => $campaign ? CampaignSchedule::forCampaign((int) $campaign['id']) : [],
        ], $campaign ? 'Edit Campaign' : 'New Campaign');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();

        $name = str_input('name');
        if ($name === '') {
            flash('error', 'Campaign name is required.');
            $this->back('campaigns/edit');
        }

        $sourceType = str_input('source_type') === 'sheet' ? 'sheet' : 'list';
        $sheetUrl   = trim((string) input('sheet_url', ''));
        if ($sourceType === 'sheet' && $sheetUrl !== '' && !GoogleSheet::isSheetUrl($sheetUrl)) {
            flash('error', 'That does not look like a Google Sheets link. Use the full https://docs.google.com/spreadsheets/… URL.');
            $this->back('campaigns/edit');
        }

        // Sending method: a verified Sending Domain (via the account's Amazon
        // SES connection) or a classic SMTP account/group — pick one per
        // campaign. Selecting one clears the other so they never both apply.
        $sendVia = str_input('send_via') === 'smtp' ? 'smtp' : 'domain';

        // Domain (Amazon SES) sending is a paid-plan-dependent choice made in
        // Settings — SMTP-mode accounts can't send this way even if they pick
        // it in the form (defense in depth against a tampered request).
        if ($sendVia === 'domain' && ($this->user['sending_mode'] ?? 'smtp') !== 'domain') {
            flash('error', 'Domain-based (Amazon SES) sending isn\'t enabled for your account. Switch to it from Settings first.');
            $this->back('campaigns/edit');
        }

        $domainId = $fromEmail = $fromName = null;
        $smtpGroupId = $smtpId = null;

        if ($sendVia === 'domain') {
            $domainId  = int_input('domain_id') ?: null;
            $fromEmail = strtolower(trim((string) input('from_email', ''))) ?: null;
            $fromName  = str_input('from_name') ?: null;
            if ($domainId) {
                $domain = Domain::findForUser($domainId, $this->uid());
                if (!$domain || (int) $domain['is_verified'] !== 1) {
                    flash('error', 'Select a verified Sending Domain.');
                    $this->back('campaigns/edit');
                }
                if ($fromEmail && !str_ends_with($fromEmail, '@' . $domain['domain'])) {
                    flash('error', 'From email must be on the selected domain (@' . $domain['domain'] . ').');
                    $this->back('campaigns/edit');
                }
            }
        } else {
            $smtpGroupId = int_input('smtp_group_id') ?: null;
            $smtpId      = int_input('smtp_id') ?: null;
        }

        $replyTo = strtolower(trim((string) input('reply_to', ''))) ?: null;
        if ($replyTo && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Reply-To must be a valid email address.');
            $this->back('campaigns/edit');
        }

        $data = [
            'name'             => $name,
            'source_type'      => $sourceType,
            'sheet_url'        => $sourceType === 'sheet' ? ($sheetUrl ?: null) : null,
            'list_id'          => $sourceType === 'sheet' ? null : (int_input('list_id') ?: null),
            'sector'           => str_input('sector') ?: null,
            'location'         => str_input('location') ?: null,
            'domain_id'        => $domainId,
            'from_name'        => $fromName,
            'from_email'       => $fromEmail,
            'reply_to'         => $replyTo,
            'smtp_group_id'    => $smtpGroupId,
            'smtp_id'          => $smtpId,
            'template_id'      => int_input('template_id') ?: null,
            'subject'          => str_input('subject'),
            'body_html'        => (string) input('body_html', ''),
            'throttle_per_hour'=> int_input('throttle_per_hour', 0),
            'track_opens'      => input('track_opens') ? 1 : 0,
            'track_clicks'     => input('track_clicks') ? 1 : 0,
            'enable_followup'  => input('enable_followup') ? 1 : 0,
        ];

        $id = int_input('id');
        $existing = $id ? Campaign::findForUser($id, $this->uid()) : null;

        // A/B subject test — once a winner is decided the test is historical
        // record, not editable (holdout has already gone out with the winner).
        if (!$existing || empty($existing['ab_winner'])) {
            if (input('ab_enabled') && str_input('ab_subject_b') !== '') {
                $data['ab_subject_b']  = str_input('ab_subject_b');
                $data['ab_test_pct']   = max(2, min(90, int_input('ab_test_pct', 20)));
                $data['ab_test_hours'] = max(1, min(72, int_input('ab_test_hours', 4)));
            } else {
                $data['ab_subject_b']  = null;
                $data['ab_test_pct']   = null;
                $data['ab_test_hours'] = null;
            }
        }

        if ($existing) {
            Campaign::update($id, $data);
        } else {
            if (!Billing::canCreateCampaign($this->user)) {
                $plan = Billing::effectivePlan($this->user);
                flash('error', "Your plan allows {$plan['max_campaigns']} campaign(s). Upgrade for more.");
                redirect('billing');
            }
            $data['user_id'] = $this->uid();
            $data['status']  = 'draft';
            $id = Campaign::insert($data);
        }

        $this->saveFollowups($id);

        flash('success', 'Campaign saved.');
        redirect('campaigns/show?id=' . $id);
    }

    private function saveFollowups(int $campaignId): void
    {
        $subjects = $_POST['fu_subject'] ?? [];
        $bodies   = $_POST['fu_body'] ?? [];
        $delays   = $_POST['fu_delay'] ?? [];
        if (!is_array($subjects)) {
            return;
        }
        CampaignSchedule::deleteForCampaign($campaignId);
        $step = 1;
        foreach ($subjects as $i => $subject) {
            $subject = trim((string) $subject);
            $body    = trim((string) ($bodies[$i] ?? ''));
            if ($subject === '' || $body === '') {
                continue;
            }
            CampaignSchedule::insert([
                'campaign_id'     => $campaignId,
                'step'            => $step++,
                'delay_days'      => max(1, (int) ($delays[$i] ?? 3)),
                'subject'         => $subject,
                'body_html'       => $body,
                'stop_if_opened'  => isset($_POST['fu_stop_opened'][$i]) ? 1 : 0,
                'stop_if_clicked' => isset($_POST['fu_stop_clicked'][$i]) ? 1 : 0,
                'stop_if_replied' => isset($_POST['fu_stop_replied'][$i]) ? 1 : 0,
            ]);
        }
    }

    /**
     * AJAX: validate a Google Sheet link and return a recipient preview
     * (count + detected columns + first row) for the campaign editor.
     */
    public function sheetPreview(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        $url = trim((string) input('sheet_url', ''));
        if ($url === '') {
            json_response(['ok' => false, 'error' => 'Paste a Google Sheet link first.']);
        }
        $res = GoogleSheet::fetch($url);
        if (!$res['ok']) {
            json_response(['ok' => false, 'error' => $res['error']]);
        }
        $rows       = $res['rows'];
        $header     = $rows[0] ?? [];
        $recipients = GoogleSheet::recipients($rows);
        $columns    = array_values(array_filter(array_map(
            static fn ($c) => trim((string) $c),
            $header
        ), static fn ($c) => $c !== ''));

        json_response([
            'ok'      => true,
            'count'   => count($recipients),
            'columns' => $columns,
            'hasEmail'=> isset(GoogleSheet::mapHeaders($header)['email']),
            'sample'  => $recipients[0]['email'] ?? '',
        ]);
    }

    public function show(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if (!$campaign) {
            redirect('campaigns');
        }
        $report = array_values(array_filter(
            Stats::campaignReport($this->uid()),
            static fn ($r) => (int) $r['id'] === (int) $campaign['id']
        ));

        // Real send progress from the queue itself — not total_recipients (which
        // only reflects the latest enqueue round) or the cumulative email_logs
        // count (which can exceed it after a resend). "done" = terminal rows
        // (sent/failed/skipped); "total" = every row this campaign has queued.
        $queueProgress = null;
        if ($campaign['status'] === 'running') {
            $stmt = db()->prepare(
                "SELECT COUNT(*) AS total, SUM(status IN ('sent','failed','skipped')) AS done
                 FROM email_queue WHERE campaign_id = ?"
            );
            $stmt->execute([(int) $campaign['id']]);
            $row = $stmt->fetch();
            $queueProgress = ['done' => (int) ($row['done'] ?? 0), 'total' => (int) ($row['total'] ?? 0)];
        }

        $org = ['address' => $this->user['org_address'] ?? '', 'company' => $this->user['company'] ?? ''];

        // Resolve a recipient *preview* without side effects. For a sheet
        // campaign this reads the live sheet (no contacts are imported until an
        // actual send); failures surface as a non-blocking warning.
        $sheetError = '';
        if (($campaign['source_type'] ?? 'list') === 'sheet') {
            $res = GoogleSheet::fetch((string) ($campaign['sheet_url'] ?? ''));
            $audience   = $res['ok'] ? GoogleSheet::recipients($res['rows']) : [];
            $sheetError = $res['ok'] ? '' : $res['error'];
        } else {
            $audience = Contact::activeInList($this->uid(), $campaign['list_id'] ? (int) $campaign['list_id'] : null, $campaign['sector'] ?? null, $campaign['location'] ?? null);
        }

        // Preview exactly what recipients get: the body with the auto-appended
        // compliance + free-plan branding footer (no tracking pixel/link rewrite).
        $previewHtml = Mailer::injectTracking(
            (string) ($campaign['body_html'] ?: '<p style="font-family:sans-serif;color:#999;padding:20px">No content yet.</p>'),
            'preview',
            false,
            false,
            Mailer::orgFooterMeta($this->uid())
        );

        $this->render('campaigns/show', [
            'campaign'    => $campaign,
            'report'      => $report[0] ?? null,
            'queueProgress' => $queueProgress,
            'schedules'   => CampaignSchedule::forCampaign((int) $campaign['id']),
            'audience'    => $audience,
            'sheetError'  => $sheetError,
            'spamCheck'   => SpamCheck::analyze($campaign, $org),
            'previewHtml' => $previewHtml,
            'isFreePlan'  => Branding::isFreeUser($this->user),
        ], $campaign['name']);
    }

    public function sendTest(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if (!$campaign) {
            json_response(['ok' => false, 'error' => 'Campaign not found.'], 404);
        }
        // Accept several addresses separated by comma / space / semicolon / newline.
        $maxRecipients = 5;
        $raw = str_input('to') ?: (string) $this->user['email'];
        $list = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $list = array_values(array_unique(array_map(static fn ($e) => strtolower(trim($e)), $list)));

        $valid = $invalid = [];
        foreach ($list as $e) {
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $e;
            } else {
                $invalid[] = $e;
            }
        }
        if ($valid === []) {
            json_response(['ok' => false, 'error' => 'Please enter at least one valid email address.']);
        }
        $capped = count($valid) > $maxRecipients;
        $valid  = array_slice($valid, 0, $maxRecipients);

        // Domain-based campaigns send via the account's Amazon SES connection;
        // legacy campaigns still resolve an SMTP account/group.
        $domainId = (int) ($campaign['domain_id'] ?? 0);
        $candidates = $sesDomain = $sesConn = null;
        if ($domainId) {
            $sesDomain = Domain::findForUser($domainId, $this->uid());
            if (!$sesDomain || (int) $sesDomain['is_verified'] !== 1) {
                json_response(['ok' => false, 'error' => "This campaign's Sending Domain is not verified."]);
            }
            $sesConnRow = SesConnection::platform();
            if (!$sesConnRow) {
                json_response(['ok' => false, 'error' => 'Amazon SES is not connected on this platform yet. Contact your administrator.']);
            }
            $sesConn = SesConnection::credentials($sesConnRow);
        } else {
            $candidates = SmtpRotator::candidates($campaign, $this->uid(), 0);
            if ($candidates === []) {
                json_response(['ok' => false, 'error' => 'No SMTP account available. Add and enable one first.']);
            }
        }

        // Base merge sample. For a Google Sheet campaign, use the first real row
        // so sheet-column merge tags resolve in the test email.
        $baseSample = ['first_name' => 'There', 'company' => 'Acme'];
        if (($campaign['source_type'] ?? 'list') === 'sheet') {
            $res = GoogleSheet::fetch((string) ($campaign['sheet_url'] ?? ''));
            if ($res['ok']) {
                $recipients = GoogleSheet::recipients($res['rows']);
                if ($recipients !== []) {
                    $baseSample = $recipients[0];
                }
            }
        }

        // Compliance footer + free-plan branding (logo embedded via CID).
        $brand = Branding::forEmail($this->uid());
        $org   = Mailer::orgFooterMeta($this->uid());
        $org['branding'] = $brand['html'];
        $inline  = $brand['image'] ? [$brand['image']] : [];
        $subject = '[TEST] ' . (string) $campaign['subject'];

        $sent = $failed = [];
        foreach ($valid as $to) {
            $sample = $baseSample;
            $sample['email'] = $to;                 // so {{email}} resolves per recipient
            $html = Mailer::injectTracking(
                render_placeholders((string) $campaign['body_html'], $sample),
                tracking_id(),
                false,
                false,
                $org
            );
            if ($domainId) {
                $fromEmail = (string) ($campaign['from_email'] ?: ('no-reply@' . $sesDomain['domain']));
                $fromName  = (string) ($campaign['from_name'] ?: APP_NAME);
                $res = Mailer::sendNowSes($sesConn, $sesDomain, $fromEmail, $fromName, $to, render_placeholders($subject, $sample), $html, $inline);
            } else {
                $res = Mailer::sendNow($candidates[0], $to, render_placeholders($subject, $sample), $html, $inline);
            }
            if ($res['ok']) {
                $sent[] = $to;
            } else {
                $failed[] = ['email' => $to, 'error' => $res['error']];
            }
        }

        json_response([
            'ok'      => $sent !== [],
            'sent'    => $sent,
            'failed'  => $failed,
            'invalid' => $invalid,
            'capped'  => $capped,
            'max'     => $maxRecipients,
            'error'   => $sent !== [] ? '' : ($failed[0]['error'] ?? 'Could not send the test email.'),
        ]);
    }

    /** Queue all recipients for immediate sending. */
    public function sendNow(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if (!$campaign) {
            redirect('campaigns');
        }
        if (!$this->validateSendable($campaign)) {
            $this->back('campaigns/show?id=' . $campaign['id']);
        }
        Campaign::update((int) $campaign['id'], ['scheduled_at' => null]);
        $campaign['scheduled_at'] = null;
        $hadDomain = !empty($campaign['domain_id']);
        $count = CampaignBuilder::enqueue($campaign);
        $upgraded = !$hadDomain && !empty(Campaign::find((int) $campaign['id'])['domain_id']);
        $msg = "Campaign launched - {$count} emails queued. The cron worker will start sending shortly.";
        if ($upgraded) {
            $msg .= " This campaign's recipient count is over your account's SMTP comfort zone, so we've automatically switched you to our managed sending infrastructure for better deliverability — you can switch back anytime in Settings.";
        }
        flash('success', $msg);
        redirect('campaigns/show?id=' . $campaign['id']);
    }

    public function schedule(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if (!$campaign) {
            redirect('campaigns');
        }
        if (!$this->validateSendable($campaign)) {
            $this->back('campaigns/show?id=' . $campaign['id']);
        }
        $when = str_input('scheduled_at');
        $ts = strtotime($when);
        if (!$ts || $ts < time() - 60) {
            flash('error', 'Please choose a valid future date and time.');
            $this->back('campaigns/show?id=' . $campaign['id']);
        }
        Campaign::update((int) $campaign['id'], [
            'scheduled_at' => date('Y-m-d H:i:s', $ts),
            'status'       => 'scheduled',
        ]);
        flash('success', 'Campaign scheduled for ' . fmt_dt(date('Y-m-d H:i:s', $ts)) . '.');
        redirect('campaigns/show?id=' . $campaign['id']);
    }

    /**
     * Reopen a finished/cancelled campaign as a draft so it can be scheduled or
     * sent again. The previous run's queue rows and logs are KEPT as history —
     * the next send simply adds a fresh batch (a new run) on top. Old rows are
     * already 'sent', and the worker only picks up newly 'queued' rows, so the
     * runs never collide.
     */
    public function reopen(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if (!$campaign) {
            redirect('campaigns');
        }
        if (in_array($campaign['status'], ['completed', 'cancelled'], true)) {
            Campaign::update((int) $campaign['id'], [
                'status'       => 'draft',
                'scheduled_at' => null,
            ]);
            flash('success', 'Campaign reopened as draft — schedule or send it again. Previous send history is kept.');
        }
        redirect('campaigns/show?id=' . $campaign['id']);
    }

    /** Cancel a pending schedule and return the campaign to draft. */
    public function unschedule(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if (!$campaign) {
            redirect('campaigns');
        }
        if ($campaign['status'] === 'scheduled') {
            Campaign::update((int) $campaign['id'], ['scheduled_at' => null, 'status' => 'draft']);
            flash('success', 'Schedule cancelled — campaign is back to draft.');
        }
        redirect('campaigns/show?id=' . $campaign['id']);
    }

    public function pause(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if ($campaign) {
            Campaign::setStatus((int) $campaign['id'], 'paused');
            // Hold queued emails.
            db()->prepare("UPDATE email_queue SET send_after = DATE_ADD(NOW(), INTERVAL 100 YEAR) WHERE campaign_id = ? AND status='queued'")
                ->execute([(int) $campaign['id']]);
            flash('success', 'Campaign paused.');
        }
        $this->back('campaigns/show?id=' . ($campaign['id'] ?? ''));
    }

    public function resume(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        $campaign = Campaign::findForUser(int_input('id'), $this->uid());
        if ($campaign) {
            Campaign::setStatus((int) $campaign['id'], 'running');
            db()->prepare("UPDATE email_queue SET send_after = NOW() WHERE campaign_id = ? AND status='queued'")
                ->execute([(int) $campaign['id']]);
            flash('success', 'Campaign resumed.');
        }
        $this->back('campaigns/show?id=' . ($campaign['id'] ?? ''));
    }

    public function delete(): void
    {
        $this->requireAuth();
        $this->requireVerified();
        csrf_guard();
        Campaign::delete(int_input('id'), $this->uid());
        flash('success', 'Campaign deleted.');
        redirect('campaigns');
    }

    private function validateSendable(array $campaign): bool
    {
        if (trim((string) $campaign['subject']) === '' || trim((string) $campaign['body_html']) === '') {
            flash('error', 'Add a subject and email body before sending.');
            return false;
        }

        if (($campaign['source_type'] ?? 'list') === 'sheet') {
            $result = GoogleSheet::fetch((string) ($campaign['sheet_url'] ?? ''));
            if (!$result['ok']) {
                flash('error', 'Google Sheet problem: ' . $result['error']);
                return false;
            }
            $need = count(GoogleSheet::recipients($result['rows']));
            if ($need === 0) {
                flash('error', 'No valid email addresses found in the Google Sheet. Make sure it has an "Email" column.');
                return false;
            }
        } else {
            $audience = Contact::activeInList($this->uid(), $campaign['list_id'] ? (int) $campaign['list_id'] : null, $campaign['sector'] ?? null, $campaign['location'] ?? null);
            if ($audience === []) {
                flash('error', 'No active contacts in the selected list / sector / location.');
                return false;
            }
            $need = count($audience);
        }
        if (!empty($campaign['domain_id'])) {
            $domain = Domain::findForUser((int) $campaign['domain_id'], $this->uid());
            if (!$domain || (int) $domain['is_verified'] !== 1) {
                flash('error', 'This campaign\'s Sending Domain is not verified.');
                return false;
            }
            if (!SesConnection::platform()) {
                flash('error', 'Amazon SES is not connected on this platform yet. Contact your administrator.');
                return false;
            }
        } elseif (SmtpRotator::candidates($campaign, $this->uid(), 0) === []) {
            flash('error', 'No enabled SMTP account available for sending.');
            return false;
        }
        // Monthly send quota for the user's plan.
        if (!Billing::canSendEmails($this->user, $need)) {
            $remaining = Billing::remainingEmails($this->user);
            flash('error', "This send ({$need}) exceeds your remaining monthly email allowance ({$remaining}). Upgrade your plan to send more.");
            return false;
        }
        return true;
    }
}
