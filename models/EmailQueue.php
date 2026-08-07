<?php

declare(strict_types=1);

final class EmailQueue extends Model
{
    protected static string $table = 'email_queue';

    /** Claim a batch of due emails for sending (atomic-ish on shared hosting). */
    public static function claimBatch(int $limit): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM email_queue
                 WHERE status = 'queued' AND (send_after IS NULL OR send_after <= NOW())
                   AND (ab_variant IS NULL OR ab_variant IN ('a','b'))
                 ORDER BY id ASC LIMIT $limit FOR UPDATE"
            );
            $stmt->execute();
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($ids === []) {
                $pdo->commit();
                return [];
            }
            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE email_queue SET status = 'sending' WHERE id IN ($in)")->execute($ids);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $pdo->prepare("SELECT * FROM email_queue WHERE id IN ($in) ORDER BY id ASC");
        $rows->execute($ids);
        return $rows->fetchAll();
    }

    public static function markSent(int $id, ?int $smtpId, ?string $sesMessageId = null): void
    {
        db()->prepare("UPDATE email_queue SET status = 'sent', smtp_id = ?, ses_message_id = ?, sent_at = NOW(), error = NULL WHERE id = ?")
            ->execute([$smtpId, $sesMessageId, $id]);
    }

    public static function markFailed(int $id, string $error, bool $retry): void
    {
        $status = $retry ? 'queued' : 'failed';
        db()->prepare(
            "UPDATE email_queue SET status = ?, attempts = attempts + 1, error = ?,
             send_after = IF(? = 'queued', DATE_ADD(NOW(), INTERVAL 10 MINUTE), send_after) WHERE id = ?"
        )->execute([$status, mb_substr($error, 0, 500), $status, $id]);
    }

    public static function findByTracking(string $trackingId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM email_queue WHERE tracking_id = ? LIMIT 1');
        $stmt->execute([$trackingId]);
        return $stmt->fetch() ?: null;
    }

    /** Matches an inbound SES/SNS bounce or complaint notification back to the send that triggered it. */
    public static function findBySesMessageId(string $messageId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM email_queue WHERE ses_message_id = ? LIMIT 1');
        $stmt->execute([$messageId]);
        return $stmt->fetch() ?: null;
    }

    public static function pendingCount(int $userId): int
    {
        $stmt = db()->prepare("SELECT COUNT(*) FROM email_queue WHERE user_id = ? AND status IN ('queued','sending')");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
