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

    /** @var ?array{domain:string,selector:string,privateKeyPem:string} */
    private ?array $dkim = null;

    public function __construct(
        private string $host,
        private int $port,
        private string $encryption, // 'tls' | 'ssl' | 'none'
        private string $username,
        private string $password,
        private int $timeout = 30
    ) {
    }

    /** Sign outgoing mail with this domain's DKIM key, independent of the relay used. */
    public function withDkim(string $domain, string $selector, string $privateKeyPem): self
    {
        $this->dkim = ['domain' => $domain, 'selector' => $selector, 'privateKeyPem' => $privateKeyPem];
        return $this;
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

            $message = MimeMessage::buildSigned($from, $to, $subject, $htmlBody, $headers, $inlineImages, $this->dkim, $this->host);
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
