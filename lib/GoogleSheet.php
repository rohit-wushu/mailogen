<?php
/**
 * Read recipients from a published / link-shared Google Sheet.
 *
 * No OAuth, no API key: the user shares the sheet ("Anyone with the link →
 * Viewer" or File → Share → Publish to web) and pastes the link. We turn it
 * into the CSV export endpoint and fetch it over HTTPS, then parse it like a
 * normal CSV upload. Read-only by design.
 */

declare(strict_types=1);

final class GoogleSheet
{
    /** Header column → recognised contact field (mirrors the CSV importer). */
    private const ALIASES = [
        'email'      => ['email', 'e-mail', 'email address', 'mail', 'emailid', 'email id'],
        'first_name' => ['first_name', 'first name', 'firstname', 'fname', 'name'],
        'last_name'  => ['last_name', 'last name', 'lastname', 'lname', 'surname'],
        'company'    => ['company', 'organisation', 'organization', 'business'],
        'sector'     => ['sector', 'industry', 'category', 'segment', 'vertical', 'department'],
        'location'   => ['location', 'city', 'state', 'country', 'region', 'area', 'place', 'zone'],
        'phone'      => ['phone', 'mobile', 'telephone', 'contact'],
    ];

    /** True if the string looks like a Google Sheets link we can read. */
    public static function isSheetUrl(string $url): bool
    {
        return self::csvUrl($url) !== null;
    }

    /**
     * Normalise any Google Sheets link to its CSV export URL.
     * Accepts /edit, /edit#gid=, ?gid=, /pub*, or an already-built export URL.
     * Returns null if it isn't a recognisable Google Sheets link.
     */
    public static function csvUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Published "/d/e/<token>/pubhtml" links use a different id space (note
        // the /d/e/ prefix) — checked first so the generic /d/<id> rule below
        // doesn't mistake the "e" segment for the id. Swap to pub?output=csv.
        if (preg_match('~docs\.google\.com/spreadsheets/d/e/([a-zA-Z0-9-_]+)~', $url, $pm)) {
            $gid = self::extractGid($url);
            return 'https://docs.google.com/spreadsheets/d/e/' . $pm[1] . '/pub?output=csv'
                 . ($gid !== null ? '&gid=' . $gid : '');
        }

        // Pull the spreadsheet id from /spreadsheets/d/<ID>/...
        if (!preg_match('~docs\.google\.com/spreadsheets/d/([a-zA-Z0-9-_]+)~', $url, $m)) {
            return null;
        }

        $id  = $m[1];
        $gid = self::extractGid($url);
        $out = 'https://docs.google.com/spreadsheets/d/' . $id . '/export?format=csv';
        return $gid !== null ? $out . '&gid=' . $gid : $out;
    }

    /** Find the sheet/tab gid in #gid=, ?gid= or &gid=. */
    private static function extractGid(string $url): ?string
    {
        return preg_match('~[#?&]gid=([0-9]+)~', $url, $g) ? $g[1] : null;
    }

    /**
     * Fetch and parse the sheet.
     * @return array{ok:bool, rows:array<int,array<int,string>>, error:string}
     */
    public static function fetch(string $url): array
    {
        $csvUrl = self::csvUrl($url);
        if ($csvUrl === null) {
            return ['ok' => false, 'rows' => [], 'error' => 'That does not look like a Google Sheets link.'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'rows' => [], 'error' => 'PHP cURL extension is not available on this server.'];
        }

        $ch = curl_init($csvUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => 'EventogenMailer/1.0 (+sheet-import)',
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err    = curl_error($ch);
        unset($ch); // curl handles are auto-freed (curl_close is a no-op in PHP 8+)

        if ($body === false) {
            return ['ok' => false, 'rows' => [], 'error' => 'Could not reach Google Sheets: ' . $err];
        }
        if ($status === 401 || $status === 403) {
            return ['ok' => false, 'rows' => [], 'error' => 'The sheet is private. Set sharing to “Anyone with the link → Viewer”.'];
        }
        if ($status !== 200) {
            return ['ok' => false, 'rows' => [], 'error' => 'Google Sheets returned HTTP ' . $status . '.'];
        }
        // A private sheet often 200s with an HTML sign-in page instead of CSV.
        if (stripos($ctype, 'text/html') !== false || stripos((string) $body, '<html') !== false) {
            return ['ok' => false, 'rows' => [], 'error' => 'The sheet is not publicly viewable. Set sharing to “Anyone with the link → Viewer”.'];
        }

        $rows = self::parseCsv((string) $body);
        if ($rows === []) {
            return ['ok' => false, 'rows' => [], 'error' => 'The sheet appears to be empty.'];
        }
        return ['ok' => true, 'rows' => $rows, 'error' => ''];
    }

    /** Parse a CSV string into a 2D array (handles quoted fields, BOM). */
    public static function parseCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv; // strip UTF-8 BOM
        $fh  = fopen('php://temp', 'r+');
        if ($fh === false) {
            return [];
        }
        fwrite($fh, $csv);
        rewind($fh);
        $rows = [];
        while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            // Skip fully blank lines.
            if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    /**
     * Map a header row to recognised field positions.
     * @return array<string,int> field => column index
     */
    public static function mapHeaders(array $header): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $norm = strtolower(trim((string) $col));
            foreach (self::ALIASES as $field => $names) {
                if (in_array($norm, $names, true) && !isset($map[$field])) {
                    $map[$field] = $i;
                }
            }
        }
        return $map;
    }

    /**
     * Turn parsed rows into recipient records. Each record carries the mapped
     * contact fields (email/first_name/…) AND every raw column keyed by its
     * header, so any column can be used as a {{merge tag}}.
     *
     * @return array<int,array<string,string>>
     */
    public static function recipients(array $rows): array
    {
        if (count($rows) < 1) {
            return [];
        }
        $header = array_shift($rows);
        $map    = self::mapHeaders($header);

        // Spreadsheet formula-injection guard (same as the CSV importer).
        $deformula = static fn (string $v): string =>
            ($v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'" . $v : $v;

        $out  = [];
        $seen = [];
        foreach ($rows as $row) {
            $get = static fn (string $field): string =>
                isset($map[$field], $row[$map[$field]]) ? $deformula(trim((string) $row[$map[$field]])) : '';

            $email = strtolower($get('email'));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $rec = [
                'email'      => $email,
                'first_name' => $get('first_name'),
                'last_name'  => $get('last_name'),
                'company'    => $get('company'),
                'sector'     => $get('sector'),
                'location'   => $get('location'),
                'phone'      => $get('phone'),
            ];

            // Add every raw column under its header so {{Header Name}} works.
            $extra = [];
            foreach ($header as $i => $col) {
                $key = trim((string) $col);
                if ($key === '') {
                    continue;
                }
                $val = isset($row[$i]) ? $deformula(trim((string) $row[$i])) : '';
                $rec[$key] = $val;
                $extra[$key] = $val;
            }
            $rec['custom_fields'] = $extra;
            $out[] = $rec;
        }
        return $out;
    }
}
