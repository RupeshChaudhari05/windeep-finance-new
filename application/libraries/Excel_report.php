<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

/**
 * Excel_report — one styled XLSX layout for every report.
 *
 * A report is described by a spec array (the same spec drives the HTML page in
 * views/admin/reports/report_view.php):
 *
 *   title, subtitle, kpis[] {label, value, type}, columns[] {key, label, type, total, width},
 *   rows[] (arrays/objects; optional '_style' => 'group'|'subtotal'|'total'),
 *   sheets[] (extra sheets: same keys), note
 *
 * Column types: text, code, date, datetime, money, int, number, pct.
 * Sheet layout: company banner, title, period line, KPI strip, styled header,
 * zebra rows, filter-aware SUBTOTAL totals row, frozen header, autofilter,
 * print setup (fit to width, repeated header, page numbers).
 */
class Excel_report {

    const C_BRAND   = '1F3A5F';
    const C_HEADER  = '2E5C8A';
    const C_ZEBRA   = 'F3F7FB';
    const C_TOTAL   = 'DCE6F1';
    const C_GROUP   = 'EAEFF5';
    const C_BORDER  = 'C5D3E2';
    const C_MUTED   = '6C7A89';

    const FMT_MONEY = '#,##0.00;[Red]-#,##0.00;"-"';
    const FMT_INT   = '#,##0;[Red]-#,##0;"-"';
    const FMT_NUM   = '#,##0.00';
    const FMT_PCT   = '0.00"%"';
    const FMT_DATE  = 'dd-mm-yyyy';
    const FMT_DT    = 'dd-mm-yyyy hh:mm';

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    public static function available() {
        return class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet');
    }

