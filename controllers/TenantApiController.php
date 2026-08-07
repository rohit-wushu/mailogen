<?php

declare(strict_types=1);

/** Read-only stats/contacts + add-contact REST API for tenants (see docs at Settings > API). */
final class TenantApiController extends ApiController
{
    private const CONTACT_FIELDS = ['id', 'email', 'first_name', 'last_name', 'company', 'phone', 'sector', 'location', 'status', 'created_at'];

    /** GET api/v1/contacts?limit=&offset= */
    public function contacts(): void
    {
        if ($this->isPost()) {
            $this->createContact();
            return;
        }

        $limit  = max(1, min(200, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $stmt = db()->prepare(
            'SELECT ' . implode(',', self::CONTACT_FIELDS) . " FROM contacts WHERE user_id = ? ORDER BY id DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute([$this->uid()]);
        $rows = $stmt->fetchAll();

        $total = (int) db_one('SELECT COUNT(*) FROM contacts WHERE user_id = ?', [$this->uid()]);

        self::ok(['data' => $rows, 'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset]]);
    }

    /** POST api/v1/contacts — body: email (required), first_name, last_name, company, phone, sector, location */
    private function createContact(): void
    {
        $body = self::jsonOrFormBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::fail(422, 'A valid "email" is required.');
        }

        if (!Billing::canAddContacts($this->tenant)) {
            $plan = Billing::effectivePlan($this->tenant);
            self::fail(403, "Plan limit reached ({$plan['max_contacts']} contacts). Upgrade to add more.");
        }

        $result = Contact::upsert($this->uid(), [
            'email'      => $email,
            'first_name' => (string) ($body['first_name'] ?? ''),
            'last_name'  => (string) ($body['last_name'] ?? ''),
            'company'    => (string) ($body['company'] ?? ''),
            'phone'      => (string) ($body['phone'] ?? ''),
            'sector'     => (string) ($body['sector'] ?? ''),
            'location'   => (string) ($body['location'] ?? ''),
        ]);
        $contact = Contact::find((int) $result['id']);
        self::ok(['data' => array_intersect_key($contact, array_flip(self::CONTACT_FIELDS))], 201);
    }

    /** GET api/v1/campaigns?limit=&offset= — summary list with basic stats per campaign. */
    public function campaigns(): void
    {
        $limit  = max(1, min(200, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $stmt = db()->prepare(
            "SELECT id, name, subject, status, total_recipients, sent_count, scheduled_at, created_at
             FROM campaigns WHERE user_id = ? ORDER BY id DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute([$this->uid()]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['opens']  = (int) db_one('SELECT COUNT(DISTINCT contact_id) FROM opens WHERE campaign_id = ?', [$row['id']]);
            $row['clicks'] = (int) db_one('SELECT COUNT(DISTINCT contact_id) FROM clicks WHERE campaign_id = ?', [$row['id']]);
        }
        unset($row);

        $total = (int) db_one('SELECT COUNT(*) FROM campaigns WHERE user_id = ?', [$this->uid()]);
        self::ok(['data' => $rows, 'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset]]);
    }

    /** GET api/v1/campaigns/show?id= — a single campaign's detail + stats. */
    public function campaignShow(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $campaign = Campaign::findForUser($id, $this->uid());
        if (!$campaign) {
            self::fail(404, 'Campaign not found.');
        }
        $campaign['opens']  = (int) db_one('SELECT COUNT(DISTINCT contact_id) FROM opens WHERE campaign_id = ?', [$id]);
        $campaign['clicks'] = (int) db_one('SELECT COUNT(DISTINCT contact_id) FROM clicks WHERE campaign_id = ?', [$id]);
        unset($campaign['body_html']); // keep the payload light; fetch via the dashboard if the raw HTML is needed
        self::ok(['data' => $campaign]);
    }

    /** GET api/v1/stats — account-wide summary, same numbers as the dashboard. */
    public function stats(): void
    {
        self::ok(['data' => Stats::dashboard($this->uid())]);
    }

    /** @return array<string,mixed> */
    private static function jsonOrFormBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }
}
