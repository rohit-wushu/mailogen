<?php
/**
 * DKIM (RFC 6376) key generation and message signing.
 *
 * The platform signs outgoing mail with its own key per verified sending
 * domain — independent of which SMTP relay actually carries the message —
 * so a bare custom SMTP server still produces DKIM-authenticated mail.
 * Uses relaxed/relaxed canonicalization + rsa-sha256, the combination every
 * major receiver (Gmail, Outlook, Yahoo) expects.
 */

declare(strict_types=1);

final class Dkim
{
    /** Headers signed when present, in this preference order. */
    private const SIGNED_HEADERS = ['from', 'to', 'subject', 'date', 'message-id', 'mime-version', 'content-type'];

    /** Generate a 2048-bit RSA keypair. Returns ['private' => PEM, 'public' => base64 DER for the DNS TXT record]. */
    public static function generateKeyPair(): array
    {
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($res === false) {
            throw new RuntimeException('Could not generate a DKIM keypair (OpenSSL unavailable?).');
        }

        openssl_pkey_export($res, $privatePem);
        $details = openssl_pkey_get_details($res);
        $publicPem = $details['key'];

        // DNS wants the raw base64 DER (the PEM body without header/footer/newlines).
        $lines = explode("\n", trim($publicPem));
        $publicDer = implode('', array_slice($lines, 1, -1));

        return ['private' => $privatePem, 'public' => $publicDer];
    }

    /**
     * Sign a raw RFC 5322 message (headers + blank line + body, CRLF)
     * and return the DKIM-Signature header line to prepend (no trailing CRLF).
     */
    public static function sign(string $rawMessage, string $domain, string $selector, string $privateKeyPem): string
    {
        $sep = strpos($rawMessage, "\r\n\r\n");
        if ($sep === false) {
            throw new InvalidArgumentException('Message has no header/body separator.');
        }
        $headerBlock = substr($rawMessage, 0, $sep);
        $body        = substr($rawMessage, $sep + 4);

        $bh = base64_encode(hash('sha256', self::canonicalizeBody($body), true));

        $headers = self::parseHeaders($headerBlock);
        $signedNames = [];
        $canonHeaders = '';
        foreach (self::SIGNED_HEADERS as $want) {
            foreach ($headers as [$name, $value]) {
                if (strtolower($name) === $want) {
                    $canonHeaders .= self::canonicalizeHeader($name, $value) . "\r\n";
                    $signedNames[] = $name;
                    break; // sign at most one instance of each field
                }
            }
        }
        if ($signedNames === []) {
            throw new RuntimeException('No signable headers found on outgoing message.');
        }

        $tag = 'v=1; a=rsa-sha256; c=relaxed/relaxed; d=' . $domain . '; s=' . $selector
             . '; t=' . time() . '; h=' . implode(':', $signedNames) . '; bh=' . $bh . '; b=';

        // The DKIM-Signature header itself is canonicalized (with b= empty)
        // and appended WITHOUT a trailing CRLF — that's what gets signed.
        $signingInput = $canonHeaders . self::canonicalizeHeader('DKIM-Signature', ' ' . $tag);

        $ok = openssl_sign($signingInput, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new RuntimeException('DKIM signing failed (invalid private key?).');
        }

        return 'DKIM-Signature: ' . $tag . base64_encode($signature);
    }

    /** @return array<int,array{0:string,1:string}> ordered [name, value] pairs, folded lines joined. */
    private static function parseHeaders(string $headerBlock): array
    {
        $unfolded = preg_replace("/\r\n[ \t]+/", ' ', $headerBlock);
        $out = [];
        foreach (explode("\r\n", $unfolded) as $line) {
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $out[] = [$name, $value];
        }
        return $out;
    }

    /** RFC 6376 §3.4.2 relaxed header canonicalization. */
    private static function canonicalizeHeader(string $name, string $value): string
    {
        $value = preg_replace('/\r\n[ \t]+/', ' ', $value) ?? $value; // unfold
        $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;     // collapse WSP runs
        return strtolower(trim($name)) . ':' . trim($value);
    }

    /** RFC 6376 §3.4.4 relaxed body canonicalization. */
    private static function canonicalizeBody(string $body): string
    {
        $lines = explode("\r\n", $body);
        foreach ($lines as &$line) {
            $line = rtrim(preg_replace('/[ \t]+/', ' ', $line) ?? $line, " \t");
        }
        unset($line);
        // Remove trailing empty lines, then guarantee exactly one terminating CRLF
        // (an empty body canonicalizes to the empty string, per spec).
        while ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }
        return $lines === [] ? '' : implode("\r\n", $lines) . "\r\n";
    }
}
