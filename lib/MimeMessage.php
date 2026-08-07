<?php
/**
 * RFC 5322 message construction, shared by every outbound transport
 * (raw SMTP socket, Amazon SES API) so DKIM signing and MIME structure
 * are identical no matter how the bytes actually leave the server.
 */

declare(strict_types=1);

final class MimeMessage
{
    /**
     * Build a message and, if $dkim is given, prepend its DKIM-Signature
     * header (RFC 6376 relaxed/relaxed, rsa-sha256).
     *
     * @param array{email:string,name?:string} $from
     * @param array<int,string>                $to
     * @param array<string,string>              $headers
     * @param array<int,array{cid:string,path:string,mime?:string}> $inlineImages
     * @param ?array{domain:string,selector:string,privateKeyPem:string} $dkim
     */
    public static function buildSigned(
        array $from,
        array $to,
        string $subject,
        string $htmlBody,
        array $headers = [],
        array $inlineImages = [],
        ?array $dkim = null,
        ?string $hostForMessageId = null
    ): string {
        $message = self::build($from, $to, $subject, $htmlBody, $headers, $inlineImages, $hostForMessageId);
        if ($dkim !== null) {
            $sig = Dkim::sign($message, $dkim['domain'], $dkim['selector'], $dkim['privateKeyPem']);
            $message = $sig . "\r\n" . $message;
        }
        return $message;
    }

    /** @see buildSigned() — same params, minus DKIM. */
    public static function build(
        array $from,
        array $to,
        string $subject,
        string $htmlBody,
        array $headers = [],
        array $inlineImages = [],
        ?string $hostForMessageId = null
    ): string {
        $fromName = self::encodeHeader($from['name'] ?? '');
        $date     = date('r');
        $altBound = 'alt_' . bin2hex(random_bytes(8));
        $msgIdHost = $hostForMessageId ?: (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost');

        $lines   = [];
        $lines[] = 'Date: ' . $date;
        $lines[] = 'From: ' . ($fromName ? $fromName . ' ' : '') . '<' . $from['email'] . '>';
        $lines[] = 'To: ' . implode(', ', array_map(static fn ($t) => '<' . $t . '>', $to));
        $lines[] = 'Subject: ' . self::encodeHeader($subject);
        $lines[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $msgIdHost . '>';
        $lines[] = 'MIME-Version: 1.0';

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        // Provide a plain-text alternative for deliverability.
        $textBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));

        $alt = [
            '--' . $altBound,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($textBody),
            '--' . $altBound,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($htmlBody),
            '--' . $altBound . '--',
        ];

        $images = self::validInlineImages($inlineImages);
        if ($images === []) {
            $lines[] = 'Content-Type: multipart/alternative; boundary="' . $altBound . '"';
            $lines[] = '';
            $lines   = array_merge($lines, $alt);
            return implode("\r\n", $lines);
        }

        // Wrap the alternative part + inline images in multipart/related so HTML
        // can reference each image via src="cid:…" and clients render it inline.
        $relBound = 'rel_' . bin2hex(random_bytes(8));
        $lines[] = 'Content-Type: multipart/related; boundary="' . $relBound . '"';
        $lines[] = '';
        $lines[] = '--' . $relBound;
        $lines[] = 'Content-Type: multipart/alternative; boundary="' . $altBound . '"';
        $lines[] = '';
        $lines   = array_merge($lines, $alt);

        foreach ($images as $img) {
            $name = 'img_' . substr($img['cid'], 0, 24);
            $lines[] = '--' . $relBound;
            $lines[] = 'Content-Type: ' . $img['mime'] . '; name="' . $name . '"';
            $lines[] = 'Content-Transfer-Encoding: base64';
            $lines[] = 'Content-ID: <' . $img['cid'] . '>';
            $lines[] = 'Content-Disposition: inline; filename="' . $name . '"';
            $lines[] = '';
            $lines[] = self::encodeFile($img['path']);
        }
        $lines[] = '--' . $relBound . '--';

        return implode("\r\n", $lines);
    }

    /**
     * Base64-encode a file (line-wrapped for MIME), cached by path+mtime so a
     * batch of emails sharing the same inline logo only reads/encodes it once.
     *
     * @var array<string,string>
     */
    private static array $encodeCache = [];
    private static function encodeFile(string $path): string
    {
        $key = $path . ':' . (@filemtime($path) ?: 0);
        if (!isset(self::$encodeCache[$key])) {
            self::$encodeCache[$key] = chunk_split(base64_encode((string) file_get_contents($path)), 76, "\r\n");
        }
        return self::$encodeCache[$key];
    }

    /** Keep only inline images that point at a readable file. */
    private static function validInlineImages(array $images): array
    {
        $out = [];
        foreach ($images as $img) {
            if (!empty($img['cid']) && !empty($img['path']) && is_file($img['path'])) {
                $out[] = ['cid' => $img['cid'], 'path' => $img['path'], 'mime' => $img['mime'] ?? 'image/png'];
            }
        }
        return $out;
    }

    private static function encodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}
