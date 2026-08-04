<?php
/**
 * Comprehensive, dependency-free email verifier (list-cleaning) aiming for the
 * highest practical accuracy without a paid API. Layers every signal a service
 * like Gamalogic/ZeroBounce uses:
 *
 *   • RFC syntax + length limits + structural rules
 *   • Typo / "did you mean" detection on popular domains
 *   • Disposable / throwaway domain detection (curated list)
 *   • Role-account detection (info@, admin@ …)
 *   • Free-provider classification (gmail, yahoo …)
 *   • Gibberish / random local-part heuristic
 *   • DNS: MX (with A fallback) reachability
 *   • SMTP: mailbox RCPT probe + catch-all detection + greylist retry
 *
 * Returns a rich result with an overall status, a 0-100 confidence score and a
 * per-check breakdown the UI can render.
 *
 * Hard truth: confirming a specific mailbox at Gmail/Outlook needs an SMTP RCPT
 * probe over port 25 (often blocked on shared hosting) — where blocked we
 * degrade gracefully to domain-level confidence and say so.
 */

declare(strict_types=1);

final class EmailVerifier
{
    private const ROLE = [
        'admin', 'administrator', 'info', 'support', 'sales', 'contact', 'help', 'office',
        'billing', 'webmaster', 'postmaster', 'hostmaster', 'abuse', 'noreply', 'no-reply',
        'noc', 'security', 'marketing', 'team', 'hello', 'enquiry', 'enquiries', 'service',
        'careers', 'jobs', 'hr', 'accounts', 'accounting', 'feedback', 'mail', 'spam',
    ];

