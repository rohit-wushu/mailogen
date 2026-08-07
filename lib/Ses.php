<?php
/**
 * Amazon SES API v1 (query protocol) client, signed with AWS Signature
 * Version 4 — hand-rolled, no AWS SDK, matching this project's
 * dependency-free approach (see SmtpMailer, Dkim).
 *
 * Used as the transport for domain-based campaigns: the raw MIME message is
 * built and DKIM-signed exactly like the SMTP path (MimeMessage::buildSigned)
 * and handed to SES's SendRawEmail action, so behaviour is identical to a
 * BYO-SMTP send except for the wire transport.
 */

declare(strict_types=1);

final class Ses
{
    private const SERVICE = 'ses';

    /**
     * Send a pre-built raw MIME message through SES.
     *
     * @param array{access_key:string,secret_key:string,region:string} $conn  decrypted credentials
     * @param array<int,string> $toEmails
     * @return array{ok:bool,error:string,message_id:string}
     */
    public static function sendRaw(array $conn, string $rawMime, string $fromEmail, array $toEmails): array
    {
        $params = [
            'Action'  => 'SendRawEmail',
            'Version' => '2010-12-01',
            'Source'  => $fromEmail,
        ];
        foreach (array_values($toEmails) as $i => $to) {
            $params['Destinations.member.' . ($i + 1)] = $to;
        }
        $params['RawMessage.Data'] = base64_encode($rawMime);

        $res = self::request($conn, $params);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'], 'message_id' => ''];
        }
        $messageId = '';
        if (preg_match('/<MessageId>([^<]+)<\/MessageId>/', $res['body'], $m)) {
            $messageId = $m[1];
        }
        return ['ok' => true, 'error' => '', 'message_id' => $messageId];
    }

    /**
     * Validate credentials with a lightweight, side-effect-free call.
     *
     * @param array{access_key:string,secret_key:string,region:string} $conn
     * @return array{ok:bool,error:string,quota:?array}
     */
    public static function verify(array $conn): array
    {
        $res = self::request($conn, ['Action' => 'GetSendQuota', 'Version' => '2010-12-01']);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'], 'quota' => null];
        }
        $quota = null;
        if (preg_match('/<Max24HourSend>([^<]+)</', $res['body'], $max)
            && preg_match('/<SentLast24Hours>([^<]+)</', $res['body'], $sent)
            && preg_match('/<MaxSendRate>([^<]+)</', $res['body'], $rate)) {
            $quota = [
                'max24Hour' => (float) $max[1],
                'sent24Hour' => (float) $sent[1],
                'maxRate' => (float) $rate[1],
            ];
        }
        return ['ok' => true, 'error' => '', 'quota' => $quota];
    }

    /**
     * SigV4-signed POST to the SES query endpoint for $conn['region'].
     *
     * @return array{ok:bool,error:string,body:string,status:int}
     */
    private static function request(array $conn, array $params): array
    {
        $region = $conn['region'] !== '' ? $conn['region'] : 'us-east-1';
        $host   = "email.$region.amazonaws.com";
        $body   = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $canonicalHeaders = "content-type:application/x-www-form-urlencoded\nhost:$host\nx-amz-date:$amzDate\n";
        $signedHeaders    = 'content-type;host;x-amz-date';
        $payloadHash      = hash('sha256', $body);
        $canonicalRequest = "POST\n/\n\n$canonicalHeaders\n$signedHeaders\n$payloadHash";

        $credentialScope = "$dateStamp/$region/" . self::SERVICE . '/aws4_request';
        $stringToSign    = "AWS4-HMAC-SHA256\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);
        $signingKey      = self::signingKey($conn['secret_key'], $dateStamp, $region);
        $signature       = hash_hmac('sha256', $stringToSign, $signingKey);

        $authHeader = 'AWS4-HMAC-SHA256 Credential=' . $conn['access_key'] . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

        $ch = curl_init("https://$host/");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-Amz-Date: ' . $amzDate,
                'Authorization: ' . $authHeader,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $respBody = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($respBody === false) {
            return ['ok' => false, 'error' => 'Network error contacting Amazon SES: ' . $curlErr, 'body' => '', 'status' => 0];
        }
        if ($status < 200 || $status >= 300) {
            $msg = '';
            if (preg_match('/<Message>([^<]+)<\/Message>/', (string) $respBody, $m)) {
                $msg = $m[1];
            }
            return ['ok' => false, 'error' => $msg !== '' ? $msg : "Amazon SES returned HTTP $status", 'body' => (string) $respBody, 'status' => $status];
        }
        return ['ok' => true, 'error' => '', 'body' => (string) $respBody, 'status' => $status];
    }

    private static function signingKey(string $secretKey, string $dateStamp, string $region): string
    {
        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', self::SERVICE, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