    /**
     * Build the workbook and stream it to the browser.
     */
    public function download(array $spec, $filename = null) {
        if (!self::available()) {
            $this->_csv_fallback($spec, $filename);
            return;
        }
        $book = $this->build($spec);
        $filename = $this->_filename($filename ?: ($spec['filename'] ?? $spec['title'] ?? 'report'));

        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book);
        $writer->save('php://output');
        exit;
    }

    /**
     * Save to a file (used by email attachments and tests).
     */
    public function save(array $spec, $path) {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->build($spec));
        $writer->save($path);
        return $path;
    }

    public function build(array $spec) {
        $book = new Spreadsheet();
        $book->getProperties()
             ->setCreator($this->_company())
             ->setTitle($spec['title'] ?? 'Report')
             ->setDescription($spec['subtitle'] ?? '');
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        $sheets = array_merge([$spec], $spec['sheets'] ?? []);
        foreach ($sheets as $i => $sh) {
            $ws = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            // Extra sheets inherit the report title/period for their banner
            $sh += ['report_title' => $spec['title'] ?? '', 'subtitle' => $spec['subtitle'] ?? ''];
            $this->_sheet($ws, $sh, $i === 0);
        }
        $book->setActiveSheetIndex(0);
        return $book;
    }

    /** Write one styled sheet into an existing workbook (multi-sheet exports). */
    public function write_sheet($ws, array $sh, $is_main = true) {
        $this->_sheet($ws, $sh, $is_main);
    }

    /**
     * Spec from a plain header list + positional rows (older exports). Column types
     * are inferred from the header text: amounts → money, dates → date, counts → int.
     */
    public static function from_headers($title, array $headers, $rows, array $extra = []) {
        $cols = [];
        foreach (array_values($headers) as $i => $h) {
            $l = strtolower($h);
            if (preg_match('/type|status|name|mode|remark|category|relation|gender|email|address|city|state|occupation|level|npa|verified|narration|description/', $l)) $t = 'text';
            elseif (preg_match('/phone|code|no\b|number|ifsc|aadhaar|pan|account/', $l)) $t = 'code';
            elseif (preg_match('/date|created at|paid at/', $l)) $t = 'date';
            elseif (preg_match('/rate|%/', $l)) $t = 'number';
            elseif (preg_match('/days|tenure|count|#|months/', $l)) $t = 'int';
            elseif (preg_match('/amount|amt|principal|interest|emi|paid|balance|fine|payable|disburs|outstanding|deposit|withdraw|income|fee|waived/', $l)) $t = 'money';
            else $t = 'text';
            $cols[] = ['key' => 'c' . $i, 'label' => $h, 'type' => $t, 'total' => $t === 'money'];
        }
        $out = [];
        foreach ($rows as $r) {
            $vals = array_values((array) $r);
            $row = [];
            foreach ($cols as $i => $c) $row['c' . $i] = $vals[$i] ?? '';
            $out[] = $row;
        }
        return array_merge(['title' => $title, 'columns' => $cols, 'rows' => $out], $extra);
    }

    /* ------------------------------------------------------------------ */

    private function _sheet($ws, array $sh, $is_main) {
        $cols = array_values($sh['columns'] ?? []);
        $rows = $sh['rows'] ?? [];
        $ncol = max(1, count($cols));
        $last = Coordinate::stringFromColumnIndex(max($ncol, 4));

        $ws->setTitle($this->_sheet_title($sh['sheet_title'] ?? $sh['title'] ?? 'Sheet'));
        $ws->setShowGridlines(false);

        // ---- Banner -------------------------------------------------------
        $r = 1;
        $ws->setCellValue("A{$r}", $this->_company());
        $ws->mergeCells("A{$r}:{$last}{$r}");
        $ws->getStyle("A{$r}:{$last}{$r}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_BRAND]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $ws->getRowDimension($r)->setRowHeight(26);

        $r++;
        $title = $sh['title'] ?? 'Report';
        if (!$is_main && !empty($sh['report_title'])) $title = $sh['report_title'] . ' — ' . $title;
        $ws->setCellValue("A{$r}", $title);
        $ws->mergeCells("A{$r}:{$last}{$r}");
        $ws->getStyle("A{$r}")->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::C_BRAND);
        $ws->getRowDimension($r)->setRowHeight(20);

        $r++;
        $meta = trim(($sh['subtitle'] ?? '') . '    Generated: ' . date('d-m-Y h:i A') . $this->_by());
        $ws->setCellValue("A{$r}", $meta);
        $ws->mergeCells("A{$r}:{$last}{$r}");
        $ws->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB(self::C_MUTED);

        // ---- KPI strip ----------------------------------------------------
        $kpis = $sh['kpis'] ?? [];
        if ($kpis) {
            $r += 2;
            $per_row = max(2, min($ncol, 6));
            $chunks = array_chunk($kpis, $per_row);
            foreach ($chunks as $chunk) {
                foreach ($chunk as $k => $kpi) {
                    $c = Coordinate::stringFromColumnIndex($k + 1);
                    $ws->setCellValue("{$c}{$r}", $kpi['label']);
                    $this->_put($ws, "{$c}" . ($r + 1), $kpi['value'], $kpi['type'] ?? 'text');
                    $ws->getStyle("{$c}{$r}")->applyFromArray([
                        'font' => ['size' => 8, 'bold' => true, 'color' => ['rgb' => self::C_MUTED]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_ZEBRA]],
                        'alignment' => ['wrapText' => true],
                    ]);
                    $ws->getStyle("{$c}" . ($r + 1))->applyFromArray([
                        'font' => ['size' => 12, 'bold' => true, 'color' => ['rgb' => self::C_BRAND]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_ZEBRA]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    ]);
                    $ws->getStyle("{$c}{$r}:{$c}" . ($r + 1))->getBorders()->getOutline()
                       ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::C_BORDER);
                }
                $r += 2;
            }
            $r--;
        }

        // ---- Table header -------------------------------------------------
        $r += 2;
        $head = $r;
        foreach ($cols as $i => $col) {
            $ws->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $r, $col['label'] ?? $col['key']);
        }
        $lastc = Coordinate::stringFromColumnIndex($ncol);
        $ws->getStyle("A{$r}:{$lastc}{$r}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_HEADER]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::C_BORDER]]],
        ]);
        $ws->getRowDimension($r)->setRowHeight(30);
        foreach ($cols as $i => $col) {
            if ($this->_is_numeric_type($col['type'] ?? 'text')) {
                $ws->getStyle(Coordinate::stringFromColumnIndex($i + 1) . $r)
                   ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        // ---- Data rows ----------------------------------------------------
        $first = $r + 1;
        $widths = [];
        foreach ($cols as $i => $col) $widths[$i] = mb_strlen((string) ($col['label'] ?? '')) * 0.8 + 2;
        $styled = [];
        foreach ($rows as $row) {
            $r++;
            $row = (array) $row;
            foreach ($cols as $i => $col) {
                $v = $this->_value($row, $col);
                $cell = Coordinate::stringFromColumnIndex($i + 1) . $r;
                $this->_put($ws, $cell, $v, $col['type'] ?? 'text');
                $len = is_numeric($v) ? strlen(number_format((float) $v, 2)) : mb_strlen((string) $v);
                $widths[$i] = max($widths[$i], min(60, $len + 2));
            }
            if (!empty($row['_style'])) $styled[$r] = $row['_style'];
        }
        $last_data = $r;

        if ($last_data >= $first) {
            $range = "A{$first}:{$lastc}{$last_data}";
            $ws->getStyle($range)->getBorders()->getAllBorders()
               ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::C_BORDER);
            $ws->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            for ($z = $first; $z <= $last_data; $z++) {
                if (isset($styled[$z])) {
                    $bold = ['font' => ['bold' => true],
                             'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $styled[$z] === 'group' ? self::C_GROUP : self::C_TOTAL]]];
                    $ws->getStyle("A{$z}:{$lastc}{$z}")->applyFromArray($bold);
                } elseif (($z - $first) % 2 === 1) {
                    $ws->getStyle("A{$z}:{$lastc}{$z}")->getFill()
                       ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::C_ZEBRA);
                }
            }
        } else {
            $r++;
            $ws->setCellValue("A{$r}", 'No records for the selected filters.');
            $ws->mergeCells("A{$r}:{$lastc}{$r}");
            $ws->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setRGB(self::C_MUTED);
        }

        // ---- Totals row (SUBTOTAL 109 = filter-aware SUM) -----------------
        $has_total = false;
        foreach ($cols as $col) { if (!empty($col['total'])) { $has_total = true; break; } }
        if ($has_total && $last_data >= $first && empty($sh['no_total_row'])) {
            $r = $last_data + 1;
            $ws->setCellValue("A{$r}", 'TOTAL');
            foreach ($cols as $i => $col) {
                if (empty($col['total'])) continue;
                $c = Coordinate::stringFromColumnIndex($i + 1);
                if ($styled) {
                    // Statement-style sheets carry their own subtotal rows: sum plain rows only
                    $sum = 0;
                    foreach ($rows as $row) { $row = (array) $row; if (empty($row['_style'])) $sum += (float) $this->_value($row, $col); }
                    $ws->setCellValue("{$c}{$r}", round($sum, 2));
                } else {
                    $ws->setCellValue("{$c}{$r}", "=SUBTOTAL(109,{$c}{$first}:{$c}{$last_data})");
                }
                $ws->getStyle("{$c}{$r}")->getNumberFormat()
                   ->setFormatCode(($col['type'] ?? '') === 'int' ? self::FMT_INT : self::FMT_MONEY);
            }
            $ws->getStyle("A{$r}:{$lastc}{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::C_BRAND]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C_TOTAL]],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::C_BRAND]],
                              'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::C_BORDER]]],
            ]);
        }

        // ---- Note ---------------------------------------------------------
        if (!empty($sh['note'])) {
            $r += 2;
            $ws->setCellValue("A{$r}", 'Note: ' . $sh['note']);
            $ws->mergeCells("A{$r}:{$last}{$r}");
            $ws->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB(self::C_MUTED);
            $ws->getStyle("A{$r}")->getAlignment()->setWrapText(true);
            $ws->getRowDimension($r)->setRowHeight(30);
        }

        // ---- Widths, freeze, filter, print ---------------------------------
        foreach ($cols as $i => $col) {
            $w = $col['width'] ?? max(8, min(45, $widths[$i]));
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($w);
        }
        if ($last_data >= $first) {
            $ws->freezePane('A' . $first);
            if (empty($styled)) $ws->setAutoFilter("A{$head}:{$lastc}{$last_data}");
        }
        $ps = $ws->getPageSetup();
        $ps->setOrientation($ncol > 6 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
        $ps->setPaperSize(PageSetup::PAPERSIZE_A4);
        $ps->setFitToWidth(1)->setFitToHeight(0);
        $ps->setRowsToRepeatAtTopByStartAndEnd($head, $head);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
        $ws->getHeaderFooter()->setOddFooter('&L&8' . $this->_xml($this->_company()) . '&C&8' . $this->_xml($sh['title'] ?? '') . '&R&8Page &P of &N');
    }

    private function _value(array $row, array $col) {
        if (isset($col['fn']) && is_callable($col['fn'])) return call_user_func($col['fn'], $row);
        return $row[$col['key']] ?? '';
    }

    private function _put($ws, $cell, $v, $type) {
        $style = $ws->getStyle($cell);
        switch ($type) {
            case 'money':
                if ($v === '' || $v === null) return;
                $ws->setCellValue($cell, round((float) $v, 2));
                $style->getNumberFormat()->setFormatCode(self::FMT_MONEY);
                return;
            case 'int':
                if ($v === '' || $v === null) return;
                $ws->setCellValue($cell, (int) $v);
                $style->getNumberFormat()->setFormatCode(self::FMT_INT);
                return;
            case 'number':
                if ($v === '' || $v === null) return;
                $ws->setCellValue($cell, (float) $v);
                $style->getNumberFormat()->setFormatCode(self::FMT_NUM);
                return;
            case 'pct':
                if ($v === '' || $v === null) return;
                $ws->setCellValue($cell, round((float) $v, 2));
                $style->getNumberFormat()->setFormatCode(self::FMT_PCT);
                return;
            case 'date':
            case 'datetime':
                $ts = $this->_ts($v);
                if ($ts === null) { $ws->setCellValueExplicit($cell, (string) $v, DataType::TYPE_STRING); return; }
                $ws->setCellValue($cell, XlsDate::PHPToExcel($ts));
                $style->getNumberFormat()->setFormatCode($type === 'date' ? self::FMT_DATE : self::FMT_DT);
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                return;
            default:
                // Text/code: always explicit strings so phone numbers, account
                // numbers and codes keep leading zeros and never turn into 9.0E+11
                $ws->setCellValueExplicit($cell, $this->_clean((string) $v), DataType::TYPE_STRING);
                if ($type === 'wrap') $style->getAlignment()->setWrapText(true);
        }
    }

    private function _ts($v) {
        if ($v === null || $v === '' || strpos((string) $v, '0000-00-00') === 0) return null;
        // '!' resets unspecified fields to zero — without it a plain date gets the current time of day
        $d = DateTime::createFromFormat('!Y-m-d H:i:s', (string) $v) ?: DateTime::createFromFormat('!Y-m-d', substr((string) $v, 0, 10));
        if (!$d || (int) $d->format('Y') < 1900 || (int) $d->format('Y') > 2200) return null;
        // PHPToExcel treats the timestamp as UTC; build it from the local wall clock
        return gmmktime((int) $d->format('H'), (int) $d->format('i'), (int) $d->format('s'),
                        (int) $d->format('n'), (int) $d->format('j'), (int) $d->format('Y'));
    }

    private function _is_numeric_type($t) {
        return in_array($t, ['money', 'int', 'number', 'pct'], true);
    }

    private function _clean($s) {
        // Strip control characters and neutralise spreadsheet formula injection
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
        if ($s !== '' && in_array($s[0], ['=', '+', '@'], true)) $s = "'" . $s;
        return $s;
    }

    private function _sheet_title($t) {
        $t = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) $t);
        return mb_substr(trim($t) ?: 'Sheet', 0, 31);
    }

    private function _filename($name) {
        $name = preg_replace('/[^A-Za-z0-9_\-]+/', '_', strtolower(trim($name)));
        return trim($name, '_') . '_' . date('Y-m-d');
    }

    private function _xml($s) {
        return str_replace('&', '&&', (string) $s);
    }

    private function _company() {
        static $name = null;
        if ($name === null) {
            $name = 'Windeep Finance';
            try {
                $row = $this->CI->db->select('setting_value')->where('setting_key', 'company_name')->get('system_settings')->row();
                if ($row && trim($row->setting_value) !== '') $name = trim($row->setting_value);
            } catch (Throwable $e) { /* keep default */ }
        }
        return $name;
    }

    private function _by() {
        $n = $this->CI->session ? $this->CI->session->userdata('admin_name') : null;
        return $n ? '  by ' . $n : '';
    }

    private function _csv_fallback(array $spec, $filename) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->_filename($filename ?: ($spec['title'] ?? 'report')) . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, array_map(function ($c) { return $c['label'] ?? $c['key']; }, $spec['columns'] ?? []));
        foreach ($spec['rows'] ?? [] as $row) {
            $row = (array) $row;
            fputcsv($out, array_map(function ($c) use ($row) { return $this->_value($row, $c); }, $spec['columns'] ?? []));
        }
        fclose($out);
        exit;
    }
}
