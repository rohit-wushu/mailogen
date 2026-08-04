<?php

declare(strict_types=1);

/**
 * Sent Emails log — every individual email the platform has queued/sent for
 * the user, with delivery status and open/click engagement.
 */
final class MailLogController extends BaseController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $this->requireAuth();
        $uid = $this->uid();

        $status   = str_input('status');
        $campaign = int_input('campaign_id');
        $q        = str_input('q');
        $page     = max(1, int_input('page', 1));
        $offset   = ($page - 1) * self::PER_PAGE;

        // Build a scoped WHERE clause from a fixed whitelist of filters.
        $where  = ['eq.user_id = ?'];
        $params = [$uid];
        if (in_array($status, ['sent', 'failed', 'queued', 'sending'], true)) {
            $where[] = 'eq.status = ?';
            $params[] = $status;
        }
        if ($campaign > 0) {
            $where[] = 'eq.campaign_id = ?';
            $params[] = $campaign;
        }
        if ($q !== '') {
            $where[] = '(eq.email LIKE ? OR eq.subject LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) db_one("SELECT COUNT(*) FROM email_queue eq WHERE $whereSql", $params);

        $sql = "SELECT eq.id, eq.email, eq.subject, eq.status, eq.sent_at, eq.created_at,
                       eq.error, eq.campaign_id, eq.workflow_id, eq.step,
                       c.name AS campaign_name, s.label AS smtp_label,
                       (SELECT COUNT(*) FROM opens o  WHERE o.queue_id  = eq.id) AS opens,
                       (SELECT COUNT(*) FROM clicks k WHERE k.queue_id  = eq.id) AS clicks
                FROM email_queue eq
                LEFT JOIN campaigns c      ON c.id = eq.campaign_id
                LEFT JOIN smtp_accounts s  ON s.id = eq.smtp_id
                WHERE $whereSql
                ORDER BY COALESCE(eq.sent_at, eq.created_at) DESC
                LIMIT " . self::PER_PAGE . ' OFFSET ' . (int) $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Headline counts (respect the user scope, ignore paging/filters).
        $counts = db()->prepare(
            "SELECT
               COUNT(*) AS total,
               SUM(status = 'sent')   AS sent,
               SUM(status = 'failed') AS failed,
               SUM(status IN ('queued','sending')) AS pending
             FROM email_queue WHERE user_id = ?"
        );
        $counts->execute([$uid]);

        $this->render('maillog/index', [
            'rows'      => $rows,
            'counts'    => $counts->fetch() ?: ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0],
            'campaigns' => Campaign::allForUser($uid, 'created_at DESC'),
            'total'     => $total,
            'page'      => $page,
            'pages'     => (int) ceil(max(1, $total) / self::PER_PAGE),
            'filters'   => ['status' => $status, 'campaign_id' => $campaign, 'q' => $q],
        ], 'Sent Emails');
    }

    /** Render the exact email body that was sent to one recipient (in an iframe). */
    public function view(): void
    {
        $this->requireAuth();
        $row = EmailQueue::findForUser(int_input('id'), $this->uid());
        header('Content-Type: text/html; charset=UTF-8');
        echo $row['body_html'] ?? '<p style="font-family:sans-serif;color:#888;padding:20px">Message not found.</p>';
        exit;
    }
}
