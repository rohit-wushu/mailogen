<?php
/**
 * Google Sign-In (OAuth 2.0 authorization-code flow), dependency-free (cURL).
 * No id_token/JWT signature verification is done here — instead we call
 * Google's userinfo endpoint with the freshly-obtained access token, which
 * gives an equally trustworthy (live, server-verified) profile without
 * needing to implement JWT/JWK verification by hand.
 */

declare(strict_types=1);

final class GoogleAuth
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public static function isConfigured(): bool
    {
        return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
    }

    public static function redirectUri(): string
    {
        return url('auth/google/callback');
    }

    public static function authUrl(string $state): string
    {
        $params = [
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ];
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange an authorization code for a verified profile.
     * @return array{sub:string,email:string,email_verified:bool,name:string}|null
     */
    public static function fetchProfile(string $code): ?array
    {
        $token = self::postJson(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
        ]);
        $accessToken = $token['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === '') {
            return null;
        }

        $ch = curl_init(self::USERINFO_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $res = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        if (!$ok || !is_string($res)) {
            return null;
        }

        $profile = json_decode($res, true);
        if (!is_array($profile) || empty($profile['sub']) || empty($profile['email'])) {
            return null;
        }

        return [
            'sub'            => (string) $profile['sub'],
            'email'          => strtolower(trim((string) $profile['email'])),
            'email_verified' => filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name'           => trim((string) ($profile['name'] ?? '')),
        ];
    }

    private static function postJson(string $url, array $fields): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_TIMEOUT        => 10,
        ]);
        $res = curl_exec($ch);
        $ok  = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        if (!$ok || !is_string($res)) {
            return null;
        }
        $data = json_decode($res, true);
        return is_array($data) ? $data : null;
    }
}
