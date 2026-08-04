<?php

declare(strict_types=1);

/**
 * Standalone "Email Verifier" page — a quick, interactive single-address
 * validator plus bulk file (CSV / TXT / Excel) checking. Thin UI layer over
 * the EmailVerifier engine.
 */
final class EmailVerifyController extends BaseController
{
    /** Hard cap on how many addresses one uploaded file may verify per run. */
    private const BULK_LIMIT = 300;

    public function index(): void
    {
        $this->requireAuth();
        $this->render('verify/index', [], 'Email Verifier');
    }

    /** Verify a single address on demand (AJAX). */
    public function check(): void
    {
        $this->requireAuth();
        csrf_guard();
        $email = str_input('email');
        if ($email === '') {
            json_response(['ok' => false, 'error' => 'Please enter an email address.'], 422);
        }
        json_response(['ok' => true, 'result' => self::shape(EmailVerifier::verify($email, true))]);
    }

    /** Verify every address found in an uploaded CSV / TXT / Excel file (AJAX). */
    public function bulk(): void
    {
        $this->requireAuth();
        csrf_guard();

        if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            json_response(['ok' => false, 'error' => 'Please attach a CSV, TXT or Excel file.'], 422);
        }

        try {
            $emails = $this->extractEmails(
                $_FILES['file']['tmp_name'],
                strtolower((string) pathinfo($_FILES['file']['name'] ?? '', PATHINFO_EXTENSION))
            );
        } catch (\Throwable $e) {
            json_response(['ok' => false, 'error' => 'Could not read the file: ' . $e->getMessage()], 422);
        }

        if ($emails === []) {
            json_response(['ok' => false, 'error' => 'No email addresses were found in that file.'], 422);
        }

        $truncated = count($emails) > self::BULK_LIMIT;
        $emails    = array_slice($emails, 0, self::BULK_LIMIT);

        // Domain-level only (no SMTP probe) to keep a large batch responsive.
        $results = [];
        $summary = ['valid' => 0, 'invalid' => 0, 'risky' => 0];
        foreach ($emails as $addr) {
            $r = self::shape(EmailVerifier::verify($addr, false));
            $summary[$r['status']] = ($summary[$r['status']] ?? 0) + 1;
            $results[] = $r;
        }

        json_response([
            'ok'        => true,
            'count'     => count($results),
            'truncated' => $truncated,
            'limit'     => self::BULK_LIMIT,
            'summary'   => $summary,
            'results'   => $results,
        ]);
    }

    /**
     * Normalise an EmailVerifier result into the flat shape the UI renders.
     * @return array<string,mixed>
     */
    private static function shape(array $r): array
    {
        $byKey  = array_column($r['checks'], null, 'key');
        $syntax = ($byKey['syntax']['state'] ?? 'fail') === 'pass';
        $flags  = $r['flags'];
        $status = $r['status'];

        return [
            'email'        => $r['email'],
            'status'       => $status,                 // valid | invalid | risky | unknown
            'valid'        => $status === 'valid',
            'score'        => $r['score'],
            'message'      => self::message($r),
            'syntax'       => $syntax,
            'role'         => (bool) ($flags['role'] ?? false),
            'disposable'   => (bool) ($flags['disposable'] ?? false),
            'catchall'     => (bool) ($flags['catchall'] ?? false),
            'unknown'      => $status === 'unknown',
            'free'         => (bool) ($flags['free'] ?? false),
            'did_you_mean' => $r['did_you_mean'] ?? null,
        ];
    }

    /** Short human label shown in the result card's "Message" line. */
    private static function message(array $r): string
    {
        if ($r['status'] === 'valid') {
            return 'Valid ID';
        }
        return ucfirst((string) $r['reason']);
    }

    /**
     * Pull every email-looking token out of a file. Supports xlsx (via the
     * dependency-free reader) and plain CSV/TXT (any column / delimiter).
     * @return array<int,string> de-duplicated, lower-cased addresses
     */
    private function extractEmails(string $path, string $ext): array
    {
        $found = [];

        $scan = static function (string $text) use (&$found): void {
            if ($text === '') {
                return;
            }
            if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
                foreach ($m[0] as $hit) {
                    $found[strtolower($hit)] = true;
                }
            }
        };

        if ($ext === 'xlsx') {
            foreach (XlsxReader::rows($path) as $row) {
                $scan(implode(' ', array_map('strval', $row)));
            }
        } else {
            $fh = fopen($path, 'rb');
            if ($fh === false) {
                throw new RuntimeException('unable to open upload');
            }
            while (($line = fgets($fh)) !== false) {
                $scan($line);
            }
            fclose($fh);
        }

        return array_keys($found);
    }
}
