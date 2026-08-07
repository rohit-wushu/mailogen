<?php

declare(strict_types=1);

final class Domain extends Model
{
    protected static string $table = 'domains';

    /** Decrypt the stored DKIM private key for signing. */
    public static function privateKey(array $domain): string
    {
        return Crypto::decrypt($domain['dkim_private_key']);
    }

    /** Is $email's domain verified for this user (SPF + DKIM both pass)? */
    public static function isVerifiedForEmail(int $userId, string $email): bool
    {
        $host = strtolower(substr((string) strrchr($email, '@'), 1));
        if ($host === '') {
            return false;
        }
        $stmt = db()->prepare('SELECT is_verified FROM domains WHERE user_id = ? AND domain = ? LIMIT 1');
        $stmt->execute([$userId, $host]);
        $row = $stmt->fetch();
        return $row !== false && (int) $row['is_verified'] === 1;
    }

    /**
     * DNS records the user needs to publish, keyed for the UI.
     * SPF/DMARC are checked for *presence* only (we can't dictate the
     * mechanism — it depends on whichever SMTP relay/ESP they route
     * through), DKIM is checked against the exact key we generated.
     */
    public static function dnsRecords(array $domain): array
    {
        $host = $domain['domain'];
        $dkimHost  = $domain['dkim_selector'] . '._domainkey.' . $host;
        $dmarcHost = '_dmarc.' . $host;
        return [
            'spf' => [
                'type'      => 'TXT',
                'host'      => $host,
                'hostShort' => '@',
                'nameNote'  => 'This one DOES go at the root — enter "@" (or leave the Name/Host field blank, whichever your provider expects for the root domain).',
                'sample'    => 'v=spf1 include:_spf.google.com ~all',
                'hint'      => 'Add (or extend) an SPF record on the apex domain that includes whichever service actually sends your mail (Gmail/Workspace: include:_spf.google.com, Amazon SES: include:amazonses.com, etc.) — merge into one record if you already have one.',
            ],
            'dkim' => [
                'type'      => 'TXT',
                'host'      => $dkimHost,
                'hostShort' => $domain['dkim_selector'] . '._domainkey',
                'nameNote'  => 'Do NOT use "@" or leave this blank — that publishes it at your root domain instead, where nothing will ever find it.',
                'sample'    => 'v=DKIM1; k=rsa; p=' . $domain['dkim_public_key'],
                'hint'      => 'Publish exactly this value — it is the public half of the key this platform signs your mail with.',
            ],
            'dmarc' => [
                'type'      => 'TXT',
                'host'      => $dmarcHost,
                'hostShort' => '_dmarc',
                'nameNote'  => 'Do NOT use "@" or leave this blank — that publishes it at your root domain instead, where nothing will ever find it.',
                'sample'    => 'v=DMARC1; p=quarantine; rua=mailto:postmaster@' . $host,
                'hint'      => 'Recommended, not required to send. Start with p=quarantine (or p=none while you observe reports) once SPF and DKIM are both green.',
            ],
        ];
    }

    /** Run live DNS lookups and update verification flags. Returns the refreshed row. */
    public static function verify(array $domain): array
    {
        $host = $domain['domain'];

        $spfOk = self::hasTxtStartingWith($host, 'v=spf1');

        $dkimHost = $domain['dkim_selector'] . '._domainkey.' . $host;
        $dkimOk = self::txtRecordsFor($dkimHost) !== [] && self::dkimMatches($dkimHost, $domain['dkim_public_key']);

        $dmarcOk = self::hasTxtStartingWith('_dmarc.' . $host, 'v=DMARC1');

        $isVerified = $spfOk && $dkimOk;

        self::update((int) $domain['id'], [
            'spf_verified'    => $spfOk ? 1 : 0,
            'dkim_verified'   => $dkimOk ? 1 : 0,
            'dmarc_verified'  => $dmarcOk ? 1 : 0,
            'is_verified'     => $isVerified ? 1 : 0,
            'last_checked_at' => date('Y-m-d H:i:s'),
        ]);

        return array_merge($domain, [
            'spf_verified' => (int) $spfOk, 'dkim_verified' => (int) $dkimOk,
            'dmarc_verified' => (int) $dmarcOk, 'is_verified' => (int) $isVerified,
        ]);
    }

    /** @return array<int,string> concatenated TXT record values for a hostname. */
    private static function txtRecordsFor(string $host): array
    {
        $records = @dns_get_record(rtrim($host, '.') . '.', DNS_TXT);
        if ($records === false) {
            return [];
        }
        $out = [];
        foreach ($records as $r) {
            // Split TXT strings are exposed as entries* / txt; concatenate defensively.
            $out[] = $r['txt'] ?? implode('', $r['entries'] ?? []);
        }
        return $out;
    }

    private static function hasTxtStartingWith(string $host, string $prefix): bool
    {
        foreach (self::txtRecordsFor($host) as $txt) {
            if (stripos(trim($txt), $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    private static function dkimMatches(string $host, string $expectedPublicKey): bool
    {
        $expected = preg_replace('/\s+/', '', $expectedPublicKey);
        foreach (self::txtRecordsFor($host) as $txt) {
            if (stripos($txt, 'v=DKIM1') === false && stripos($txt, 'p=') === false) {
                continue;
            }
            if (preg_match('/p=([^;]+)/i', $txt, $m)) {
                $published = preg_replace('/\s+/', '', $m[1]);
                if ($published !== '' && $published === $expected) {
                    return true;
                }
            }
        }
        return false;
    }
}
