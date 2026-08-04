<?php

declare(strict_types=1);

final class Contact extends Model
{
    protected static string $table = 'contacts';

    public static function findByEmail(int $userId, string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM contacts WHERE user_id = ? AND email = ? LIMIT 1');
        $stmt->execute([$userId, strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Upsert a contact. Returns ['id'=>int,'inserted'=>bool].
     */
    public static function upsert(int $userId, array $data): array
    {
        $email = strtolower(trim($data['email']));
        $existing = self::findByEmail($userId, $email);
        if ($existing) {
            $fields = array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name'  => $data['last_name'] ?? null,
                'company'    => $data['company'] ?? null,
                'sector'     => $data['sector'] ?? null,
                'location'   => $data['location'] ?? null,
                'phone'      => $data['phone'] ?? null,
                'list_id'    => $data['list_id'] ?? null,
            ], static fn ($v) => $v !== null && $v !== '');
            if ($fields !== []) {
                self::update((int) $existing['id'], $fields);
            }
            return ['id' => (int) $existing['id'], 'inserted' => false];
        }

        $id = self::insert([
            'user_id'    => $userId,
            'list_id'    => $data['list_id'] ?? null,
            'email'      => $email,
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'company'    => $data['company'] ?? null,
            'sector'     => $data['sector'] ?? null,
            'location'   => $data['location'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'custom_fields' => isset($data['custom_fields']) ? json_encode($data['custom_fields']) : null,
            'tags'       => $data['tags'] ?? null,
            'status'     => 'active',
        ]);
        return ['id' => $id, 'inserted' => true];
    }

    /** Paginated + filtered listing. */
    public static function search(int $userId, array $opts = []): array
    {
        $where  = ['c.user_id = ?'];
        $params = [$userId];

        if (!empty($opts['q'])) {
            $where[] = '(c.email LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.company LIKE ?)';
            $like = '%' . $opts['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($opts['list_id'])) {
            $where[] = 'c.list_id = ?';
            $params[] = (int) $opts['list_id'];
        }
        if (!empty($opts['sector'])) {
            $where[] = 'c.sector = ?';
            $params[] = $opts['sector'];
        }
        if (!empty($opts['location'])) {
            $where[] = 'c.location = ?';
            $params[] = $opts['location'];
        }
        if (!empty($opts['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $opts['status'];
        }
        if (!empty($opts['verify_status'])) {
            $where[] = 'c.verify_status = ?';
            $params[] = $opts['verify_status'];
        }

        $limit  = max(1, (int) ($opts['limit'] ?? 50));
        $offset = max(0, (int) ($opts['offset'] ?? 0));
        $sql = 'SELECT c.* FROM contacts c WHERE ' . implode(' AND ', $where)
             . ' ORDER BY c.created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countSearch(int $userId, array $opts = []): int
    {
        $where  = ['user_id = ?'];
        $params = [$userId];
        if (!empty($opts['q'])) {
            $where[] = '(email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR company LIKE ?)';
            $like = '%' . $opts['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($opts['list_id'])) {
            $where[] = 'list_id = ?';
            $params[] = (int) $opts['list_id'];
        }
        if (!empty($opts['sector'])) {
            $where[] = 'sector = ?';
            $params[] = $opts['sector'];
        }
        if (!empty($opts['location'])) {
            $where[] = 'location = ?';
            $params[] = $opts['location'];
        }
        if (!empty($opts['status'])) {
            $where[] = 'status = ?';
            $params[] = $opts['status'];
        }
        if (!empty($opts['verify_status'])) {
            $where[] = 'verify_status = ?';
            $params[] = $opts['verify_status'];
        }
        $stmt = db()->prepare('SELECT COUNT(*) FROM contacts WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Distinct non-empty sectors for a user (for filter dropdowns). */
    public static function sectors(int $userId): array
    {
        $stmt = db()->prepare("SELECT sector, COUNT(*) c FROM contacts WHERE user_id = ? AND sector IS NOT NULL AND sector <> '' GROUP BY sector ORDER BY sector ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Distinct non-empty locations for a user (for filter dropdowns). */
    public static function locations(int $userId): array
    {
        $stmt = db()->prepare("SELECT location, COUNT(*) c FROM contacts WHERE user_id = ? AND location IS NOT NULL AND location <> '' GROUP BY location ORDER BY location ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Active contacts for a campaign audience, optionally filtered by list,
     * sector and location. Used by the campaign builder and audience preview.
     */
    public static function activeInList(int $userId, ?int $listId, ?string $sector = null, ?string $location = null): array
    {
        $sql = "SELECT * FROM contacts WHERE user_id = ? AND status = 'active'";
        $params = [$userId];
        if ($listId) {
            $sql .= ' AND list_id = ?';
            $params[] = $listId;
        }
        if ($sector !== null && $sector !== '') {
            $sql .= ' AND sector = ?';
            $params[] = $sector;
        }
        if ($location !== null && $location !== '') {
            $sql .= ' AND location = ?';
            $params[] = $location;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function markStatusByEmail(int $userId, string $email, string $status): void
    {
        $stmt = db()->prepare('UPDATE contacts SET status = ? WHERE user_id = ? AND email = ?');
        $stmt->execute([$status, $userId, strtolower(trim($email))]);
    }

    // ---- Email verification --------------------------------------------
    /** Fetch a batch of not-yet-verified contacts (optionally one list). */
    public static function unverifiedBatch(int $userId, ?int $listId, int $limit): array
    {
        $sql = "SELECT id, email FROM contacts WHERE user_id = ? AND verify_status = 'unverified'";
        $params = [$userId];
        if ($listId) {
            $sql .= ' AND list_id = ?';
            $params[] = $listId;
        }
        $sql .= ' LIMIT ' . (int) $limit;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function setVerification(int $id, string $status, string $reason): void
    {
        db()->prepare("UPDATE contacts SET verify_status = ?, verify_reason = ?, verified_at = NOW() WHERE id = ?")
            ->execute([$status, $reason, $id]);
    }

    /** Count contacts grouped by verification status (optionally one list). */
    public static function verifyCounts(int $userId, ?int $listId = null): array
    {
        $sql = 'SELECT verify_status, COUNT(*) c FROM contacts WHERE user_id = ?';
        $params = [$userId];
        if ($listId) {
            $sql .= ' AND list_id = ?';
            $params[] = $listId;
        }
        $sql .= ' GROUP BY verify_status';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $out = ['unverified' => 0, 'valid' => 0, 'invalid' => 0, 'risky' => 0, 'unknown' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $out[$r['verify_status']] = (int) $r['c'];
        }
        return $out;
    }
}
