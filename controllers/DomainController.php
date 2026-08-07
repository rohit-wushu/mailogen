<?php

declare(strict_types=1);

final class DomainController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        $domains = Domain::allForUser($this->uid());
        $records = [];
        foreach ($domains as $d) {
            $records[(int) $d['id']] = Domain::dnsRecords($d);
        }
        $this->render('domains/index', [
            'domains' => $domains,
            'records' => $records,
            'tab'     => 'domains',
        ], 'Authentication');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $domain = strtolower(str_input('domain'));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim(explode('/', $domain)[0], '.');

        if ($domain === '' || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $domain)) {
            flash('error', 'Enter a valid domain (e.g. yourcompany.com), without http:// or a path.');
            $this->back('domains');
        }

        $existing = Domain::allForUser($this->uid());
        if (in_array($domain, array_column($existing, 'domain'), true)) {
            flash('error', 'That domain is already added.');
            $this->back('domains');
        }

        $keys = Dkim::generateKeyPair();
        Domain::insert([
            'user_id'          => $this->uid(),
            'domain'           => $domain,
            'dkim_selector'    => 'em' . bin2hex(random_bytes(3)),
            'dkim_private_key' => Crypto::encrypt($keys['private']),
            'dkim_public_key'  => $keys['public'],
        ]);

        flash('success', 'Domain added. Publish the DNS records shown below, then click Verify.');
        redirect('domains');
    }

    public function verify(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $domain = Domain::findForUser(int_input('id'), $this->uid());
        if (!$domain) {
            flash('error', 'Domain not found.');
            $this->back('domains');
        }

        $refreshed = Domain::verify($domain);
        flash($refreshed['is_verified'] ? 'success' : 'error', $refreshed['is_verified']
            ? 'Domain verified — sending accounts linked to it can now send.'
            : 'Not verified yet: ' . implode(', ', array_filter([
                $refreshed['spf_verified']  ? null : 'SPF record not found',
                $refreshed['dkim_verified'] ? null : 'DKIM record not found or does not match',
            ])) . '. DNS changes can take time to propagate — try again shortly.');
        $this->back('domains');
    }

    public function delete(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        Domain::delete(int_input('id'), $this->uid());
        flash('success', 'Domain removed. Any SMTP accounts linked to it now send without domain gating.');
        redirect('domains');
    }

    // ---- Sender Management ------------------------------------------

    public function senders(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        $verifiedDomains = array_values(array_filter(
            Domain::allForUser($this->uid()),
            static fn ($d) => (int) $d['is_verified'] === 1
        ));
        $this->render('domains/senders', [
            'senders'         => Sender::withDomain($this->uid()),
            'verifiedDomains' => $verifiedDomains,
            'tab'             => 'senders',
        ], 'Authentication');
    }

    public function storeSender(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $domainId = int_input('domain_id');
        $domain   = $domainId ? Domain::findForUser($domainId, $this->uid()) : null;
        if (!$domain || (int) $domain['is_verified'] !== 1) {
            flash('error', 'Select a verified domain.');
            $this->back('domains/senders');
        }

        $name  = str_input('name');
        $email = strtolower(trim((string) input('email', '')));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@' . $domain['domain'])) {
            flash('error', 'Enter a sender name and an email address on @' . $domain['domain'] . '.');
            $this->back('domains/senders');
        }
        if (in_array($email, array_column(Sender::allForUser($this->uid()), 'email'), true)) {
            flash('error', 'You already have a sender with that email.');
            $this->back('domains/senders');
        }

        $isDefault = input('is_default') ? 1 : 0;
        if ($isDefault) {
            Sender::clearDefault($this->uid());
        }
        Sender::insert([
            'user_id'    => $this->uid(),
            'domain_id'  => $domainId,
            'name'       => $name,
            'email'      => $email,
            'is_default' => $isDefault,
        ]);
        flash('success', 'Sender added.');
        redirect('domains/senders');
    }

    public function deleteSender(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        Sender::delete(int_input('id'), $this->uid());
        flash('success', 'Sender removed.');
        redirect('domains/senders');
    }

    // ---- Reply IDs -----------------------------------------------------

    public function replyIds(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        $this->render('domains/reply_ids', [
            'replyIds' => ReplyId::allForUser($this->uid(), 'is_default DESC, created_at DESC'),
            'tab'      => 'reply-ids',
        ], 'Authentication');
    }

    public function storeReplyId(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $email = strtolower(trim((string) input('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email address.');
            $this->back('domains/reply-ids');
        }
        if (in_array($email, array_column(ReplyId::allForUser($this->uid()), 'email'), true)) {
            flash('error', 'You already have a Reply ID with that email.');
            $this->back('domains/reply-ids');
        }

        $isDefault = input('is_default') ? 1 : 0;
        if ($isDefault) {
            ReplyId::clearDefault($this->uid());
        }
        ReplyId::insert([
            'user_id'    => $this->uid(),
            'email'      => $email,
            'label'      => str_input('label') ?: null,
            'is_default' => $isDefault,
        ]);
        flash('success', 'Reply ID added.');
        redirect('domains/reply-ids');
    }

    public function deleteReplyId(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        ReplyId::delete(int_input('id'), $this->uid());
        flash('success', 'Reply ID removed.');
        redirect('domains/reply-ids');
    }
}
