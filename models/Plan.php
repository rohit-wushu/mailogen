<?php

declare(strict_types=1);

final class Plan extends Model
{
    protected static string $table = 'plans';

    /** @var array<int,?array> per-request cache — plans don't change mid-request. */
    private static array $findCache = [];

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
        return db()->query('SELECT * FROM plans ORDER BY sort_order ASC, price_monthly ASC')->fetchAll();
    }

    /** Only active plans — for the public/user pricing panel. */
    public static function active(): array
    {
        return db()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, price_monthly ASC')->fetchAll();
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
}
