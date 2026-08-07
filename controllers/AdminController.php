<?php

declare(strict_types=1);

final class AdminController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/index', [
            'stats'  => Stats::admin(),
            'users'  => array_slice(User::all(), 0, 10),
        ], 'Admin Dashboard');
    }

    public function users(): void
    {
        $this->requireAdmin();
        $this->render('admin/users', [
            'users' => User::all(),
            'plans' => Plan::all(),
        ], 'Manage Users');
    }

    /** Full drill-down on one tenant: profile, sending setup, activity, deliverability. */
    public function viewUser(): void
    {
        $this->requireAdmin();
        $id = int_input('id');
        $target = User::find($id);
        if (!$target) {
            http_response_code(404);
            exit('User not found.');
        }

        $sentCount = (int) db_one(
            "SELECT COUNT(*) FROM email_queue WHERE user_id = ? AND status = 'sent'", [$id]
        );

        $this->render('admin/user_view', [
            'target'          => $target,
            'plan'            => !empty($target['plan_id']) ? Plan::find((int) $target['plan_id']) : null,
            'domains'         => Domain::allForUser($id),
            'campaignCount'   => Campaign::countForUser($id),
            'recentCampaigns' => array_slice(Campaign::withStats($id), 0, 5),
            'contactCount'    => Contact::countForUser($id),
            'smtpCount'       => SmtpAccount::countForUser($id),
            'sentCount'       => $sentCount,
            'rates'           => Reputation::ratesByUser($id),
        ], 'Tenant: ' . $target['name']);
    }

    /** Read-only view of one tenant's contacts — for support/debugging, not editable from here. */
    public function userContacts(): void
    {
        $this->requireAdmin();
        $id = int_input('id');
        $target = User::find($id);
        if (!$target) {
            http_response_code(404);
            exit('User not found.');
        }

        $q = str_input('q');
        $page = max(1, int_input('page', 1));
        $perPage = 50;
        $opts = ['q' => $q, 'limit' => $perPage, 'offset' => ($page - 1) * $perPage];

        $total = Contact::countSearch($id, $opts);
        $this->render('admin/user_contacts', [
            'target' => $target,
            'rows'   => Contact::search($id, $opts),
            'total'  => $total,
            'page'   => $page,
            'pages'  => (int) ceil(max(1, $total) / $perPage),
            'q'      => $q,
        ], 'Contacts: ' . $target['name']);
    }

    public function toggleUser(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $id = int_input('id');
        $u = User::find($id);
        if ($u && $u['role'] !== 'admin') {
            User::update($id, ['status' => (int) $u['status'] === 1 ? 0 : 1]);
        }
        $this->back('admin/users');
    }

    /** Log in as a tenant to see exactly what they see, e.g. for support/debugging. Audited + reversible. */
    public function impersonate(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $target = User::find(int_input('id'));
        if (!$target || $target['role'] === 'admin') {
            flash('error', 'That account cannot be impersonated.');
            $this->back('admin/users');
        }

        $adminId = $this->uid();
        $_SESSION['impersonator_id'] = $adminId;
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $target['id'];

        SystemLog::write('warning', 'admin.impersonate', "Admin #{$adminId} started impersonating user #{$target['id']} ({$target['email']})", $adminId);
        redirect('dashboard');
    }

    /** Return from an impersonation session back to the admin's own account. */
    public function stopImpersonate(): void
    {
        $impersonatorId = (int) ($_SESSION['impersonator_id'] ?? 0);
        if ($impersonatorId <= 0) {
            redirect('login');
        }
        $tenantId = (int) ($_SESSION['user_id'] ?? 0);
        unset($_SESSION['impersonator_id']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $impersonatorId;

        SystemLog::write('info', 'admin.impersonate', "Admin #{$impersonatorId} stopped impersonating user #{$tenantId}", $impersonatorId);
        redirect('admin/users');
    }

    public function setPlan(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $id = int_input('id');
        $planId = int_input('plan_id') ?: null;
        if (User::find($id)) {
            User::update($id, ['plan_id' => $planId]);
            flash('success', 'Plan updated.');
        }
        $this->back('admin/users');
    }

    public function plans(): void
    {
        $this->requireAdmin();
        $this->render('admin/plans', [
            'plans'             => Plan::all(),
            'modeCompareSmtp'   => Plan::modeCompareRaw('smtp'),
            'modeCompareDomain' => Plan::modeCompareRaw('domain'),
        ], 'Subscription Plans');
    }

    /** Save the SMTP-vs-domain comparison shown in the info modal on register/settings. */
    public function storeModeCompare(): void
    {
        $this->requireAdmin();
        csrf_guard();
        Setting::set('mode_compare_smtp', trim((string) input('mode_compare_smtp', '')) ?: null);
        Setting::set('mode_compare_domain', trim((string) input('mode_compare_domain', '')) ?: null);
        flash('success', 'Sending-mode comparison saved.');
        $this->back('admin/plans');
    }

    /** Create or update a pricing plan from the admin editor. */
    public function storePlan(): void
    {
        $this->requireAdmin();
        csrf_guard();

        $name = str_input('name');
        if ($name === '') {
            flash('error', 'Plan name is required.');
            $this->back('admin/plans');
        }

        // -1 means unlimited; keep the sentinel, otherwise clamp to >= 0.
        $limit = static fn (string $k): int => (int) input($k) < 0 ? -1 : max(0, (int) input($k));

        $priceSmtp = max(0, (float) input('price_smtp', 0));
        $priceDom  = max(0, (float) input('price_domain', 0));
        $data = [
            'name'           => $name,
            'tagline'        => str_input('tagline') ?: null,
            'price_smtp'     => $priceSmtp,
            'price_domain'   => $priceDom,
            'price_monthly'  => $priceDom, // legacy column, kept in sync for any code that still reads it
            'price_period'   => in_array(str_input('price_period'), ['month', 'year'], true) ? str_input('price_period') : 'month',
            'billed_note'    => str_input('billed_note') ?: null,
            'cta_label'      => str_input('cta_label') ?: 'Subscribe',
            'features'       => trim((string) input('features', '')) ?: null,
            'is_featured'    => input('is_featured') ? 1 : 0,
            'is_active'      => input('is_active') ? 1 : 0,
            'sort_order'     => (int) input('sort_order', 0),
            'max_contacts'   => $limit('max_contacts'),
            'max_campaigns'  => $limit('max_campaigns'),
            'max_smtp'       => $limit('max_smtp'),
            'monthly_emails' => $limit('monthly_emails'),
        ];

        $id = int_input('id');
        if ($id && Plan::find($id)) {
            Plan::update($id, $data);
            flash('success', 'Plan updated.');
        } else {
            $data['slug'] = $this->uniquePlanSlug($name);
            Plan::insert($data);
            flash('success', 'Plan created.');
        }
        $this->back('admin/plans');
    }

    public function deletePlan(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $id = int_input('id');
        if ($id && Plan::find($id)) {
            // Detach any users currently on this plan so they fall back to free.
            db()->prepare('UPDATE users SET plan_id = NULL WHERE plan_id = ?')->execute([$id]);
            Plan::delete($id);
            flash('success', 'Plan deleted. Users on it were moved to free limits.');
        }
        $this->back('admin/plans');
    }

    /** Build a URL-safe, unique slug from a plan name. */
    private function uniquePlanSlug(string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
        $base = trim((string) $base, '-') ?: 'plan';
        $slug = $base;
        $n = 2;
        while (db_one('SELECT id FROM plans WHERE slug = ?', [$slug]) !== null) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    public function logs(): void
    {
        $this->requireAdmin();
        $this->render('admin/logs', [
            'logs' => SystemLog::recent(300),
        ], 'System Logs');
    }

    // ---- Deliverability (platform-wide + per-tenant bounce/complaint health) ----

    public function deliverability(): void
    {
        $this->requireAdmin();
        $this->render('admin/deliverability', [
            'platform' => Reputation::platformRates(),
            'risky'    => Reputation::riskyTenants(15),
            'recent'   => EmailLog::recentBounceComplaint(30),
        ], 'Deliverability');
    }

    // ---- Global suppression list (platform-wide, protects shared SES) ----

    public function suppression(): void
    {
        $this->requireAdmin();
        $q = str_input('q');
        $page = max(1, int_input('page', 1));
        $perPage = 50;
        $this->render('admin/suppression', [
            'rows'  => GlobalSuppression::search($q, $perPage, ($page - 1) * $perPage),
            'total' => GlobalSuppression::count($q),
            'page'  => $page,
            'pages' => (int) ceil(max(1, GlobalSuppression::count($q)) / $perPage),
            'q'     => $q,
        ], 'Global Suppression List');
    }

    public function addSuppression(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $email = strtolower(str_input('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            GlobalSuppression::add($email, 'manual', $this->uid());
            flash('success', $email . ' added to the platform suppression list. No tenant will be able to send to it.');
        } else {
            flash('error', 'Please enter a valid email address.');
        }
        $this->back('admin/suppression');
    }

    public function removeSuppression(): void
    {
        $this->requireAdmin();
        csrf_guard();
        GlobalSuppression::delete(int_input('id'));
        flash('success', 'Address removed from the platform suppression list.');
        $this->back('admin/suppression');
    }

    // ---- Amazon SES (platform-level sending connection) --------------

    public function ses(): void
    {
        $this->requireAdmin();
        $this->render('admin/ses', [
            'sesConn'              => SesConnection::platform(),
            'autoUpgradeEnabled'   => Setting::get('auto_ses_enabled', '0') === '1',
            'autoUpgradeThreshold' => (int) (Setting::get('auto_ses_threshold', '5000') ?: 5000),
        ], 'Amazon SES');
    }

    /** Auto-upgrade: campaigns over the recipient threshold move the account to domain (SES) sending automatically. */
    public function storeAutoUpgrade(): void
    {
        $this->requireAdmin();
        csrf_guard();
        Setting::set('auto_ses_enabled', input('auto_ses_enabled') ? '1' : '0');
        Setting::set('auto_ses_threshold', (string) max(1, (int) input('auto_ses_threshold', 5000)));
        flash('success', 'Auto-upgrade settings saved.');
        $this->back('admin/ses');
    }

    /** Save (or replace) the platform's single Amazon SES connection, then verify it. */
    public function storeSes(): void
    {
        $this->requireAdmin();
        csrf_guard();

        $accessKey = str_input('access_key');
        $secretKey = (string) input('secret_key', '');
        $region    = str_input('region') ?: 'us-east-1';

        $existing = SesConnection::platform();
        if ($accessKey === '' || ($secretKey === '' && !$existing)) {
            flash('error', 'Access Key and Secret Key are both required.');
            $this->back('admin/ses');
        }
        // Editing with a blank secret keeps the stored one.
        if ($secretKey === '' && $existing) {
            $secretKey = Crypto::decrypt($existing['secret_key']);
        }

        $id = SesConnection::save($this->uid(), $accessKey, $secretKey, $region);
        $res = Ses::verify(['access_key' => $accessKey, 'secret_key' => $secretKey, 'region' => $region]);
        SesConnection::markVerified($id, $res['ok'], $res['error']);

        flash($res['ok'] ? 'success' : 'error', $res['ok']
            ? 'Amazon SES connected — every tenant with a verified Sending Domain can now send campaigns.'
            : 'Saved, but the connection test failed: ' . $res['error']);
        $this->back('admin/ses');
    }

    public function disconnectSes(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $conn = SesConnection::platform();
        if ($conn) {
            SesConnection::delete((int) $conn['id']);
            flash('success', 'Amazon SES disconnected. Domain-based campaigns will stop sending for every tenant until you reconnect.');
        }
        $this->back('admin/ses');
    }

    // ---- Branding (free-plan email footer) ---------------------------

    public function branding(): void
    {
        $this->requireAdmin();
        $this->render('admin/branding', [
            'text' => Setting::get('branding_text', ''),
            'logo' => Setting::get('branding_logo', ''),
            'link' => Setting::get('branding_link', ''),
        ], 'Branding');
    }

    public function storeBranding(): void
    {
        $this->requireAdmin();
        csrf_guard();

        Setting::set('branding_text', str_input('branding_text') ?: '');
        Setting::set('branding_link', str_input('branding_link') ?: '');

        $logoUrl = trim((string) input('branding_logo_url', ''));

        // Remove the existing logo if requested.
        if (input('remove_logo')) {
            $this->deleteBrandingLogoFile();
            Setting::set('branding_logo', '');
        } elseif ($logoUrl !== '') {
            // An explicit public URL takes precedence and is the reliable option
            // for inbox display (uploaded files on localhost can't be loaded by
            // remote email clients).
            if (!preg_match('#^https?://#i', $logoUrl)) {
                flash('error', 'The logo URL must start with http:// or https://');
                $this->back('admin/branding');
            }
            $this->deleteBrandingLogoFile();
            Setting::set('branding_logo', $logoUrl);
        } elseif (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $err = $this->saveBrandingLogo($_FILES['logo']);
            if ($err !== null) {
                flash('error', $err);
                $this->back('admin/branding');
            }
        }

        flash('success', 'Branding saved.');
        $this->back('admin/branding');
    }

    /** Validate + store an uploaded logo. Returns an error string or null on success. */
    private function saveBrandingLogo(array $file): ?string
    {
        if (($file['size'] ?? 0) > 1024 * 1024) {
            return 'Logo must be under 1 MB.';
        }
        $allowed = [
            'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif',
            'image/webp' => 'webp', 'image/svg+xml' => 'svg',
        ];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        // SVG is reported as text/plain or image/svg+xml depending on the host.
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!isset($allowed[$mime]) && !($ext === 'svg' && in_array($mime, ['text/plain', 'text/xml', 'application/xml'], true))) {
            return 'Please upload a PNG, JPG, GIF, WEBP or SVG image.';
        }
        $ext = $allowed[$mime] ?? 'svg';

        $dir = BASE_PATH . '/public/uploads/branding';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $this->deleteBrandingLogoFile();        // drop any previous stored logo
        $name = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return 'Could not save the uploaded file. Check folder permissions.';
        }
        Setting::set('branding_logo', 'uploads/branding/' . $name);
        return null;
    }

    /** Delete the currently-stored local logo file (no-op for remote URLs). */
    private function deleteBrandingLogoFile(): void
    {
        $this->deleteSettingImageFile('branding_logo');
    }

    // ---- Site identity (name, logo, favicon, meta) -------------------

    public function site(): void
    {
        $this->requireAdmin();
        $this->render('admin/site', [
            'siteName'   => Setting::get('site_name', '') ?: APP_NAME,
            'logo'       => Setting::get('site_logo', ''),
            'favicon'    => Setting::get('site_favicon', ''),
            'brandMode'  => Site::brandMode(),
            'metaTitle'  => Setting::get('meta_title', ''),
            'metaDesc'   => Setting::get('meta_description', ''),
            'metaKeys'   => Setting::get('meta_keywords', ''),
        ], 'Site Settings');
    }

    public function storeSite(): void
    {
        $this->requireAdmin();
        csrf_guard();

        Setting::set('site_name', str_input('site_name') ?: '');
        Setting::set('brand_display', in_array(str_input('brand_display'), ['both', 'logo', 'title'], true) ? str_input('brand_display') : 'both');
        Setting::set('meta_title', str_input('meta_title') ?: '');
        Setting::set('meta_description', str_input('meta_description') ?: '');
        Setting::set('meta_keywords', str_input('meta_keywords') ?: '');

        // Logo (sidebar / header). Accepts upload, public URL, or removal.
        $err = $this->applyImageSetting('site_logo', 'logo', 'site', ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], 2 * 1024 * 1024);
        if ($err !== null) {
            flash('error', $err);
            $this->back('admin/site');
        }
        // Favicon.
        $err = $this->applyImageSetting('site_favicon', 'favicon', 'site', ['ico', 'png', 'svg'], 512 * 1024);
        if ($err !== null) {
            flash('error', $err);
            $this->back('admin/site');
        }

        flash('success', 'Site settings saved.');
        $this->back('admin/site');
    }

    /**
     * Apply an image setting from the request: a "remove_<field>" checkbox, a
     * "<field>_url" public URL, or an uploaded "<field>" file (in that order).
     * Returns an error string, or null on success / no change.
     */
    private function applyImageSetting(string $key, string $field, string $subdir, array $allowed, int $maxBytes): ?string
    {
        if (input('remove_' . $field)) {
            $this->deleteSettingImageFile($key);
            Setting::set($key, '');
            return null;
        }
        $url = trim((string) input($field . '_url', ''));
        if ($url !== '') {
            if (!preg_match('#^https?://#i', $url)) {
                return 'The ' . $field . ' URL must start with http:// or https://';
            }
            $this->deleteSettingImageFile($key);
            Setting::set($key, $url);
            return null;
        }
        if (!empty($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return $this->saveSettingImage($_FILES[$field], $key, $subdir, $allowed, $maxBytes);
        }
        return null;
    }

    /** Validate + store an uploaded image into a setting key. Error string or null. */
    private function saveSettingImage(array $file, string $key, string $subdir, array $allowed, int $maxBytes): ?string
    {
        if (($file['size'] ?? 0) > $maxBytes) {
            return ucfirst($key) . ' must be under ' . round($maxBytes / 1048576, 1) . ' MB.';
        }
        $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        // Accept by extension when the MIME looks like an image, or for the
        // text-y reports some hosts give SVG / ICO.
        $special = in_array($ext, ['svg', 'ico'], true)
            && in_array($mime, ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml', 'image/vnd.microsoft.icon', 'image/x-icon', 'application/octet-stream'], true);
        if (!in_array($ext, $allowed, true) || !(str_starts_with((string) $mime, 'image/') || $special)) {
            return 'Please upload a valid ' . strtoupper(implode(' / ', $allowed)) . ' file.';
        }

        $dir = BASE_PATH . '/public/uploads/' . $subdir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $this->deleteSettingImageFile($key);
        $name = $key . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return 'Could not save the uploaded file. Check folder permissions.';
        }
        Setting::set($key, 'uploads/' . $subdir . '/' . $name);
        return null;
    }

    /** Delete a setting's locally-stored file (no-op for remote URLs / unset). */
    private function deleteSettingImageFile(string $key): void
    {
        $old = (string) Setting::get($key, '');
        if ($old !== '' && !preg_match('#^https?://#', $old)) {
            @unlink(BASE_PATH . '/public/' . ltrim($old, '/'));
        }
    }

    // ---- Subscription approval ---------------------------------------

    public function requests(): void
    {
        $this->requireAdmin();
        $this->render('admin/requests', [
            'requests' => PlanRequest::withDetails(),
            'pending'  => PlanRequest::countPending(),
        ], 'Subscription Requests');
    }

    /** Approve a pending request → activate the plan for that user. */
    public function approveRequest(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $req = PlanRequest::find(int_input('id'));
        if (!$req || $req['status'] !== 'pending') {
            flash('error', 'That request is no longer pending.');
            $this->back('admin/requests');
        }

        Billing::activate((int) $req['user_id'], (int) $req['plan_id'], (int) $req['period_months']);
        PlanRequest::update((int) $req['id'], [
            'status'     => 'approved',
            'decided_by' => $this->uid(),
            'decided_at' => date('Y-m-d H:i:s'),
        ]);
        // Record it in the user's payment history.
        Payment::insert([
            'user_id'       => (int) $req['user_id'],
            'plan_id'       => (int) $req['plan_id'],
            'amount'        => (float) $req['amount'],
            'currency'      => $req['currency'],
            'period_months' => (int) $req['period_months'],
            'gateway'       => 'manual',
            'status'        => 'paid',
            'paid_at'       => date('Y-m-d H:i:s'),
        ]);
        SystemLog::write('info', 'billing', "Approved plan request #{$req['id']} (plan {$req['plan_id']}) for user {$req['user_id']}", $this->uid());

        flash('success', 'Request approved — the plan is now active for the user.');
        $this->back('admin/requests');
    }

    public function rejectRequest(): void
    {
        $this->requireAdmin();
        csrf_guard();
        $req = PlanRequest::find(int_input('id'));
        if ($req && $req['status'] === 'pending') {
            PlanRequest::update((int) $req['id'], [
                'status'     => 'rejected',
                'note'       => str_input('note') ?: null,
                'decided_by' => $this->uid(),
                'decided_at' => date('Y-m-d H:i:s'),
            ]);
            SystemLog::write('warning', 'billing', "Rejected plan request #{$req['id']} for user {$req['user_id']}", $this->uid());
            flash('success', 'Request rejected.');
        }
        $this->back('admin/requests');
    }
}
