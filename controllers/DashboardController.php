<?php

declare(strict_types=1);

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $stats = Stats::dashboard($this->uid());
        $cards = Stats::dashboardCards($this->uid());
        $series = Stats::timeseries($this->uid(), 14);
        $allCampaigns = Campaign::withStats($this->uid());
        $recentCampaigns = array_slice($allCampaigns, 0, 5);

        // Top performing campaign by open rate (needs at least one send).
        $top = null;
        foreach ($allCampaigns as $c) {
            $sent = max(1, (int) $c['sent_count']);
            $c['open_rate']  = round((int) $c['opens'] / $sent * 100, 1);
            $c['click_rate'] = round((int) $c['clicks'] / $sent * 100, 1);
            if ($top === null || $c['open_rate'] > $top['open_rate']) {
                $top = $c;
            }
        }

        $smtpAccounts = array_slice(SmtpAccount::allForUser($this->uid()), 0, 4);

        $this->render('dashboard', [
            'stats'           => $stats,
            'cards'           => $cards,
            'series'          => $series,
            'recentCampaigns' => $recentCampaigns,
            'topCampaign'     => $top,
            'smtpAccounts'    => $smtpAccounts,
            'onboarding'      => $this->onboarding(),
        ], 'Dashboard');
    }

    /** First-run setup checklist (hidden once every step is done). */
    private function onboarding(): array
    {
        $uid  = $this->uid();
        $sent = (int) db_one("SELECT COUNT(*) FROM email_logs WHERE user_id = ? AND event = 'sent'", [$uid]);
        $steps = [
            ['done' => (int) db_one('SELECT COUNT(*) FROM smtp_accounts WHERE user_id = ?', [$uid]) > 0,
             'title' => 'Connect a sending account', 'desc' => 'Add your SMTP (Gmail, Brevo, SES…).', 'url' => 'smtp', 'icon' => 'hdd-network'],
            ['done' => (int) db_one('SELECT COUNT(*) FROM contacts WHERE user_id = ?', [$uid]) > 0,
             'title' => 'Add contacts', 'desc' => 'Import a CSV or add them manually.', 'url' => 'contacts/import', 'icon' => 'people'],
            ['done' => trim((string) ($this->user['org_address'] ?? '')) !== '',
             'title' => 'Set your mailing address', 'desc' => 'Required by law on every email footer.', 'url' => 'settings', 'icon' => 'geo-alt'],
            ['done' => (int) db_one('SELECT COUNT(*) FROM campaigns WHERE user_id = ?', [$uid]) > 0,
             'title' => 'Create a campaign', 'desc' => 'Design your first email.', 'url' => 'campaigns/edit', 'icon' => 'megaphone'],
            ['done' => $sent > 0,
             'title' => 'Send your first email', 'desc' => 'Launch a campaign or send a test.', 'url' => 'campaigns', 'icon' => 'send'],
        ];
        $doneCount = count(array_filter($steps, fn ($s) => $s['done']));
        return ['steps' => $steps, 'done' => $doneCount, 'total' => count($steps), 'complete' => $doneCount === count($steps)];
    }
}
