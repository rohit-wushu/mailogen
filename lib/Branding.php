<?php
/**
 * "Powered by" branding footer appended to emails sent by users on the FREE
 * plan. The text, logo and link are configured by the admin (app_settings);
 * paid plans get no branding.
 *
 * A locally-uploaded logo is EMBEDDED into the email as a CID attachment so it
 * displays in any inbox without needing a public URL. A logo given as an
 * absolute http(s) URL is referenced directly. The on-page preview always uses
 * the URL form (CID only renders inside email clients).
 */

declare(strict_types=1);

final class Branding
{
    public const LOGO_CID = 'brandlogo';

    /** A user is "free" when their effective plan costs nothing (or has lapsed). */
    public static function isFreeUser(array $user): bool
    {
        return (float) Billing::effectivePlan($user)['price_monthly'] <= 0;
    }

    /**
     * Branding for the on-page / browser preview (URL-based image). Returns ''
     * for paid users.
     */
    public static function footerFor(int $userId): string
    {
        $user = User::find($userId);
        if (!$user || !self::isFreeUser($user)) {
            return '';
        }
        return self::render(self::logoUrl());
    }

    /**
     * Branding for an actual email send. A local logo is embedded via CID so it
     * always displays; a remote URL is referenced as-is.
     *
     * @return array{html:string, image:?array{cid:string,path:string,mime:string}}
     */
    /** @var array<int,array{html:string,image:?array}> per-request memo (hot in the send loop). */
    private static array $emailCache = [];

    public static function forEmail(int $userId): array
    {
        if (isset(self::$emailCache[$userId])) {
            return self::$emailCache[$userId];
        }
        $user = User::find($userId);
        if (!$user || !self::isFreeUser($user)) {
            return self::$emailCache[$userId] = ['html' => '', 'image' => null];
        }

        $logo = trim((string) Setting::get('branding_logo', ''));
        if ($logo !== '' && !preg_match('#^https?://#i', $logo)) {
            $path = BASE_PATH . '/public/' . ltrim($logo, '/');
            if (is_file($path)) {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'image/png';
                return self::$emailCache[$userId] = [
                    'html'  => self::render('cid:' . self::LOGO_CID),
                    'image' => ['cid' => self::LOGO_CID, 'path' => $path, 'mime' => $mime],
                ];
            }
        }
        // Remote URL (or no logo) — reference directly, nothing to embed.
        return self::$emailCache[$userId] = ['html' => self::render(self::logoUrl()), 'image' => null];
    }

    /** Resolve the configured logo to an absolute URL ('' when unset). */
    private static function logoUrl(): string
    {
        $logo = trim((string) Setting::get('branding_logo', ''));
        if ($logo === '') {
            return '';
        }
        return preg_match('#^https?://#i', $logo) ? $logo : url($logo);
    }

    /** Render the footer block with a given <img> src (URL or cid:). '' if empty. */
    private static function render(string $logoSrc): string
    {
        $text = trim((string) Setting::get('branding_text', ''));
        $link = trim((string) Setting::get('branding_link', ''));
        if ($text === '' && $logoSrc === '') {
            return '';
        }

        $inner = '';
        if ($logoSrc !== '') {
            $inner .= '<img src="' . e($logoSrc) . '" alt="" height="22" style="height:22px;width:auto;vertical-align:middle;border:0">';
        }
        if ($text !== '') {
            $inner .= ($logoSrc !== '' ? '<span style="margin-left:8px;vertical-align:middle">' . e($text) . '</span>' : e($text));
        }
        if ($link !== '') {
            $href = preg_match('#^https?://#i', $link) ? $link : 'https://' . $link;
            $inner = '<a href="' . e($href) . '" style="color:#9aa0ad;text-decoration:none" target="_blank">' . $inner . '</a>';
        }

        return '<div style="margin-top:10px;font:12px Arial,sans-serif;color:#9aa0ad;text-align:center;line-height:1.6">'
            . $inner . '</div>';
    }
}