    private const FREE = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.in', 'yahoo.co.uk', 'ymail.com',
        'hotmail.com', 'hotmail.co.uk', 'outlook.com', 'live.com', 'msn.com', 'icloud.com',
        'me.com', 'mac.com', 'aol.com', 'protonmail.com', 'proton.me', 'zoho.com', 'zohomail.com',
        'gmx.com', 'gmx.net', 'mail.com', 'yandex.com', 'yandex.ru', 'tutanota.com', 'rediffmail.com',
    ];

    /**
     * Distinctive brand cores of major mailbox providers → canonical domain.
     * Used to catch look-alike / typo-squat domains (e.g. 12gmail.com,
     * gmail123.net, gmail.in) that REGISTER and resolve in DNS but are never the
     * address the sender intended. Only distinctive cores are listed — generic
     * ones like "mail", "live", "me", "aol" would false-positive.
     */
    private const BRANDS = [
        'gmail'      => 'gmail.com',
        'googlemail' => 'googlemail.com',
        'yahoo'      => 'yahoo.com',
        'ymail'      => 'ymail.com',
        'hotmail'    => 'hotmail.com',
        'outlook'    => 'outlook.com',
        'icloud'     => 'icloud.com',
        'protonmail' => 'protonmail.com',
        'rediffmail' => 'rediffmail.com',
        'yandex'     => 'yandex.com',
    ];

    /** Popular domains used for typo "did you mean" suggestions. */
    private const POPULAR = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.in', 'yahoo.co.uk', 'ymail.com',
        'hotmail.com', 'hotmail.co.uk', 'outlook.com', 'live.com', 'msn.com', 'icloud.com',
        'me.com', 'aol.com', 'protonmail.com', 'proton.me', 'zoho.com', 'gmx.com', 'mail.com',
        'yandex.com', 'rediffmail.com',
    ];

    /** Explicit common misspellings (incl. typo-squats that resolve). */
    private const COMMON_TYPOS = [
        'gmial.com' => 'gmail.com', 'gmai.com' => 'gmail.com', 'gmal.com' => 'gmail.com',
        'gmail.co' => 'gmail.com', 'gmail.cm' => 'gmail.com', 'gmail.con' => 'gmail.com',
        'gmail.comm' => 'gmail.com', 'gnail.com' => 'gmail.com', 'gmali.com' => 'gmail.com',
        'gmaill.com' => 'gmail.com', ' gmail.com' => 'gmail.com', 'gmaul.com' => 'gmail.com',
        'gmsil.com' => 'gmail.com', 'gmail.org' => 'gmail.com', 'googlemail.co' => 'googlemail.com',
        'hotmial.com' => 'hotmail.com', 'hotmal.com' => 'hotmail.com', 'hotmil.com' => 'hotmail.com',
        'hotmail.co' => 'hotmail.com', 'hotmail.cm' => 'hotmail.com', 'hotmail.con' => 'hotmail.com',
        'hotmaill.com' => 'hotmail.com', 'hotnail.com' => 'hotmail.com', 'homail.com' => 'hotmail.com',
        'yahooo.com' => 'yahoo.com', 'yaho.com' => 'yahoo.com', 'yahoo.co' => 'yahoo.com',
        'yahoo.cm' => 'yahoo.com', 'yahoo.con' => 'yahoo.com', 'yhaoo.com' => 'yahoo.com',
        'yahho.com' => 'yahoo.com', 'ymail.co' => 'ymail.com',
        'outlok.com' => 'outlook.com', 'outloo.com' => 'outlook.com', 'outlook.co' => 'outlook.com',
        'outlook.cm' => 'outlook.com', 'outlook.con' => 'outlook.com', 'oulook.com' => 'outlook.com',
        'hotmailcom' => 'hotmail.com', 'iclod.com' => 'icloud.com', 'icloud.co' => 'icloud.com',
        'icloud.con' => 'icloud.com', 'iclould.com' => 'icloud.com', 'live.co' => 'live.com',
        'rediffmail.co' => 'rediffmail.com', 'rediff.com' => 'rediffmail.com',
    ];

    public static function disposable(): array
    {
        static $d = null;
        if ($d === null) {
            $d = @require __DIR__ . '/data/disposable_domains.php';
            if (!is_array($d)) {
                $d = [];
            }
        }
        return $d;
    }

    /**
     * Full verification. $deep enables the SMTP mailbox probe.
     * @return array{status:string,score:int,reason:string,email:string,
     *               did_you_mean:?string,flags:array,checks:array}
     */
    public static function verify(string $email, bool $deep = false): array
    {
        $email = strtolower(trim($email));
        $checks = [];
        $flags  = ['free' => false, 'role' => false, 'disposable' => false, 'catchall' => false, 'gibberish' => false, 'smtp_checked' => false];

        $add = static function (string $key, string $label, string $state, string $detail) use (&$checks): void {
            // state: pass | fail | warn | skip
            $checks[] = ['key' => $key, 'label' => $label, 'state' => $state, 'detail' => $detail];
        };

        // ---- 1. Syntax & structure ------------------------------------
        $syntax = self::syntaxError($email);
        if ($syntax !== null) {
            $add('syntax', 'Syntax', 'fail', $syntax);
            return self::result('invalid', 0, $syntax, $email, null, $flags, $checks);
        }
        $add('syntax', 'Syntax', 'pass', 'valid format');
        [$local, $domain] = explode('@', $email, 2);

        // ---- 2. Typo / did-you-mean -----------------------------------
        $suggestion = self::suggestDomain($domain);
        // Hard-fail an obvious misspelling (curated map) or a brand look-alike /
        // typo-squat (12gmail.com, gmail123.net, gmail.in) — these resolve in DNS
        // but are never the intended provider, so the MX check alone is not enough.
        $lookAlike = isset(self::COMMON_TYPOS[$domain]) || self::brandImpersonation($domain) !== null;
        if ($suggestion !== null && $lookAlike) {
            $add('typo', 'Domain spelling', 'fail', "look-alike / typo domain — did you mean {$suggestion}?");
            return self::result('invalid', 8, "look-alike or misspelled domain (did you mean {$suggestion}?)", $email, $suggestion, $flags, $checks);
        }
        if ($suggestion !== null) {
            $add('typo', 'Domain spelling', 'warn', "possible typo — did you mean {$suggestion}?");
        }

        // ---- 3. Disposable --------------------------------------------
        if (isset(self::disposable()[$domain])) {
            $flags['disposable'] = true;
            $add('disposable', 'Disposable', 'fail', 'throwaway / temp-mail domain');
            return self::result('invalid', 5, 'disposable domain', $email, $suggestion, $flags, $checks);
        }
        $add('disposable', 'Disposable', 'pass', 'not a temp-mail domain');

        // ---- 4. Role & free classification ----------------------------
        $flags['role'] = in_array($local, self::ROLE, true);
        $flags['free'] = in_array($domain, self::FREE, true);
        $add('role', 'Role account', $flags['role'] ? 'warn' : 'pass', $flags['role'] ? "shared inbox ({$local}@)" : 'personal mailbox');

        // ---- 5. Gibberish heuristic -----------------------------------
        $flags['gibberish'] = self::looksGibberish($local);
        if ($flags['gibberish']) {
            $add('quality', 'Local-part quality', 'warn', 'looks random / low quality');
        } else {
            $add('quality', 'Local-part quality', 'pass', 'looks human');
        }

        // ---- 6. DNS / MX ----------------------------------------------
        $mx = [];
        $hasMx = getmxrr($domain, $mx) && $mx !== [];
        $hasA  = $hasMx ? true : checkdnsrr($domain, 'A');
        if (!$hasMx && !$hasA) {
            $add('mx', 'Domain & MX', 'fail', 'no mail server for this domain');
            $reason = $suggestion ? "domain has no mail server — did you mean {$suggestion}?" : 'no mail server (MX) for domain';
            return self::result('invalid', $suggestion ? 10 : 5, $reason, $email, $suggestion, $flags, $checks);
        }
        $add('mx', 'Domain & MX', $hasMx ? 'pass' : 'warn', $hasMx ? count($mx) . ' MX record(s)' : 'no MX, falls back to A record');

        // ---- 7. SMTP mailbox probe (deep) -----------------------------
        $score = 70;                          // domain-verified baseline
        $status = 'valid';
        $reason = 'domain accepts mail (mailbox not checked)';

        if ($deep && $hasMx) {
            $probe = self::smtpProbe($mx, $email, $domain);
            $flags['smtp_checked'] = $probe['code'] !== 'unknown';
            $flags['catchall'] = $probe['catchall'];

            switch ($probe['code']) {
                case 'missing':
                    $add('smtp', 'Mailbox (SMTP)', 'fail', 'server says this mailbox does not exist');
                    return self::result('invalid', 2, 'mailbox does not exist', $email, $suggestion, $flags, $checks);
                case 'exists':
                    $add('smtp', 'Mailbox (SMTP)', 'pass', 'server confirms the mailbox exists');
                    $score = 98; $reason = 'mailbox exists';
                    break;
                case 'catchall':
                    $add('smtp', 'Mailbox (SMTP)', 'warn', 'catch-all domain — accepts every address');
                    $status = 'risky'; $score = 55; $reason = 'catch-all domain (mailbox not confirmable)';
                    break;
                case 'full':
                    $add('smtp', 'Mailbox (SMTP)', 'warn', 'mailbox full / temporarily unavailable');
                    $status = 'risky'; $score = 60; $reason = 'mailbox full or deferred';
                    break;
                default:
                    $add('smtp', 'Mailbox (SMTP)', 'skip', 'could not probe (port 25 blocked or greylisted)');
            }
        } elseif ($deep) {
            $add('smtp', 'Mailbox (SMTP)', 'skip', 'no MX to probe');
        } else {
            $add('smtp', 'Mailbox (SMTP)', 'skip', 'deep check off (domain-level only)');
        }

        // ---- 8. Aggregate downgrades ----------------------------------
        if ($status === 'valid') {
            if ($flags['role']) { $status = 'risky'; $score = min($score, 60); $reason = 'role address — ' . $reason; }
            if ($flags['gibberish']) { $status = 'risky'; $score = min($score, 50); $reason = 'random-looking local part'; }
        }

        return self::result($status, $score, $reason, $email, $suggestion, $flags, $checks);
    }

    // ---- syntax ------------------------------------------------------
    private static function syntaxError(string $email): ?string
    {
        if ($email === '' || strpos($email, '@') === false) {
            return 'missing @ / empty';
        }
        if (strlen($email) > 254) {
            return 'address too long';
        }
        [$local, $domain] = explode('@', $email, 2) + ['', ''];
        if ($local === '' || $domain === '') {
            return 'empty local or domain part';
        }
        if (strlen($local) > 64) {
            return 'local part too long';
        }
        if (str_contains($local, '..') || $local[0] === '.' || substr($local, -1) === '.') {
            return 'misplaced dots in local part';
        }
        if (!str_contains($domain, '.') || str_contains($domain, '..')) {
            return 'invalid domain';
        }
        $tld = substr(strrchr($domain, '.'), 1);
        if (!preg_match('/^[a-z]{2,}$/i', $tld)) {
            return 'invalid TLD';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid format';
        }
        return null;
    }

    // ---- typo suggestion --------------------------------------------
    private static function suggestDomain(string $domain): ?string
    {
        if (in_array($domain, self::POPULAR, true) || isset(self::disposable()[$domain])) {
            return null;
        }
        // 1. Explicit, curated typo map (catches typo-squats that resolve).
        if (isset(self::COMMON_TYPOS[$domain])) {
            return self::COMMON_TYPOS[$domain];
        }
        // 2. Brand look-alike / typo-squat — flagged even if it resolves, since
        //    squats are registered on purpose (e.g. 12gmail.com, gmail123.net).
        $brand = self::brandImpersonation($domain);
        if ($brand !== null) {
            return $brand;
        }
        // 3. Fuzzy match — but ONLY when the typed domain doesn't resolve, so we
        //    never "correct" a legitimate domain (e.g. email.com is real).
        if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')) {
            return null;
        }
        $best = null;
        $bestDist = 99;
        foreach (self::POPULAR as $p) {
            $d = levenshtein($domain, $p);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $p;
            }
        }
        return ($best !== null && $bestDist >= 1 && $bestDist <= 2) ? $best : null;
    }

    /**
     * Detect a brand look-alike / typo-squat domain and return the canonical
     * provider it imitates (or null). Catches the dominant squat patterns:
     *   • correct brand label, wrong TLD   — gmail.in, yahoo.xyz, outlook.net
     *   • brand with digits/separators glued on — 12gmail.com, gmail123.net, g-mail style
     * Deliberately high-precision: the noise around the brand must be only
     * digits / separators, so real words like "bigmail" or "rocketmail" pass.
     */
    private static function brandImpersonation(string $domain): ?string
    {
        // Genuine providers never impersonate themselves.
        if (in_array($domain, self::POPULAR, true) || in_array($domain, self::FREE, true)) {
            return null;
        }
        $sld = explode('.', $domain, 2)[0];        // label before the first dot
        foreach (self::BRANDS as $core => $canon) {
            // Exact brand label but a different (wrong) TLD → squat.
            if ($sld === $core) {
                return $domain === $canon ? null : $canon;
            }
            // Brand embedded with only digits/separators as noise → squat.
            if (str_contains($sld, $core)) {
                $noise = str_replace($core, '', $sld);
                if ($noise !== '' && preg_match('/^[0-9._\-]+$/', $noise)) {
                    return $canon;
                }
            }
        }
        return null;
    }

    // ---- gibberish heuristic ----------------------------------------
    private static function looksGibberish(string $local): bool
    {
        $core = preg_replace('/[._+\-]/', '', $local);
        $len = strlen($core);
        if ($len < 8) {
            return false;                       // short names are fine
        }
        // No vowels at all in a long string → likely random.
        if (!preg_match('/[aeiou]/i', $core)) {
            return true;
        }
        // Long run of consonants.
        if (preg_match('/[bcdfghjklmnpqrstvwxz]{6,}/i', $core)) {
            return true;
        }
        // Mostly digits.
        $digits = preg_match_all('/\d/', $core);
        if ($len >= 10 && $digits / $len > 0.6) {
            return true;
        }
        return false;
    }

    /**
     * SMTP probe with catch-all detection + a single greylist retry.
     * @return array{code:string,catchall:bool}  code: exists|missing|catchall|full|unknown
     */
    private static function smtpProbe(array $mx, string $email, string $domain): array
    {
        $code = self::smtpSession($mx, $email, $domain);
        if ($code === 'greylist') {
            usleep(400000);                       // brief pause, retry once
            $code = self::smtpSession($mx, $email, $domain);
        }
        return [
            'code'     => in_array($code, ['exists', 'missing', 'catchall', 'full'], true) ? $code : 'unknown',
            'catchall' => $code === 'catchall',
        ];
    }

    /** One SMTP conversation. Returns exists|missing|catchall|full|greylist|unknown. */
    private static function smtpSession(array $mx, string $email, string $domain): string
    {
        $fp = @fsockopen($mx[0], 25, $errno, $errstr, 7);
        if (!$fp) {
            return 'unknown';                     // port 25 blocked
        }
        stream_set_timeout($fp, 7);

        $reply = static function () use ($fp): int {
            $code = 0;
            while (($line = fgets($fp, 515)) !== false) {
                $code = (int) substr($line, 0, 3);
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $code;
        };
        $cmd = static function (string $c) use ($fp, $reply): int {
            fwrite($fp, $c . "\r\n");
            return $reply();
        };

        $reply();                                 // banner
        $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'verify.local';
        if ($cmd('EHLO ' . $host) >= 500) {
            $cmd('HELO ' . $host);
        }
        $cmd('MAIL FROM:<verify@' . $host . '>');

        $rand = 'no-such-' . substr(md5($email . microtime()), 0, 12) . '@' . $domain;
        $randCode = $cmd('RCPT TO:<' . $rand . '>');
        $realCode = $cmd('RCPT TO:<' . $email . '>');
        $cmd('QUIT');
        fclose($fp);

        $accepts = static fn (int $c): bool => $c === 250 || $c === 251;

        if ($accepts($randCode)) {
            return 'catchall';
        }
        if ($realCode === 452 || $realCode === 552) {
            return 'full';
        }
        if ($realCode >= 500 && $realCode < 560) {
            return 'missing';
        }
        if ($accepts($realCode)) {
            return 'exists';
        }
        if ($realCode >= 400 && $realCode < 500) {
            return 'greylist';
        }
        return 'unknown';
    }

    private static function result(string $status, int $score, string $reason, string $email, ?string $sugg, array $flags, array $checks): array
    {
        return [
            'status'       => $status,
            'score'        => $score,
            'reason'       => $reason,
            'email'        => $email,
            'did_you_mean' => $sugg,
            'flags'        => $flags,
            'checks'       => $checks,
        ];
    }

    public static function color(string $status): string
    {
        return [
            'valid' => 'success', 'invalid' => 'danger', 'risky' => 'warning',
            'unknown' => 'secondary', 'unverified' => 'light',
        ][$status] ?? 'secondary';
    }
}
