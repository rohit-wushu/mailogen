<?php
/**
 * Minimal, dependency-free .xlsx reader.
 *
 * An .xlsx file is a ZIP of XML parts. This reads the first worksheet and the
 * shared-strings table and returns a 2D array of cell values — enough to
 * import contacts without PhpSpreadsheet / Composer.
 */

declare(strict_types=1);

final class XlsxReader
{
    /**
     * @return array<int,array<int,string>> rows of column-indexed values
     */
    public static function rows(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP zip extension is required to import .xlsx files.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the spreadsheet file.');
        }

        // Shared strings.
        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = @simplexml_load_string($ss);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    $shared[] = self::siText($si);
                }
            }
        }

        // First worksheet (resolve via workbook rels, fallback to sheet1).
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 1; $i <= 20; $i++) {
                $sheetXml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
                if ($sheetXml !== false) {
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('No worksheet found in the spreadsheet.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false) {
            throw new RuntimeException('Could not parse the worksheet.');
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref  = (string) $c['r'];                 // e.g. "B3"
                $col  = self::colIndex($ref);
                $type = (string) $c['t'];
                $val  = '';

                if ($type === 's') {                       // shared string
                    $idx = (int) $c->v;
                    $val = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = self::siText($c->is);
                } else {                                    // number / date / bool
                    $val = (string) $c->v;
                }
                $cells[$col] = trim($val);
            }
            if ($cells !== []) {
                // Normalise to a dense 0..max array.
                $max = max(array_keys($cells));
                $dense = [];
                for ($i = 0; $i <= $max; $i++) {
                    $dense[$i] = $cells[$i] ?? '';
                }
                $rows[] = $dense;
            }
        }
        return $rows;
    }

    /** Concatenate text runs inside a shared-string / inline-string node. */
    private static function siText(\SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string) $si->t;
        }
        $text = '';
        foreach ($si->r as $r) {
            $text .= (string) $r->t;
        }
        return $text;
    }

    /** Convert an A1-style ref to a 0-based column index. */
    private static function colIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $letters = $m[1] ?? 'A';
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }
        return $n - 1;
    }
}
