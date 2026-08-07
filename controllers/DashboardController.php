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
            'onboarding'      => Stats::onboardingProgress($this->user),
            'greeting'        => self::timeOfDayGreeting(),
            'firstName'       => explode(' ', trim((string) ($this->user['name'] ?? 'there')))[0] ?: 'there',
        ], 'Dashboard');
    }

    private static function timeOfDayGreeting(): string
    {
        $hour = (int) date('G');
        if ($hour < 12) {
            return 'Morning';
        }
        return $hour < 17 ? 'Afternoon' : 'Evening';
    }
}
