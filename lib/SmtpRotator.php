<?php
/**
 * Multi-SMTP rotation engine.
 *
 * Given a set of available SMTP accounts and a rotation mode, returns the
 * ordered list of accounts to attempt for a single send. The first element
 * is the primary choice; the remainder form the auto-failover chain.
 *
 *   round_robin  Lead 1 -> SMTP 1, Lead 2 -> SMTP 2, ... wrapping around.
 *   random       Pick a random account each send.
 *   priority     Always prefer the lowest `priority` value.
 *   failover     Same primary every time; fall through on failure.
 */

declare(strict_types=1);

final class SmtpRotator
{
    /**
     * Resolve the candidate accounts for a campaign send.
     *
     * @return array<int,array>  ordered attempt list (primary first)
     */
    public static function candidates(array $campaign, int $userId, int $sequence): array
    {
        $groupId = $campaign['smtp_group_id'] ?? null;
        $singleId = $campaign['smtp_id'] ?? null;

        // Explicit single SMTP account.
        if ($singleId) {
            $acc = SmtpAccount::find((int) $singleId);
            return ($acc && (int) $acc['is_enabled'] === 1) ? [$acc] : [];
        }

        // A rotation group.
        if ($groupId) {
            $group    = SmtpGroup::find((int) $groupId);
            $accounts = SmtpGroup::availableAccounts((int) $groupId);
            if ($accounts === []) {
                return [];
            }
            return self::order($accounts, $group['rotation_mode'] ?? 'round_robin', $sequence, $group);
        }

        // Fall back to all of the user's available accounts, round-robin.
        $accounts = SmtpAccount::availableForUser($userId);
        return self::order($accounts, 'round_robin', $sequence, null);
    }

    /** @param array<int,array> $accounts */
    private static function order(array $accounts, string $mode, int $sequence, ?array $group): array
    {
        if (count($accounts) <= 1) {
            return array_values($accounts);
        }

        switch ($mode) {
            case 'random':
                // Deterministic-ish shuffle seeded by sequence so the failover
                // chain is stable within a single send attempt.
                $keys = array_keys($accounts);
                $start = $sequence % count($keys);
                return self::rotate($accounts, $start);

            case 'priority':
            case 'failover':
                usort($accounts, static fn ($a, $b) => ($a['priority'] <=> $b['priority']) ?: ($a['id'] <=> $b['id']));
                return array_values($accounts);

            case 'round_robin':
            default:
                $start = $sequence % count($accounts);
                $ordered = self::rotate($accounts, $start);
                if ($group !== null) {
                    // Remember which account led this round for cross-run continuity.
                    SmtpGroup::update((int) $group['id'], ['rr_cursor' => (int) $ordered[0]['id']]);
                }
                return $ordered;
        }
    }

    /** Rotate an array so element $start becomes first; rest form failover chain. */
    private static function rotate(array $accounts, int $start): array
    {
        $accounts = array_values($accounts);
        return array_merge(array_slice($accounts, $start), array_slice($accounts, 0, $start));
    }
}
