<?php

declare(strict_types=1);

final class ContactController extends BaseController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $this->requireAuth();
        $page = max(1, int_input('page', 1));
        $opts = [
            'q'             => str_input('q'),
            'list_id'       => int_input('list_id') ?: null,
            'sector'        => str_input('sector') ?: null,
            'location'      => str_input('location') ?: null,
            'status'        => in_array(str_input('status'), ['active', 'unsubscribed', 'bounced'], true) ? str_input('status') : null,
            'verify_status' => in_array(str_input('verify'), ['valid', 'invalid', 'risky', 'unknown', 'unverified'], true) ? str_input('verify') : null,
            'limit'         => self::PER_PAGE,
            'offset'        => ($page - 1) * self::PER_PAGE,
        ];
        $contacts = Contact::search($this->uid(), $opts);
        $total    = Contact::countSearch($this->uid(), $opts);
        $lists    = ContactList::withCounts($this->uid());
        $tags     = ContactTag::withCounts($this->uid());

        $uid = $this->uid();
        $cardStats = Stats::contactsCards($uid);

        $this->render('contacts/index', [
            'contacts'    => $contacts,
            'lists'       => $lists,
            'tags'        => $tags,
            'total'       => $total,
            'page'        => $page,
            'pages'       => (int) ceil(max(1, $total) / self::PER_PAGE),
            'filters'     => $opts,
            'cardStats'   => $cardStats,
            'verifyCounts'=> Contact::verifyCounts($this->uid()),
            'sectors'     => Contact::sectors($this->uid()),
            'locations'   => Contact::locations($this->uid()),
        ], 'Contacts');
    }

    public function store(): void
    {
        $this->requireAuth();
        csrf_guard();
        $email = str_input('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'A valid email is required.');
            $this->back('contacts');
        }

        $id = int_input('id');
        $existing = Contact::findByEmail($this->uid(), $email);

        if ($id && Contact::findForUser($id, $this->uid())) {
            // Editing: block if the email now collides with a different contact.
            if ($existing && (int) $existing['id'] !== $id) {
                flash('error', 'Another contact already uses ' . $email . '.');
                $this->back('contacts');
            }
            Contact::update($id, [
                'email'      => strtolower(trim($email)),
                'first_name' => str_input('first_name') ?: null,
                'last_name'  => str_input('last_name') ?: null,
                'company'    => str_input('company') ?: null,
                'sector'     => str_input('sector') ?: null,
                'location'   => str_input('location') ?: null,
                'phone'      => str_input('phone') ?: null,
            ]);
            // The edit form's multi-select carries the contact's full intended
            // set of lists, so this replaces rather than adds.
            ContactList::setMemberships($id, $this->uid(), (array) ($_POST['list_ids'] ?? []));
            flash('success', 'Contact updated.');
        } else {
            // New contact: reject duplicate emails instead of silently merging.
            if ($existing) {
                flash('error', 'A contact with the email ' . $email . ' already exists.');
                $this->back('contacts');
            }
            if (!Billing::canAddContacts($this->user)) {
                $plan = Billing::effectivePlan($this->user);
                flash('error', "You've reached your plan's contact limit ({$plan['max_contacts']}). Upgrade to add more.");
                redirect('billing');
            }
            $res = Contact::upsert($this->uid(), [
                'email'      => $email,
                'first_name' => str_input('first_name'),
                'last_name'  => str_input('last_name'),
                'company'    => str_input('company'),
                'sector'     => str_input('sector'),
                'location'   => str_input('location'),
                'phone'      => str_input('phone'),
            ]);
            ContactList::setMemberships((int) $res['id'], $this->uid(), (array) ($_POST['list_ids'] ?? []));
            flash('success', 'Contact saved.');
        }
        $this->back('contacts');
    }

    public function delete(): void
    {
        $this->requireAuth();
        csrf_guard();
        Contact::delete(int_input('id'), $this->uid());
        flash('success', 'Contact deleted.');
        $this->back('contacts');
    }

    public function bulkDelete(): void
    {
        $this->requireAuth();
        csrf_guard();
        $ids = $_POST['ids'] ?? [];
        $n = 0;
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    Contact::delete($id, $this->uid());
                    $n++;
                }
            }
        }
        flash('success', $n . ' contact(s) deleted.');
        $this->back('contacts');
    }

    /**
     * Remove duplicate contacts (same email, case-insensitive). Keeps the
     * oldest copy of each address and deletes the rest. Tenant-scoped.
     */
    public function dedupe(): void
    {
        $this->requireAuth();
        csrf_guard();
        $uid = $this->uid();

        $before = (int) db_one('SELECT COUNT(*) FROM contacts WHERE user_id = ?', [$uid]);
        // Delete the higher-id row of any pair that shares a normalised email.
        db()->prepare(
            'DELETE c1 FROM contacts c1
             JOIN contacts c2
               ON c1.user_id = c2.user_id
              AND LOWER(TRIM(c1.email)) = LOWER(TRIM(c2.email))
              AND c1.id > c2.id
             WHERE c1.user_id = ? AND c2.user_id = ?'
        )->execute([$uid, $uid]);
        $removed = $before - (int) db_one('SELECT COUNT(*) FROM contacts WHERE user_id = ?', [$uid]);

        flash($removed > 0 ? 'success' : 'info',
            $removed > 0
                ? "Removed {$removed} duplicate contact(s) — kept the oldest copy of each email."
                : 'No duplicate emails found — your list is already clean.');
        $this->back('contacts');
    }

    /**
     * Stream the current filtered view out as CSV — the same list / status /
     * verification / sector / location / search filters the page is showing,
     * so "export this list" and "export only what verified clean" both fall
     * out of the filters the user already set.
     *
     * Rows are fetched in batches and flushed as they go: a tenant with tens
     * of thousands of contacts would otherwise build the whole file in memory.
     */
    public function export(): void
    {
        $this->requireAuth();
        $uid = $this->uid();

        $opts = [
            'q'             => str_input('q'),
            'list_id'       => int_input('list_id') ?: null,
            'sector'        => str_input('sector') ?: null,
            'location'      => str_input('location') ?: null,
            'status'        => in_array(str_input('status'), ['active', 'unsubscribed', 'bounced'], true) ? str_input('status') : null,
            'verify_status' => in_array(str_input('verify'), ['valid', 'invalid', 'risky', 'unknown', 'unverified'], true) ? str_input('verify') : null,
        ];

        // Name the file after what's in it, so a folder of exports stays sortable.
        $parts = ['contacts'];
        if ($opts['list_id']) {
            $list = ContactList::find((int) $opts['list_id']);
            if ($list && (int) $list['user_id'] === $uid) {
                $parts[] = preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $list['name']));
            }
        }
        if ($opts['verify_status']) {
            $parts[] = $opts['verify_status'];
        }
        $parts[] = date('Y-m-d');
        $filename = trim(preg_replace('/-+/', '-', implode('-', $parts)), '-') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 names correctly
        fputcsv($out, [
            'email', 'first_name', 'last_name', 'company', 'phone',
            'sector', 'location', 'lists', 'status', 'verify_status',
            'verify_reason', 'verified_at', 'added_on',
        ]);

        // A leading = + - @ makes Excel/Sheets treat the cell as a formula.
        $safe = static function ($v): string {
            $v = (string) ($v ?? '');
            return ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
        };

        $offset = 0;
        $batch  = 500;
        do {
            $rows = Contact::search($uid, $opts + ['limit' => $batch, 'offset' => $offset]);
            $listNames = ContactList::namesByContact(array_column($rows, 'id'));
            foreach ($rows as $c) {
                fputcsv($out, [
                    $safe($c['email']),
                    $safe($c['first_name']),
                    $safe($c['last_name']),
                    $safe($c['company']),
                    $safe($c['phone']),
                    $safe($c['sector']),
                    $safe($c['location']),
                    implode(', ', $listNames[(int) $c['id']] ?? []),
                    $c['status'],
                    $c['verify_status'],
                    $safe($c['verify_reason']),
                    $c['verified_at'],
                    $c['created_at'],
                ]);
            }
            flush();
            $offset += $batch;
        } while (count($rows) === $batch);

        fclose($out);
        exit;
    }

    /**
     * Delete contacts whose verification came back invalid (and optionally
     * risky) — one-click list cleaning after a verify run. Tenant-scoped.
     */
    public function cleanInvalid(): void
    {
        $this->requireAuth();
        csrf_guard();
        $uid = $this->uid();
        // 'invalid' (default) or 'both' (invalid + risky).
        $statuses = str_input('scope') === 'both' ? ['invalid', 'risky'] : ['invalid'];
        $in = implode(',', array_fill(0, count($statuses), '?'));

        $removed = (int) db_one(
            "SELECT COUNT(*) FROM contacts WHERE user_id = ? AND verify_status IN ($in)",
            array_merge([$uid], $statuses)
        );
        if ($removed > 0) {
            db()->prepare("DELETE FROM contacts WHERE user_id = ? AND verify_status IN ($in)")
                ->execute(array_merge([$uid], $statuses));
            flash('success', "Removed {$removed} " . implode(' & ', $statuses) . " contact(s) from your list.");
        } else {
            flash('info', 'No matching contacts to remove.');
        }
        $this->back('contacts');
    }

    /**
     * Verify a batch of unverified contacts (AJAX). The client calls this
     * repeatedly until `remaining` reaches 0, showing a progress bar.
     */
    public function verify(): void
    {
        $this->requireAuth();
        csrf_guard();
        $listId = int_input('list_id') ?: null;
        $deep   = (bool) input('deep');               // SMTP mailbox probe (slow, accurate)
        // Deep checks are slow (a few seconds each) so process fewer per request.
        $batch  = Contact::unverifiedBatch($this->uid(), $listId, $deep ? 5 : 20);

        $processed = 0;
        foreach ($batch as $c) {
            $res = EmailVerifier::verify($c['email'], $deep);
            Contact::setVerification((int) $c['id'], $res['status'], $res['reason']);
            // Mirror hard-invalid into the send status so it's excluded everywhere.
            if ($res['status'] === 'invalid') {
                Contact::markStatusByEmail($this->uid(), $c['email'], 'bounced');
            }
            $processed++;
        }

        $counts    = Contact::verifyCounts($this->uid(), $listId);
        json_response([
            'ok'        => true,
            'processed' => $processed,
            'remaining' => $counts['unverified'],
            'counts'    => $counts,
        ]);
    }

    /** Reset verification so a full re-verify can run (AJAX). */
    public function verifyReset(): void
    {
        $this->requireAuth();
        csrf_guard();
        $listId = int_input('list_id') ?: null;
        $sql = "UPDATE contacts SET verify_status = 'unverified' WHERE user_id = ?";
        $params = [$this->uid()];
        if ($listId) {
            $sql .= ' AND EXISTS (SELECT 1 FROM contact_list_map m WHERE m.contact_id = contacts.id AND m.list_id = ?)';
            $params[] = $listId;
        }
        db()->prepare($sql)->execute($params);
        json_response(['ok' => true, 'remaining' => Contact::verifyCounts($this->uid(), $listId)['unverified']]);
    }

    /** Verify a single address on demand (AJAX, e.g. the add form). */
    public function verifyOne(): void
    {
        $this->requireAuth();
        csrf_guard();
        $email = str_input('email');
        json_response(['ok' => true] + EmailVerifier::verify($email, (bool) input('deep')));
    }

    public function import(): void
    {
        $this->requireAuth();
        if ($this->isPost()) {
            csrf_guard();
            $listId = int_input('list_id') ?: null;

            if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
                flash('error', 'Please choose a CSV or Excel file to upload.');
                $this->back('contacts/import');
            }

            $ext = strtolower(pathinfo($_FILES['file']['name'] ?? '', PATHINFO_EXTENSION));
            try {
                $rows = $ext === 'xlsx'
                    ? XlsxReader::rows($_FILES['file']['tmp_name'])
                    : $this->readCsv($_FILES['file']['tmp_name']);
            } catch (\Throwable $e) {
                flash('error', 'Import failed: ' . $e->getMessage());
                $this->back('contacts/import');
            }
            $result = $this->importRows($rows, $listId);
            $rowTotal = max(0, count($rows) - 1); // minus the header row
            $msg = sprintf(
                '%d of %d rows imported: %d new, %d existing updated, %d duplicate rows merged, %d invalid emails skipped.',
                $result['added'] + $result['updated'],
                $rowTotal,
                $result['added'],
                $result['updated'],
                $result['duplicates'],
                $result['invalid']
            );
            if (!empty($result['skippedQuota'])) {
                // The most common "why didn't everything import?" cause, so it
                // leads with the number and says exactly what to do about it.
                flash('error', sprintf(
                    '%d contacts were NOT imported — your plan\'s contact limit is full. Upgrade to import the rest. (%s)',
                    $result['skippedQuota'],
                    $msg
                ));
            } else {
                flash('success', $msg);
            }
            redirect('contacts');
        }
        // Surfaced on the form so a truncated import is predictable rather
        // than something the user only discovers from the result message.
        $plan = Billing::effectivePlan($this->user);
        $max  = (int) $plan['max_contacts'];
        $used = Billing::contactCount($this->uid());

        $this->render('contacts/import', [
            'lists'         => ContactList::withCounts($this->uid()),
            'planName'      => $plan['name'] ?? 'your plan',
            'maxContacts'   => $max,
            'usedContacts'  => $used,
            'roomLeft'      => $max < 0 ? -1 : max(0, $max - $used),
            'maxUploadSize' => ini_get('upload_max_filesize'),
        ], 'Import Contacts');
    }

    /** Read a CSV file into a 2D array (header + rows). */
    private function readCsv(string $path): array
    {
        $rows = [];
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return $rows;
        }
        // Pass all args explicitly (PHP 8.4 deprecates the implicit $escape);
        // escape '' = standard CSV, no backslash escaping.
        while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    /**
     * Import parsed rows (first row = header). Accepts headers in any order;
     * recognises email, first_name/first name, last_name, company, phone.
     * Shared by CSV and XLSX. De-dupes and validates.
     */
    private function importRows(array $rows, ?int $listId): array
    {
        $added = $updated = $invalid = $duplicates = $skippedQuota = 0;

        // Remaining contact allowance for this plan (-1 limit = unlimited).
        $plan      = Billing::effectivePlan($this->user);
        $maxAdd    = (int) $plan['max_contacts'];
        $remaining = $maxAdd < 0 ? PHP_INT_MAX : max(0, $maxAdd - Billing::contactCount($this->uid()));
        $seen = [];

        if ($rows === []) {
            return compact('added', 'updated', 'invalid', 'duplicates', 'skippedQuota');
        }
        $header = array_shift($rows);
        $map = $this->mapHeaders($header);

        // Neutralise spreadsheet formula injection: a value starting with
        // = + - @ (or tab/CR) would execute if the data is later exported and
        // opened in Excel/Sheets. Prefix such values with an apostrophe.
        $deformula = static function (string $v): string {
            return ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;
        };

        foreach ($rows as $row) {
            $get = static fn (string $k) => isset($map[$k]) && isset($row[$map[$k]]) ? $deformula(trim((string) $row[$map[$k]])) : '';
            $email = strtolower($get('email'));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;
                continue;
            }
            if (isset($seen[$email])) {
                $duplicates++;
                continue;
            }
            $seen[$email] = true;

            // Stop adding NEW contacts once the plan limit is reached (updates
            // to existing contacts are always allowed).
            $exists = Contact::findByEmail($this->uid(), $email) !== null;
            if (!$exists && $remaining <= 0) {
                $skippedQuota++;
                continue;
            }

            $res = Contact::upsert($this->uid(), [
                'email'      => $email,
                'first_name' => $get('first_name'),
                'last_name'  => $get('last_name'),
                'company'    => $get('company'),
                'sector'     => $get('sector'),
                'location'   => $get('location'),
                'phone'      => $get('phone'),
                'list_id'    => $listId,
            ]);
            if ($res['inserted']) {
                $added++;
                $remaining--;
            } else {
                $updated++;
            }
        }

        return compact('added', 'updated', 'invalid', 'duplicates', 'skippedQuota');
    }

    private function mapHeaders(array $header): array
    {
        $aliases = [
            'email'      => ['email', 'e-mail', 'email address', 'mail'],
            'first_name' => ['first_name', 'first name', 'firstname', 'fname', 'name'],
            'last_name'  => ['last_name', 'last name', 'lastname', 'lname', 'surname'],
            'company'    => ['company', 'organisation', 'organization', 'business'],
            'sector'     => ['sector', 'industry', 'category', 'segment', 'vertical', 'department'],
            'location'   => ['location', 'city', 'state', 'country', 'region', 'area', 'place', 'zone'],
            'phone'      => ['phone', 'mobile', 'telephone', 'contact'],
        ];
        $map = [];
        foreach ($header as $i => $col) {
            $norm = strtolower(trim((string) $col));
            foreach ($aliases as $field => $names) {
                if (in_array($norm, $names, true) && !isset($map[$field])) {
                    $map[$field] = $i;
                }
            }
        }
        return $map;
    }

    // ---- lists -----------------------------------------------------

    public function lists(): void
    {
        $this->requireAuth();
        $this->render('contacts/lists', [
            'lists' => ContactList::withCounts($this->uid()),
        ], 'Contact Lists');
    }

    public function storeList(): void
    {
        $this->requireAuth();
        csrf_guard();
        $name = str_input('name');
        if ($name === '') {
            flash('error', 'List name is required.');
            $this->back('contacts');
        }
        $id = int_input('id');
        if ($id && ContactList::findForUser($id, $this->uid())) {
            ContactList::update($id, ['name' => $name, 'description' => str_input('description') ?: null]);
        } else {
            ContactList::insert(['user_id' => $this->uid(), 'name' => $name, 'description' => str_input('description') ?: null]);
        }
        flash('success', 'List saved.');
        $this->back('contacts');
    }

    public function deleteList(): void
    {
        $this->requireAuth();
        csrf_guard();
        ContactList::delete(int_input('id'), $this->uid());
        flash('success', 'List deleted.');
        $this->back('contacts');
    }

    public function storeTag(): void
    {
        $this->requireAuth();
        csrf_guard();
        $name = str_input('name');
        if ($name !== '') {
            ContactTag::firstOrCreate($this->uid(), $name);
            flash('success', 'Tag created.');
        }
        $this->back('contacts');
    }
}
