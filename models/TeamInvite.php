<?php

declare(strict_types=1);

final class TeamInvite extends Model
{
    protected static string $table = 'team_invites';

    public static function findByToken(string $token): ?array
    {
        $stmt = db()->prepare('SELECT * FROM team_invites WHERE token = ? AND accepted_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /** Pending (not yet accepted, not expired) invites for a tenant. */
    public static function pendingForOwner(int $ownerId): array
    {
        $stmt = db()->prepare('SELECT * FROM team_invites WHERE owner_id = ? AND accepted_at IS NULL AND expires_at > NOW() ORDER BY created_at DESC');
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    public static function create(int $ownerId, string $email, string $teamRole, int $invitedBy): array
    {
        $token = bin2hex(random_bytes(24));
        $id = self::insert([
            'owner_id'    => $ownerId,
            'email'       => strtolower(trim($email)),
            'team_role'   => in_array($teamRole, ['admin', 'member'], true) ? $teamRole : 'member',
            'token'       => $token,
            'invited_by'  => $invitedBy,
            'expires_at'  => date('Y-m-d H:i:s', time() + 7 * 86400),
        ]);
        return self::find($id);
    }

    public static function markAccepted(int $id): void
    {
        self::update($id, ['accepted_at' => date('Y-m-d H:i:s')]);
    }
}
