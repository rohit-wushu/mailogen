<?php
/**
 * Lightweight pre-send quality / spam checklist for a campaign. Returns a list
 * of findings ['level' => 'error|warn|ok', 'msg' => ...] the UI can show before
 * launch. Heuristic only — not a guarantee of inbox placement.
 */

declare(strict_types=1);

final class SpamCheck
{
    private const SPAM_WORDS = [
        'free', 'guarantee', 'guaranteed', 'winner', 'congratulations', 'click here',
        'buy now', 'act now', 'limited time', 'urgent', 'cash', 'cheap', '100%',
        'risk-free', 'no obligation', 'increase sales', 'earn money', 'double your',
        'viagra', 'lottery', 'prize', 'income',
    ];

    /** @return array<int,array{level:string,msg:string}> */
    public static function analyze(array $campaign, array $org = []): array
    {
        $out     = [];
        $subject = trim((string) ($campaign['subject'] ?? ''));
        $html    = (string) ($campaign['body_html'] ?? '');
        $text    = trim(strip_tags($html));

        // Subject line
        if ($subject === '') {
            $out[] = ['level' => 'error', 'msg' => 'No subject line set.'];
        } else {
            if (mb_strlen($subject) > 70) {
                $out[] = ['level' => 'warn', 'msg' => 'Subject is long (' . mb_strlen($subject) . ' chars) — it may be truncated on mobile.'];
            }
            if ($subject === mb_strtoupper($subject) && preg_match('/[A-Z]/', $subject)) {
                $out[] = ['level' => 'warn', 'msg' => 'Subject is ALL CAPS — a common spam trigger.'];
            }
            if (preg_match_all('/[!?]/', $subject) > 2) {
                $out[] = ['level' => 'warn', 'msg' => 'Subject has lots of !/? punctuation.'];
            }
            if (substr_count($subject, '$') > 0 || preg_match('/\b(free|winner|congratulations)\b/i', $subject)) {
                $out[] = ['level' => 'warn', 'msg' => 'Subject contains spam-trigger words ($, "free", "winner"…).'];
            }
        }

        // Body presence
        if ($text === '') {
            $out[] = ['level' => 'error', 'msg' => 'The email has no content.'];
            return $out;
        }

        // Text-to-image balance
        $imgCount  = preg_match_all('/<img\b/i', $html);
        $textLen   = mb_strlen($text);
        if ($imgCount > 0 && $textLen < 80) {
            $out[] = ['level' => 'warn', 'msg' => 'This is mostly images with little text — image-only emails often land in spam. Add real text.'];
        }
        if ($imgCount === 0 && $textLen < 40) {
            $out[] = ['level' => 'warn', 'msg' => 'Very little content — short, sparse emails look spammy.'];
        }

        // Links
        $linkCount = preg_match_all('/<a\b[^>]*href=/i', $html);
        if ($linkCount > 25) {
            $out[] = ['level' => 'warn', 'msg' => "Lots of links ({$linkCount}) — too many hurts deliverability."];
        }

        // Spam words in body
        $hits = [];
        $lower = mb_strtolower($text);
        foreach (self::SPAM_WORDS as $w) {
            if (str_contains($lower, $w)) {
                $hits[] = $w;
            }
        }
        if (count($hits) >= 4) {
            $out[] = ['level' => 'warn', 'msg' => 'Several spam-trigger phrases found: ' . implode(', ', array_slice($hits, 0, 6)) . '.'];
        }

        // Personalisation hint
        if (!str_contains($html, '{{') && !str_contains($subject, '{{')) {
            $out[] = ['level' => 'ok', 'msg' => 'Tip: add a merge tag like {{first_name}} to personalise and boost engagement.'];
        }

        // Compliance: physical address required by CAN-SPAM
        if (trim((string) ($org['address'] ?? '')) === '') {
            $out[] = ['level' => 'error', 'msg' => 'No physical mailing address set — legally required on bulk email. Add it in Settings → Profile.'];
        }

        if ($out === [] || !self::hasProblem($out)) {
            array_unshift($out, ['level' => 'ok', 'msg' => 'Looks good — no major deliverability issues detected.']);
        }
        return $out;
    }

    public static function hasProblem(array $findings): bool
    {
        foreach ($findings as $f) {
            if ($f['level'] === 'error' || $f['level'] === 'warn') {
                return true;
            }
        }
        return false;
    }
}
