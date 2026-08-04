<?php
/**
 * Admin-configurable site identity (name, logo, favicon, meta tags) backed by
 * the app_settings store. Every getter falls back to a sensible default so the
 * app works out of the box before anything is configured.
 */

declare(strict_types=1);

final class Site
{
    /** A stored setting, or $default when it's missing/blank. */
    private static function val(string $key, string $default = ''): string
    {
        $v = trim((string) (Setting::get($key, '') ?? ''));
        return $v !== '' ? $v : $default;
    }

    /** Resolve a stored path/URL setting to an absolute URL ('' when unset). */
    private static function asset(string $key, string $default = ''): string
    {
        $v = self::val($key, $default);
        if ($v === '') {
            return '';
        }
        return preg_match('#^https?://#i', $v) ? $v : url($v);
    }

    public static function name(): string
    {
        return self::val('site_name', APP_NAME);
    }

    /** How the brand is displayed: 'both' | 'logo' | 'title'. */
    public static function brandMode(): string
    {
        $m = self::val('brand_display', 'both');
        return in_array($m, ['both', 'logo', 'title'], true) ? $m : 'both';
    }

    /** Whether to render the logo/icon mark (true unless title-only). */
    public static function showMark(): bool
    {
        return self::brandMode() !== 'title';
    }

    /** Whether to render the site-name text (true unless logo-only). */
    public static function showName(): bool
    {
        return self::brandMode() !== 'logo';
    }

    /** Site logo URL for the sidebar/header ('' → fall back to the icon mark). */
    public static function logoUrl(): string
    {
        return self::asset('site_logo');
    }

    public static function faviconUrl(): string
    {
        return self::asset('site_favicon', 'assets/favicon.svg');
    }

    public static function metaTitle(): string
    {
        return self::val('meta_title', self::name());
    }

    public static function metaDescription(): string
    {
        return self::val('meta_description');
    }

    public static function metaKeywords(): string
    {
        return self::val('meta_keywords');
    }
}
