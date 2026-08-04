<?php

declare(strict_types=1);

/**
 * Suppression list — addresses that must never be emailed again
 * (unsubscribes, bounces, complaints). Good list hygiene = better deliverability.
 */
final class SuppressionController extends BaseController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $this->requireAuth();
        $uid = $this->uid();

        $type = str_input('type');     // '', 'unsubscribe', 'bounce', 'complaint'
        $q    = str_input('q');
        $page = max(1, int_input('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $where  = ['user_id = ?'];
        $params = [$uid];
        if ($type === 'bounce') {
            $where[] = 'reason LIKE ?';
            $params[] = '%bounce%';
        } elseif ($type === 'complaint') {
            $where[] = '(reason LIKE ? OR reason LIKE ?)';
            $params[] = '%complaint%';
            $params[] = '%spam%';
        } elseif ($type === 'unsubscribe') {
            $where[] = '(reason IS NULL OR (reason NOT LIKE ? AND reason NOT LIKE ? AND reason NOT LIKE ?))';
            $params[] = '%bounce%';
            $params[] = '%complaint%';
            $params[] = '%spam%';
        }
        if ($q !== '') {
            $where[] = 'email LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) db_one("SELECT COUNT(*) FROM unsubscribes WHERE $whereSql", $params);
        $stmt = db()->prepare("SELECT * FROM unsubscribes WHERE $whereSql ORDER BY created_at DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (int) $offset);
        $stmt->execute($params);

        // Counts by category for the summary cards.
        $counts = [
            'total'       => (int) db_one('SELECT COUNT(*) FROM unsubscribes WHERE user_id = ?', [$uid]),
            'bounce'      => (int) db_one("SELECT COUNT(*) FROM unsubscribes WHERE user_id = ? AND reason LIKE '%bounce%'", [$uid]),
            'complaint'   => (int) db_one("SELECT COUNT(*) FROM unsubscribes WHERE user_id = ? AND (reason LIKE '%complaint%' OR reason LIKE '%spam%')", [$uid]),
        ];
        $counts['unsubscribe'] = $counts['total'] - $counts['bounce'] - $counts['complaint'];

        $this->render('suppression/index', [
            'rows'    => $stmt->fetchAll(),
            'counts'  => $counts,
            'total'   => $total,
            'page'    => $page,
            'pages'   => (int) ceil(max(1, $total) / self::PER_PAGE),
            'filters' => ['type' => $type, 'q' => $q],
        ], 'Suppression List');
    }

    /** Manually add an address to the suppression list. */
    public function add(): void
    {
        $this->requireAuth();
        csrf_guard();
        $email = strtolower(str_input('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Unsubscribe::add($this->uid(), $email, null, 'manual');
            flash('success', $email . ' added to your suppression list.');
        } else {
            flash('error', 'Please enter a valid email address.');
        }
        $this->back('suppression');
    }

    /** Remove a suppression (re-allow sending to this address). */
    public function remove(): void
    {
        $this->requireAuth();
        csrf_guard();
        db()->prepare('DELETE FROM unsubscribes WHERE id = ? AND user_id = ?')
            ->execute([int_input('id'), $this->uid()]);
        flash('success', 'Address removed from the suppression list.');
        $this->back('suppression');
    }
}
