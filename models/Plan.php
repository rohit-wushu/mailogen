<?php

declare(strict_types=1);

final class Plan extends Model
{
    protected static string $table = 'plans';

    /** @var array<int,?array> per-request cache — plans don't change mid-request. */
    private static array $findCache = [];

    private const DEFAULT_SMTP_COMPARE = "Use your existing SMTP or ESP (Gmail, Brevo, Mailgun, SendGrid...)\nYou manage your own sender reputation & deliverability\nConnect and start sending in minutes\n-We don't monitor bounces/complaints on your behalf\nCosts us nothing to run, so it's priced lower";

    private const DEFAULT_DOMAIN_COMPARE = "We send on your verified domain via our infrastructure\nWe monitor bounce & complaint rates for you\nHigher inbox placement out of the box\nDomain (SPF/DKIM) setup guidance included\nReal delivery costs apply, so it's priced higher";

    /** Cached single-plan lookup (hot via Billing::effectivePlan). */
    public static function find(int $id): ?array
    {
        if (!array_key_exists($id, self::$findCache)) {
            self::$findCache[$id] = parent::find($id);
        }
        return self::$findCache[$id];
    }

    /** All plans in display order (admin sort, then price). */
    public static function all(): array
    {
        return db()->query('SELECT * FROM plans ORDER BY sort_order ASC, price_smtp ASC')->fetchAll();
    }

    /** Only active plans — for the public/user pricing panel. */
    public static function active(): array
    {
        return db()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, price_smtp ASC')->fetchAll();
    }

    /**
     * A plan's price for a given sending mode. Domain/SES sending costs the
     * platform real Amazon SES fees, so it's priced above BYO-SMTP, which
     * costs the platform nothing.
     */
    public static function priceFor(array $plan, string $mode): float
    {
        return $mode === 'domain' ? (float) $plan['price_domain'] : (float) $plan['price_smtp'];
    }

    /**
     * Parse a plan's feature text into render-ready rows. Each line is one
     * feature; a leading "-" (or "~") marks it as NOT included (shown struck
     * through). Returns [['label'=>string,'included'=>bool], ...].
     */
    public static function featureList(array $plan): array
    {
        $raw = trim((string) ($plan['features'] ?? ''));
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $excluded = ($line[0] === '-' || $line[0] === '~');
            $out[] = [
                'label'    => trim(ltrim($line, '-~ ')),
                'included' => !$excluded,
            ];
        }
        return $out;
    }

    /** Admin-editable raw text for the SMTP vs domain sending-mode comparison (register / settings info modal). */
    public static function modeCompareRaw(string $mode): string
    {
        $key     = $mode === 'domain' ? 'mode_compare_domain' : 'mode_compare_smtp';
        $default = $mode === 'domain' ? self::DEFAULT_DOMAIN_COMPARE : self::DEFAULT_SMTP_COMPARE;
        $stored  = trim((string) Setting::get($key, ''));
        return $stored !== '' ? $stored : $default;
    }

    /** Same content as {@see modeCompareRaw()}, parsed into render-ready feature rows. */
    public static function modeCompareFeatures(string $mode): array
    {
        return self::featureList(['features' => self::modeCompareRaw($mode)]);
    }
}
