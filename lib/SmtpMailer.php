<?php
/**
 * Dependency-free SMTP client.
 *
 * Speaks just enough SMTP to authenticate (AUTH LOGIN) and send a single
 * HTML message over SSL or STARTTLS. Good enough for Gmail, Brevo, Amazon
 * SES and most custom relays — no Composer / PHPMailer required, which keeps
 * deployment on plain Hostinger shared hosting trivial.
 */

declare(strict_types=1);

final class SmtpMailer
{
    private $socket = null;
    private string $lastError = '';

    public function __construct(
        private string $host,
        private int $port,
        private string $encryption, // 'tls' | 'ssl' | 'none'
        private string $username,
        private string $password,
        private int $timeout = 30
    ) {
    }

    public function lastError(): string
    {
        return $this->lastError;
    }

    /**
     * Send one message. Returns true on success.
     *
     * @param array{email:string,name?:string} $from
     * @param array<int,string>                 $to       recipient emails
     * @param array<string,string>              $headers  extra raw headers
     * @param array<int,array{cid:string,path:string,mime:string}> $inlineImages
     *        images embedded via Content-ID so HTML can reference src="cid:…"
     */
    public function send(array $from, array $to, string $subject, string $htmlBody, array $headers = [], array $inlineImages = []): bool
    {
        try {
            $this->connect();
            $this->ehlo();

            if ($this->encryption === 'tls') {
                $this->command('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Failed to enable TLS encryption.');
                }
                $this->ehlo(); // re-greet after TLS upgrade
            }

            $this->authenticate();

            $this->command('MAIL FROM:<' . $from['email'] . '>', 250);
            foreach ($to as $rcpt) {
                $this->command('RCPT TO:<' . $rcpt . '>', 250);
            }
            $this->command('DATA', 354);

            $message = $this->buildMessage($from, $to, $subject, $htmlBody, $headers, $inlineImages);
            // Dot-stuffing for lines beginning with a period.
            $message = preg_replace('/^\./m', '..', $message);
            $this->write($message . "\r\n.");
            $this->expect(250);

            $this->command('QUIT', 221);
            $this->close();
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->close();
            return false;
        }
    }

    // ----------------------------------------------------------------

    private function connect(): void
    {
        $remote = $this->host . ':' . $this->port;
        if ($this->encryption === 'ssl') {
            $remote = 'ssl://' . $remote;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        $this->socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new RuntimeException("Connection failed ({$errno}): {$errstr}");
        }
        stream_set_timeout($this->socket, $this->timeout);
        $this->expect(220);
    }

    private function ehlo(): void
    {
        $host = $_SERVER['SERVER_NAME'] ?? (gethostname() ?: 'localhost');
        $this->write('EHLO ' . $host);
        // EHLO yields a multi-line reply; read it all.
        $this->readReply(250);
    }

    private function authenticate(): void
    {
        $this->command('AUTH LOGIN', 334);
        $this->command(base64_encode($this->username), 334);
        $this->command(base64_encode($this->password), 235);
    }

    private function buildMessage(array $from, array $to, string $subject, string $htmlBody, array $headers, array $inlineImages = []): string
    {
        $fromName = $this->encodeHeader($from['name'] ?? '');
        $date     = date('r');
        $altBound = 'alt_' . bin2hex(random_bytes(8));

        $lines   = [];
        $lines[] = 'Date: ' . $date;
        $lines[] = 'From: ' . ($fromName ? $fromName . ' ' : '') . '<' . $from['email'] . '>';
        $lines[] = 'To: ' . implode(', ', array_map(static fn ($t) => '<' . $t . '>', $to));
        $lines[] = 'Subject: ' . $this->encodeHeader($subject);
        $lines[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($this->host) . '>';
        $lines[] = 'MIME-Version: 1.0';

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        // Provide a plain-text alternative for deliverability.
        $textBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));

        // The text + HTML alternative block, reused with or without images.
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

        $images = $this->validInlineImages($inlineImages);
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
            $lines[] = self::encodeFile($img['path']);   // cached across a batch
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
    private function validInlineImages(array $images): array
    {
        $out = [];
        foreach ($images as $img) {
            if (!empty($img['cid']) && !empty($img['path']) && is_file($img['path'])) {
                $out[] = ['cid' => $img['cid'], 'path' => $img['path'], 'mime' => $img['mime'] ?? 'image/png'];
            }
        }
        return $out;
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    // ----------------------------------------------------------------

    private function command(string $cmd, int $expect): void
    {
        $this->write($cmd);
        $this->expect($expect);
    }

    private function write(string $data): void
    {
        if (!$this->socket || fwrite($this->socket, $data . "\r\n") === false) {
            throw new RuntimeException('Failed to write to SMTP socket.');
        }
    }

    private function expect(int $code): void
    {
        $this->readReply($code);
    }

    /** Read a (possibly multi-line) SMTP reply and assert the status code. */
    private function readReply(int $expected): string
    {
        $data = '';
        while ($this->socket && ($line = fgets($this->socket, 515)) !== false) {
            $data .= $line;
            // Continuation lines look like "250-...", the final like "250 ...".
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($data, 0, 3);
        if ($code !== $expected) {
            throw new RuntimeException('SMTP error: expected ' . $expected . ', got: ' . trim($data));
        }
        return $data;
    }

    private function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }
}
