<?php
/**
 * Very small user-agent parser for device + browser labels.
 * Good enough for engagement analytics without a third-party DB.
 */

declare(strict_types=1);

final class UserAgent
{
    public static function device(string $ua): string
    {
        $ua = strtolower($ua);
        if (preg_match('/ipad|tablet|playbook|silk/', $ua)) {
            return 'Tablet';
        }
        if (preg_match('/mobi|iphone|android.*mobile|windows phone/', $ua)) {
            return 'Mobile';
        }
        if (preg_match('/bot|crawl|spider|preview|proxy|google-/', $ua)) {
            return 'Bot/Proxy';
        }
        return 'Desktop';
    }

    public static function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg')                      => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome')                   => 'Chrome',
            str_contains($ua, 'Firefox')                  => 'Firefox',
            str_contains($ua, 'Safari')                   => 'Safari',
            str_contains($ua, 'Outlook') || str_contains($ua, 'MSIE') => 'Outlook/IE',
            default                                       => 'Other',
        };
    }

    /** Best-effort metadata bundle for an inbound tracking request. */
    public static function meta(): array
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return [
            'ip'         => self::clientIp(),
            'user_agent' => mb_substr($ua, 0, 255),
            'device'     => self::device($ua),
            'browser'    => self::browser($ua),
            'country'    => self::country(),
        ];
    }

    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

    /**
     * Country is best resolved by a CDN header (Cloudflare's CF-IPCountry) on
     * shared hosting where no GeoIP database is available.
     */
    public static function country(): ?string
    {
        return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
    }
}
