<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * قراءة وكتابة ملفات Excel (.xlsx) البسيطة بدون حزم إضافية
 * القراءة: أول ورقة في الملف · كل صف = مصفوفة قيم نصية
 */
class Xlsx
{
    /** قراءة أول ورقة · يرجع مصفوفة صفوف (كل صف مصفوفة أعمدة من A) */
    public static function read(string $path, int $maxRows = 50000): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('الملف ليس ملف Excel صحيحاً (.xlsx).');
        }

        try {
            $shared = self::sharedStrings($zip);
            $sheet  = self::firstSheetPath($zip);
            $xml    = $zip->getFromName($sheet);
            if ($xml === false) {
                throw new RuntimeException('لم يتم العثور على ورقة بيانات داخل الملف.');
            }
        } finally {
            $zip->close();
        }

        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        if ($doc === false) {
            throw new RuntimeException('تعذر قراءة محتوى الملف.');
        }
        $doc->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($doc->xpath('//m:sheetData/m:row') as $row) {
            if (count($rows) >= $maxRows) {
                throw new RuntimeException("الملف أكبر من الحد المسموح ({$maxRows} صف).");
            }
            $cells = [];
            $next  = 0;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = $ref !== '' ? self::colIndex($ref) : $next;
                $next = $col + 1;
                $cells[$col] = self::cellValue($c, $shared);
            }
            if (! $cells) {
                continue;
            }
            $line = array_fill(0, max(array_keys($cells)) + 1, '');
            foreach ($cells as $i => $v) {
                $line[$i] = $v;
            }
            $rows[] = $line;
        }

        return $rows;
    }

    /** كتابة ملف بورقة واحدة (للقوالب) */
    public static function write(string $path, array $rows, string $sheetName = 'Sheet1'): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذر إنشاء الملف.');
        }

        $sheet = '';
        foreach (array_values($rows) as $r => $row) {
            $sheet .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($row) as $c => $v) {
                $ref = self::colName($c) . ($r + 1);
                if (is_int($v) || is_float($v)) {
                    $sheet .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
                } else {
                    $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                        . htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $rel . '/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="' . $ns . '" xmlns:r="' . $rel . '"><sheets><sheet name="'
            . htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $rel . '/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="' . $ns . '"><sheetViews><sheetView workbookViewId="0" rightToLeft="1"/></sheetViews>'
            . '<cols><col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="40" customWidth="1"/><col min="3" max="3" width="30" customWidth="1"/></cols>'
            . '<sheetData>' . $sheet . '</sheetData></worksheet>');
        $zip->close();
    }

    /* ======================================================= */

    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        if ($doc === false) {
            return [];
        }
        $out = [];
        foreach ($doc->si as $si) {
            if (isset($si->t)) {
                $out[] = (string) $si->t;
            } else {
                $txt = '';
                foreach ($si->r as $r) {
                    $txt .= (string) $r->t;
                }
                $out[] = $txt;
            }
        }

        return $out;
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $wb   = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb !== false && $rels !== false) {
            $w = simplexml_load_string($wb, 'SimpleXMLElement', LIBXML_NONET);
            $r = simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NONET);
            if ($w && $r && isset($w->sheets->sheet[0])) {
                $rid = (string) $w->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach ($r->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $target = ltrim((string) $rel['Target'], '/');
                        return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function cellValue(\SimpleXMLElement $c, array $shared): string
    {
        $t = (string) $c['t'];
        if ($t === 's') {
            return trim($shared[(int) $c->v] ?? '');
        }
        if ($t === 'inlineStr') {
            $txt = isset($c->is->t) ? (string) $c->is->t : '';
            foreach ($c->is->r ?? [] as $r) {
                $txt .= (string) $r->t;
            }
            return trim($txt);
        }
        $v = trim((string) $c->v);
        if ($t === 'b') {
            return $v === '1' ? '1' : '0';
        }
        // أرقام: إزالة .0 الزائدة وتقريب أخطاء الكسور (مثل 12.300000000000001)
        if ($v !== '' && is_numeric($v) && $t !== 'str') {
            $f = (float) $v;
            if (floor($f) == $f && abs($f) < 1e15) {
                return (string) (int) $f;
            }
            return rtrim(rtrim(number_format($f, 6, '.', ''), '0'), '.');
        }

        return $v;
    }

    private static function colIndex(string $ref): int
    {
        $letters = preg_replace('/\d+/', '', strtoupper($ref));
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    private static function colName(int $i): string
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = intdiv($i - 1, 26);
        }

        return $s;
    }
}