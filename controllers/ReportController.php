<?php

declare(strict_types=1);

final class ReportController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $uid = $this->uid();

        $campaignsList = Campaign::withStats($uid);
        $selectedId = int_input('campaign_id');
        if ($selectedId === 0 && $campaignsList) {
            $selectedId = (int) $campaignsList[0]['id'];
        }

        // Summary for the selected campaign (or zeros).
        $summary = ['sent' => 0, 'delivered' => 0, 'opened' => 0, 'clicked' => 0, 'unsubscribed' => 0, 'bounced' => 0, 'name' => '—', 'open_rate' => 0, 'click_rate' => 0];
        $topLinks = [];
        if ($selectedId) {
            foreach (Stats::campaignReport($uid) as $r) {
                if ((int) $r['id'] === $selectedId) {
                    $sent = max(1, (int) $r['sent']);
                    $summary = [
                        'name' => $r['name'], 'sent' => (int) $r['sent'], 'delivered' => (int) $r['sent'],
                        'opened' => (int) $r['opened'], 'clicked' => (int) $r['clicked'],
                        'unsubscribed' => (int) $r['unsubscribed'], 'bounced' => (int) $r['failed'],
                        'open_rate' => round((int) $r['opened'] / $sent * 100, 1),
                        'click_rate' => round((int) $r['clicked'] / $sent * 100, 1),
                    ];
                    break;
                }
            }
            $stmt = db()->prepare(
                'SELECT url, COUNT(*) c FROM clicks WHERE user_id = ? AND campaign_id = ? GROUP BY url ORDER BY c DESC LIMIT 6'
            );
            $stmt->execute([$uid, $selectedId]);
            $topLinks = $stmt->fetchAll();
        }
        $totalClicks = array_sum(array_map(static fn ($l) => (int) $l['c'], $topLinks)) ?: 1;

        $this->render('reports/index', [
            'campaignsList' => $campaignsList,
            'selectedId'    => $selectedId,
            'summary'       => $summary,
            'topLinks'      => $topLinks,
            'totalClicks'   => $totalClicks,
            'campaigns'     => Stats::campaignReport($uid),
            'smtp'          => Stats::smtpReport($uid),
            'series'        => Stats::timeseries($uid, 14),
            'logs'          => EmailLog::recent($uid, 60),
        ], 'Campaign Report');
    }
}
