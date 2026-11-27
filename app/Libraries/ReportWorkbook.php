<?php

namespace App\Libraries;

use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Pembuat file Excel untuk laporan (Pembelian, Penjualan, Stok, Produk Terlaris).
 *
 * Setiap workbook berisi dua sheet:
 *   1. "Laporan": tabel yang sama dengan yang tampil di halaman (judul, periode, filter otomatis, total).
 *   2. "Grafik" : tabel ringkasan agregat + grafik native Excel yang menunjuk ke tabel itu.
 *
 * Kelas ini murni (tidak menyentuh database/CodeIgniter): menerima baris laporan dari ReportingService
 * sehingga isi Excel selalu sama dengan layar.
 *
 * Keamanan: SEMUA teks ditulis sebagai string eksplisit. Nama supplier/produk yang diawali "=", "+", "-"
 * atau "@" tidak akan pernah dijalankan Excel sebagai rumus (formula injection).
 */
class ReportWorkbook
{
    private const BRAND   = '4338CA'; // indigo, selaras dengan header halaman
    private const BORDER  = 'DFE3EE';
    private const ZEBRA   = 'F6F7FF';
    private const INK     = '1F2433';
    private const MUTED   = '6B7287';
    private const MONEY   = '"Rp" #,##0';
    private const DATE    = 'dd mmm yyyy';
    private const INTEGER = '#,##0';

    private const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** [warna huruf, warna latar] per status dokumen. */
    private const STATUS = [
        'Draft'      => ['475569', 'E2E8F0'],
        'Approved'   => ['0F766E', 'CCFBF1'],
        'Open'       => ['1D4ED8', 'DBEAFE'],
        'Selesai'    => ['15803D', 'DCFCE7'],
        'Aktif'      => ['15803D', 'DCFCE7'],
        'Dibatalkan' => ['B91C1C', 'FEE2E2'],
    ];

    /** Urutan status pada ringkasan. */
    private const STATUS_ORDER = ['Draft', 'Approved', 'Open', 'Selesai', 'Aktif', 'Dibatalkan'];

    private const PALETTE = ['4338CA', '0EA5C6', '0F8A5C', 'F59E0B', '6D4AED', 'DC2626', '64748B', 'DB2777'];

    // ==================================================================
    // Laporan
    // ==================================================================

    /** @param list<array<string,mixed>> $rows baris dari ReportingService::purchasesReport() */
    public function purchases(array $rows, array $filter, string $printedBy): Spreadsheet
    {
        return $this->documentReport(
            $rows, $filter, $printedBy,
            [
                'title' => 'Laporan Pembelian', 'noun' => 'PO', 'party' => 'Supplier',
                'number' => 'po_number', 'date' => 'po_date', 'name' => 'supplier_name',
                'number_label' => 'Nomor PO', 'name_label' => 'Supplier',
                'total_label' => 'Total Pembelian', 'color' => self::BRAND,
            ]
        );
    }

    /** @param list<array<string,mixed>> $rows baris dari ReportingService::salesReport() */
    public function sales(array $rows, array $filter, string $printedBy): Spreadsheet
    {
        return $this->documentReport(
            $rows, $filter, $printedBy,
            [
                'title' => 'Laporan Penjualan', 'noun' => 'SO', 'party' => 'Customer',
                'number' => 'so_number', 'date' => 'so_date', 'name' => 'customer_name',
                'number_label' => 'Nomor SO', 'name_label' => 'Customer',
                'total_label' => 'Total Penjualan', 'color' => '0EA5C6',
            ]
        );
    }

    /** @param list<array<string,mixed>> $rows baris dari ReportingService::stockReport() */
    public function stock(array $rows, array $filter, string $printedBy, ?string $categoryName = null): Spreadsheet
    {
        $wb = $this->newWorkbook('Laporan Stok');
        $ws = $wb->getActiveSheet();
        $ws->setTitle('Laporan');

        $cols = [
            ['key' => 'code',          'label' => 'Kode',        'width' => 14, 'type' => 'code'],
            ['key' => 'name',          'label' => 'Nama Produk', 'width' => 40, 'type' => 'text'],
            ['key' => 'category_name', 'label' => 'Kategori',    'width' => 20, 'type' => 'text'],
            ['key' => 'unit',          'label' => 'Satuan',      'width' => 10, 'type' => 'center'],
            ['key' => 'stok_awal',     'label' => 'Stok Awal',   'width' => 12, 'type' => 'int'],
            ['key' => 'total_masuk',   'label' => 'Masuk',       'width' => 12, 'type' => 'in'],
            ['key' => 'total_keluar',  'label' => 'Keluar',      'width' => 12, 'type' => 'out'],
            ['key' => 'stock_akhir',   'label' => 'Stok Akhir',  'width' => 13, 'type' => 'final'],
        ];
        $last = Coordinate::stringFromColumnIndex(count($cols));

        $extra = $categoryName !== null && $categoryName !== '' ? 'Kategori: ' . $categoryName : 'Kategori: Semua Kategori';
        $this->banner($ws, 'Laporan Stok', $filter, $printedBy, $last, self::BRAND, $extra);
        [$first, $end] = $this->table($ws, $cols, $rows, 6);

        if ($rows !== []) {
            $t = $end + 1;
            $this->label($ws, "A{$t}:D{$t}", 'Total (mengikuti filter Excel)');
            foreach (['E', 'F', 'G', 'H'] as $c) {
                $ws->setCellValue("{$c}{$t}", "=SUBTOTAL(109,{$c}{$first}:{$c}{$end})");
            }
            $this->totalStyle($ws, "A{$t}:{$last}{$t}", self::INTEGER);
        }
        $this->finishSheet($ws, $last, $first, $end);

        $graph = $wb->createSheet();
        $graph->setTitle('Grafik');
        $this->graphBanner($graph, 'Grafik Laporan Stok', $filter);

        if ($rows !== []) {
            $blocks = [];

            // Pergerakan terbesar: masuk vs keluar
            $move = $rows;
            usort($move, static fn ($a, $b) => ((float) $b['total_masuk'] + (float) $b['total_keluar']) <=> ((float) $a['total_masuk'] + (float) $a['total_keluar']));
            $move = array_slice($move, 0, 15);
            $blocks[] = [
                'title'   => 'Pergerakan stok (maks. 15 produk dengan mutasi terbesar)',
                'headers' => ['Kode', 'Masuk', 'Keluar'],
                'formats' => [null, 'int', 'int'],
                'rows'    => array_map(static fn ($r) => [(string) $r['code'], (float) $r['total_masuk'], (float) $r['total_keluar']], $move),
                'chart'   => [
                    'type' => 'col', 'title' => 'Barang Masuk vs Keluar per Produk', 'y_title' => 'Kuantitas',
                    'series' => [[1, '0F8A5C'], [2, 'DC2626']], 'format' => self::INTEGER,
                ],
            ];

            // Stok akhir terbesar
            $top = $rows;
            usort($top, static fn ($a, $b) => (float) $b['stock_akhir'] <=> (float) $a['stock_akhir']);
            $top = array_slice($top, 0, 15);
            $blocks[] = [
                'title'   => 'Stok akhir (maks. 15 produk dengan stok terbesar)',
                'headers' => ['Kode', 'Stok Akhir'],
                'formats' => [null, 'int'],
                'rows'    => array_map(static fn ($r) => [(string) $r['code'], (float) $r['stock_akhir']], $top),
                'chart'   => [
                    'type' => 'col', 'title' => 'Stok Akhir per Produk', 'y_title' => 'Kuantitas',
                    'series' => [[1, self::BRAND]], 'format' => self::INTEGER, 'legend' => false,
                ],
            ];

            // Komposisi per kategori (hanya bila ada >= 2 kategori)
            $byCat = [];
            foreach ($rows as $r) {
                $k = trim((string) ($r['category_name'] ?? '')) !== '' ? (string) $r['category_name'] : 'Tanpa kategori';
                $byCat[$k] = ($byCat[$k] ?? 0) + (float) $r['stock_akhir'];
            }
            arsort($byCat);
            if (count($byCat) >= 2 && array_sum($byCat) > 0) {
                $blocks[] = [
                    'title'   => 'Stok akhir per kategori',
                    'headers' => ['Kategori', 'Stok Akhir'],
                    'formats' => [null, 'int'],
                    'rows'    => array_map(null, array_keys($byCat), array_values($byCat)),
                    'chart'   => ['type' => 'pie', 'title' => 'Komposisi Stok Akhir per Kategori', 'series' => [[1, null]]],
                ];
            }

            $this->graphBlocks($graph, $blocks);
        }

        $wb->setActiveSheetIndex(0);

        return $wb;
    }

    /** @param list<array<string,mixed>> $rows baris dari ReportingService::topProductsReport() */
    public function topProducts(array $rows, array $filter, string $printedBy): Spreadsheet
    {
        $wb = $this->newWorkbook('Top 10 Produk Terlaris');
        $ws = $wb->getActiveSheet();
        $ws->setTitle('Laporan');

        // Peringkat ditambahkan sebagai kolom nyata supaya ikut tersaring/terurut di Excel
        foreach ($rows as $i => &$r) {
            $r['rank'] = $i + 1;
        }
        unset($r);

        $cols = [
            ['key' => 'rank',          'label' => 'Peringkat',         'width' => 12, 'type' => 'center'],
            ['key' => 'code',          'label' => 'Kode',              'width' => 14, 'type' => 'code'],
            ['key' => 'name',          'label' => 'Nama Produk',       'width' => 42, 'type' => 'text'],
            ['key' => 'total_qty',     'label' => 'Kuantitas Terjual', 'width' => 20, 'type' => 'int'],
            ['key' => 'total_revenue', 'label' => 'Total Pendapatan',  'width' => 22, 'type' => 'money'],
        ];
        $last = Coordinate::stringFromColumnIndex(count($cols));

        $this->banner($ws, 'Top 10 Produk Terlaris', $filter, $printedBy, $last, '6D4AED');
        [$first, $end] = $this->table($ws, $cols, $rows, 5 + 1);

        if ($rows !== []) {
            $t = $end + 1;
            $this->label($ws, "A{$t}:C{$t}", 'Total (mengikuti filter Excel)');
            $ws->setCellValue("D{$t}", "=SUBTOTAL(109,D{$first}:D{$end})");
            $ws->setCellValue("E{$t}", "=SUBTOTAL(109,E{$first}:E{$end})");
            $this->totalStyle($ws, "A{$t}:{$last}{$t}", null);
            $ws->getStyle("D{$t}")->getNumberFormat()->setFormatCode(self::INTEGER);
            $ws->getStyle("E{$t}")->getNumberFormat()->setFormatCode(self::MONEY);
        }
        $this->finishSheet($ws, $last, $first, $end);

        $graph = $wb->createSheet();
        $graph->setTitle('Grafik');
        $this->graphBanner($graph, 'Grafik Top 10 Produk Terlaris', $filter);

        if ($rows !== []) {
            $this->graphBlocks($graph, [
                [
                    'title'   => 'Kuantitas terjual per produk',
                    'headers' => ['Produk', 'Kuantitas'],
                    'formats' => [null, 'int'],
                    'rows'    => array_map(static fn ($r) => [(string) $r['code'], (float) $r['total_qty']], $rows),
                    'chart'   => [
                        'type' => 'col', 'title' => 'Kuantitas Terjual per Produk', 'y_title' => 'Kuantitas',
                        'series' => [[1, '6D4AED']], 'format' => self::INTEGER, 'legend' => false,
                    ],
                ],
                [
                    'title'   => 'Pendapatan per produk',
                    'headers' => ['Produk', 'Pendapatan (Rp)'],
                    'formats' => [null, 'money'],
                    'rows'    => array_map(static fn ($r) => [(string) $r['code'], (float) $r['total_revenue']], $rows),
                    'chart'   => [
                        'type' => 'col', 'title' => 'Total Pendapatan per Produk', 'y_title' => 'Rupiah',
                        'series' => [[1, '0F8A5C']], 'format' => self::INTEGER, 'legend' => false,
                    ],
                ],
            ]);
        }

        $wb->setActiveSheetIndex(0);

        return $wb;
    }

    // ==================================================================
    // Laporan dokumen (PO / SO): satu kerangka, dua laporan
    // ==================================================================

    private function documentReport(array $rows, array $filter, string $printedBy, array $o): Spreadsheet
    {
        $wb = $this->newWorkbook($o['title']);
        $ws = $wb->getActiveSheet();
        $ws->setTitle('Laporan');

        $cols = [
            ['key' => $o['number'], 'label' => $o['number_label'], 'width' => 18, 'type' => 'code'],
            ['key' => $o['date'],   'label' => 'Tanggal',          'width' => 15, 'type' => 'date'],
            ['key' => $o['name'],   'label' => $o['name_label'],   'width' => 44, 'type' => 'text'],
            ['key' => 'status',     'label' => 'Status',           'width' => 15, 'type' => 'status'],
            ['key' => 'total_amount', 'label' => 'Total',          'width' => 22, 'type' => 'money'],
        ];
        $last = Coordinate::stringFromColumnIndex(count($cols));

        $this->banner($ws, $o['title'], $filter, $printedBy, $last, $o['color']);
        [$first, $end] = $this->table($ws, $cols, $rows, 6);

        if ($rows !== []) {
            $t1 = $end + 1;
            $t2 = $end + 2;
            $this->label($ws, "A{$t1}:D{$t1}", $o['total_label'] . ' (mengikuti filter Excel)');
            $ws->setCellValue("E{$t1}", "=SUBTOTAL(109,E{$first}:E{$end})");
            $this->label($ws, "A{$t2}:D{$t2}", $o['total_label'] . ' tanpa status Dibatalkan');
            $ws->setCellValue("E{$t2}", "=SUMIFS(E{$first}:E{$end},D{$first}:D{$end},\"<>Dibatalkan\")");
            $this->totalStyle($ws, "A{$t1}:{$last}{$t2}", self::MONEY);
        }
        $this->finishSheet($ws, $last, $first, $end);

        // ----- Grafik -----
        $graph = $wb->createSheet();
        $graph->setTitle('Grafik');
        $this->graphBanner($graph, 'Grafik ' . $o['title'], $filter);

        if ($rows !== []) {
            [$periodRows, $unit] = $this->periodSeries($rows, $o['date'], $filter);
            $statusRows          = $this->statusSeries($rows);
            $partyRows           = $this->topParties($rows, $o['name'], 8);

            $blocks = [
                [
                    'title'   => 'Nilai ' . $o['noun'] . ' per ' . ($unit === 'day' ? 'hari' : 'bulan') . ' (tanpa Dibatalkan)',
                    'headers' => ['Periode', 'Nilai (Rp)', 'Jumlah ' . $o['noun']],
                    'formats' => [null, 'money', 'int'],
                    'rows'    => $periodRows,
                    'chart'   => [
                        'type' => 'col', 'title' => $o['total_label'] . ' per ' . ($unit === 'day' ? 'Hari' : 'Bulan'),
                        'y_title' => 'Rupiah', 'series' => [[1, $o['color']]], 'format' => self::INTEGER, 'legend' => false,
                    ],
                ],
                [
                    'title'   => 'Komposisi status ' . $o['noun'],
                    'headers' => ['Status', 'Jumlah ' . $o['noun'], 'Nilai (Rp)'],
                    'formats' => [null, 'int', 'money'],
                    'rows'    => $statusRows,
                    'chart'   => [
                        'type' => 'pie', 'title' => 'Komposisi Status ' . $o['noun'] . ' (jumlah dokumen)', 'series' => [[1, null]],
                        'point_colors' => array_map(fn ($r) => (self::STATUS[$r[0]][0] ?? '64748B'), $statusRows),
                    ],
                ],
            ];
            if ($partyRows !== []) {
                $blocks[] = [
                    'title'   => 'Top ' . count($partyRows) . ' ' . strtolower($o['party']) . ' menurut nilai (tanpa Dibatalkan)',
                    'headers' => [$o['party'], 'Nilai (Rp)'],
                    'formats' => [null, 'money'],
                    'rows'    => $partyRows,
                    'chart'   => [
                        'type' => 'col', 'title' => 'Top ' . $o['party'] . ' menurut Nilai ' . $o['noun'],
                        'y_title' => 'Rupiah', 'series' => [[1, '0F8A5C']], 'format' => self::INTEGER, 'legend' => false,
                    ],
                ];
            }
            $this->graphBlocks($graph, $blocks);
        }

        $wb->setActiveSheetIndex(0);

        return $wb;
    }

    // ==================================================================
    // Agregasi untuk grafik
    // ==================================================================

    /**
     * Nilai dan jumlah dokumen per periode (tanpa Dibatalkan). Periode kosong tetap muncul bernilai 0
     * agar tren tidak terputus. Rentang <= 62 hari per hari, selebihnya per bulan.
     *
     * @return array{0: list<array{0:string,1:float,2:int}>, 1: string} [baris, 'day'|'month']
     */
    private function periodSeries(array $rows, string $dateKey, array $filter): array
    {
        $start = new DateTimeImmutable($filter['start']);
        $end   = new DateTimeImmutable($filter['end']);
        $byDay = ((int) $start->diff($end)->days + 1) <= 62;

        $buckets = [];
        if ($byDay) {
            for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
                $buckets[$d->format('Y-m-d')] = [$d->format('d') . ' ' . self::MONTHS[(int) $d->format('n')], 0.0, 0];
            }
        } else {
            $endMonth = $end->modify('first day of this month');
            for ($m = $start->modify('first day of this month'); $m <= $endMonth; $m = $m->modify('+1 month')) {
                $buckets[$m->format('Y-m')] = [self::MONTHS[(int) $m->format('n')] . ' ' . $m->format('Y'), 0.0, 0];
            }
        }

        foreach ($rows as $r) {
            if (($r['status'] ?? '') === 'Dibatalkan') {
                continue;
            }
            $key = substr((string) $r[$dateKey], 0, $byDay ? 10 : 7);
            if (isset($buckets[$key])) {
                $buckets[$key][1] += (float) $r['total_amount'];
                $buckets[$key][2]++;
            }
        }

        return [array_values($buckets), $byDay ? 'day' : 'month'];
    }

    /** @return list<array{0:string,1:int,2:float}> */
    private function statusSeries(array $rows): array
    {
        $agg = [];
        foreach ($rows as $r) {
            $s = (string) ($r['status'] ?? '-');
            $agg[$s] ??= [0, 0.0];
            $agg[$s][0]++;
            $agg[$s][1] += (float) $r['total_amount'];
        }

        $out   = [];
        $known = array_values(array_filter(self::STATUS_ORDER, static fn ($s) => isset($agg[$s])));
        $other = array_diff(array_keys($agg), self::STATUS_ORDER);
        sort($other);
        foreach (array_merge($known, $other) as $s) {
            $out[] = [$s, $agg[$s][0], $agg[$s][1]];
        }

        return $out;
    }

    /** @return list<array{0:string,1:float}> */
    private function topParties(array $rows, string $nameKey, int $limit): array
    {
        $agg = [];
        foreach ($rows as $r) {
            if (($r['status'] ?? '') === 'Dibatalkan') {
                continue;
            }
            $n       = trim((string) $r[$nameKey]) !== '' ? (string) $r[$nameKey] : '-';
            $agg[$n] = ($agg[$n] ?? 0.0) + (float) $r['total_amount'];
        }
        arsort($agg);

        $out = [];
        foreach (array_slice($agg, 0, $limit, true) as $n => $v) {
            $out[] = [(string) $n, $v];
        }

        return $out;
    }

    // ==================================================================
    // Sheet "Laporan"
    // ==================================================================

    private function newWorkbook(string $title): Spreadsheet
    {
        $wb = new Spreadsheet();
        $wb->getProperties()->setCreator('Mini ERP - PT Nusantara Distribusi')->setTitle($title)->setSubject($title);
        $wb->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11)->getColor()->setRGB(self::INK);

        return $wb;
    }

    /** Judul, periode, dan keterangan cetak (baris 1-4). */
    private function banner(Worksheet $ws, string $title, array $filter, string $printedBy, string $lastCol, string $color, ?string $extra = null): void
    {
        $this->text($ws, 'A1', $title);
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $ws->getRowDimension(1)->setRowHeight(32);

        $period = 'Periode: ' . self::dateLabel($filter['start']) . ' s.d. ' . self::dateLabel($filter['end']);
        if ($extra !== null) {
            $period .= '   |   ' . $extra;
        }
        $this->text($ws, 'A2', $period);
        $ws->getStyle('A2')->getFont()->setBold(true);

        $this->text($ws, 'A3', 'Dicetak: ' . date('d') . ' ' . self::MONTHS[(int) date('n')] . ' ' . date('Y H:i') . ' oleh ' . $printedBy);
        $ws->getStyle('A3')->getFont()->setColor(self::rgb(self::MUTED))->setSize(10);
    }

    /**
     * Tulis tabel mulai baris $headerRow (judul kolom), data di bawahnya.
     *
     * @param list<array{key:string,label:string,width:int|float,type:string}> $cols
     *
     * @return array{0:int,1:int} [baris data pertama, baris data terakhir]
     */
    private function table(Worksheet $ws, array $cols, array $rows, int $headerRow): array
    {
        $lastCol = Coordinate::stringFromColumnIndex(count($cols));

        foreach ($cols as $i => $c) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $this->text($ws, $letter . $headerRow, $c['label']);
            $ws->getColumnDimension($letter)->setWidth($c['width']);
        }
        $ws->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::INK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $ws->getRowDimension($headerRow)->setRowHeight(24);

        $first = $headerRow + 1;
        $r     = $headerRow;
        foreach ($rows as $n => $row) {
            $r++;
            foreach ($cols as $i => $c) {
                $coord = Coordinate::stringFromColumnIndex($i + 1) . $r;
                $v     = $row[$c['key']] ?? null;

                switch ($c['type']) {
                    case 'date':
                        $serial = $v !== null && $v !== '' ? Date::stringToExcel(substr((string) $v, 0, 10)) : false;
                        if ($serial !== false) {
                            $ws->setCellValue($coord, $serial);
                        }
                        break;

                    case 'money':
                    case 'int':
                    case 'in':
                    case 'out':
                    case 'final':
                        $ws->setCellValue($coord, (float) $v);
                        break;

                    case 'center':
                        if (is_numeric($v) && $c['key'] === 'rank') {
                            $ws->setCellValue($coord, (int) $v);
                        } else {
                            $this->text($ws, $coord, (string) $v);
                        }
                        break;

                    default: // text, code, status
                        $this->text($ws, $coord, (string) $v);
                }
            }
        }
        $last = max($first, $r); // satu baris cadangan agar rentang gaya tidak terbalik saat data kosong

        if ($rows === []) {
            return [$first, $headerRow];
        }

        // Format, rata, dan gaya per kolom (sekali per kolom, bukan per sel)
        foreach ($cols as $i => $c) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $range  = "{$letter}{$first}:{$letter}{$last}";
            $style  = $ws->getStyle($range);

            match ($c['type']) {
                'date'   => $style->getNumberFormat()->setFormatCode(self::DATE) && $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER),
                'money'  => $style->getNumberFormat()->setFormatCode(self::MONEY),
                'int'    => $style->getNumberFormat()->setFormatCode(self::INTEGER),
                'in'     => $style->getNumberFormat()->setFormatCode('+#,##0;-#,##0;0') && $style->getFont()->getColor()->setRGB('0F8A5C'),
                'out'    => $style->getNumberFormat()->setFormatCode('-#,##0;-#,##0;0') && $style->getFont()->getColor()->setRGB('B91C1C'),
                'final'  => $style->getNumberFormat()->setFormatCode(self::INTEGER) && $style->getFont()->setBold(true),
                'center' => $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER),
                'status' => $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER),
                'code'   => $style->getFont()->setName('Consolas')->setSize(10),
                default  => null,
            };
        }

        // Garis tepi + belang-belang
        $ws->getStyle("A{$first}:{$lastCol}{$last}")->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        for ($x = $first + 1; $x <= $last; $x += 2) {
            $ws->getStyle("A{$x}:{$lastCol}{$x}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ZEBRA);
        }

        // Warna status per sel
        foreach ($cols as $i => $c) {
            if ($c['type'] !== 'status') {
                continue;
            }
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            for ($x = $first; $x <= $last; $x++) {
                $st = (string) $ws->getCell($letter . $x)->getValue();
                [$fg, $bg] = self::STATUS[$st] ?? ['475569', 'E2E8F0'];
                $cell = $ws->getStyle($letter . $x);
                $cell->getFont()->setBold(true)->getColor()->setRGB($fg);
                $cell->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            }
        }

        return [$first, $last];
    }

    private function label(Worksheet $ws, string $range, string $text): void
    {
        $this->text($ws, explode(':', $range)[0], $text);
        $ws->mergeCells($range);
        $ws->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function totalStyle(Worksheet $ws, string $range, ?string $numberFormat): void
    {
        $style = $ws->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF0FF');
        $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::BRAND);
        if ($numberFormat !== null) {
            $style->getNumberFormat()->setFormatCode($numberFormat);
        }
    }

    private function finishSheet(Worksheet $ws, string $lastCol, int $first, int $end): void
    {
        $headerRow = $first - 1;
        $ws->freezePane('A' . $first);
        if ($end >= $first) {
            $ws->setAutoFilter("A{$headerRow}:{$lastCol}{$end}");
        }
        $ws->setShowGridlines(false);

        $setup = $ws->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1)->setFitToHeight(0);
        $setup->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $ws->getPageMargins()->setTop(0.6)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);
        $ws->getHeaderFooter()->setOddFooter('&L&8Mini ERP - PT Nusantara Distribusi&R&8Halaman &P dari &N');
    }

    // ==================================================================
    // Sheet "Grafik"
    // ==================================================================

    private function graphBanner(Worksheet $ws, string $title, array $filter): void
    {
        $this->text($ws, 'A1', $title);
        $ws->mergeCells('A1:N1');
        $ws->getStyle('A1:N1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BRAND]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $ws->getRowDimension(1)->setRowHeight(32);
        $this->text($ws, 'A2', 'Periode: ' . self::dateLabel($filter['start']) . ' s.d. ' . self::dateLabel($filter['end']) . '   |   Grafik bersumber dari tabel ringkasan di sebelah kirinya.');
        $ws->getStyle('A2')->getFont()->setColor(self::rgb(self::MUTED))->setSize(10);
        $ws->setShowGridlines(false);

        $ws->getColumnDimension('A')->setWidth(26);
        foreach (['B', 'C', 'D'] as $c) {
            $ws->getColumnDimension($c)->setWidth(17);
        }
        $ws->getColumnDimension('E')->setWidth(3);
    }

    /**
     * Susun blok "tabel ringkasan (kiri) + grafik (kanan)" dari atas ke bawah.
     *
     * @param list<array{title:string,headers:list<string>,formats:list<?string>,rows:list<array>,chart:array}> $blocks
     */
    private function graphBlocks(Worksheet $ws, array $blocks): void
    {
        $row = 4;
        foreach ($blocks as $i => $b) {
            $n = count($b['rows']);
            if ($n === 0) {
                continue;
            }

            // Judul blok
            $this->text($ws, "A{$row}", $b['title']);
            $ws->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::BRAND);

            // Header tabel
            $hr = $row + 1;
            foreach ($b['headers'] as $c => $h) {
                $this->text($ws, Coordinate::stringFromColumnIndex($c + 1) . $hr, $h);
            }
            $lastLetter = Coordinate::stringFromColumnIndex(count($b['headers']));
            $ws->getStyle("A{$hr}:{$lastLetter}{$hr}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::INK]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ]);

            // Isi
            $first = $hr + 1;
            $r     = $hr;
            foreach ($b['rows'] as $rowData) {
                $r++;
                foreach ($rowData as $c => $v) {
                    $coord = Coordinate::stringFromColumnIndex($c + 1) . $r;
                    if ($c === 0) {
                        $this->text($ws, $coord, (string) $v);
                    } else {
                        $ws->setCellValue($coord, $v);
                    }
                }
            }
            $last = $r;

            foreach ($b['formats'] as $c => $f) {
                if ($f === null) {
                    continue;
                }
                $letter = Coordinate::stringFromColumnIndex($c + 1);
                $ws->getStyle("{$letter}{$first}:{$letter}{$last}")->getNumberFormat()
                    ->setFormatCode($f === 'money' ? self::MONEY : self::INTEGER);
            }
            $ws->getStyle("A{$first}:{$lastLetter}{$last}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
            ]);

            // Grafik di sebelah kanan, sejajar dengan awal blok
            $height = 19; // baris
            $this->addChart($ws, 'grafik' . ($i + 1), $b['chart'], $hr, $first, $last, 'F' . $row, 'N' . ($row + $height - 1));

            $row += max($height, $n + 4) + 1;
        }
    }

    /** @param array<string,mixed> $spec */
    private function addChart(Worksheet $ws, string $name, array $spec, int $headerRow, int $first, int $last, string $topLeft, string $bottomRight): void
    {
        $sheet = "'" . $ws->getTitle() . "'";
        $count = $last - $first + 1;
        $cat   = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheet}!\$A\${$first}:\$A\${$last}", null, $count)];

        $labels = [];
        $values = [];
        foreach ($spec['series'] as [$colIdx, $color]) {
            $letter   = Coordinate::stringFromColumnIndex($colIdx + 1);
            $labels[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheet}!\${$letter}\${$headerRow}", null, 1);

            $v = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheet}!\${$letter}\${$first}:\${$letter}\${$last}", null, $count);
            if ($spec['type'] === 'pie') {
                $colors = $spec['point_colors'] ?? [];
                $v->setFillColor(array_map(
                    static fn ($k) => $colors[$k] ?? self::PALETTE[$k % count(self::PALETTE)],
                    range(0, $count - 1)
                ));
            } elseif ($color !== null) {
                $v->setFillColor($color);
            }
            $values[] = $v;
        }
        $order = range(0, count($values) - 1);

        if ($spec['type'] === 'pie') {
            $series = new DataSeries(DataSeries::TYPE_DONUTCHART, null, $order, $labels, $cat, $values);
            $layout = new Layout();
            $layout->setShowVal(false)->setShowCatName(false)->setShowPercent(true)->setShowLeaderLines(true);
            $plot   = new PlotArea($layout, [$series]);
            $legend = new Legend(Legend::POSITION_RIGHT, null, false);
            $chart  = new Chart($name, new Title($spec['title']), $legend, $plot, true, 'gap');
        } else {
            $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, $order, $labels, $cat, $values);
            $series->setPlotDirection(DataSeries::DIRECTION_COL);
            $plot   = new PlotArea(null, [$series]);
            $legend = ($spec['legend'] ?? true) ? new Legend(Legend::POSITION_BOTTOM, null, false) : null;
            $chart  = new Chart($name, new Title($spec['title']), $legend, $plot, true, 'gap', null, new Title($spec['y_title'] ?? ''));
            $chart->getChartAxisY()->setAxisNumberProperties($spec['format'] ?? self::INTEGER);
        }

        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);
        $ws->addChart($chart);
    }

    // ==================================================================
    // Util
    // ==================================================================

    /** Teks selalu string eksplisit: tidak pernah dianggap rumus (mencegah formula injection). */
    private function text(Worksheet $ws, string $coord, string $value): void
    {
        $ws->setCellValueExplicit($coord, $value, DataType::TYPE_STRING);
    }

    private static function rgb(string $hex): \PhpOffice\PhpSpreadsheet\Style\Color
    {
        return new \PhpOffice\PhpSpreadsheet\Style\Color($hex);
    }

    private static function dateLabel(string $ymd): string
    {
        $d = new DateTimeImmutable($ymd);

        return $d->format('d') . ' ' . self::MONTHS[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    /** Tulis workbook ke string biner .xlsx (grafik ikut ditulis). */
    public static function toBinary(Spreadsheet $wb): string
    {
        $writer = IOFactory::createWriter($wb, 'Xlsx');
        $writer->setIncludeCharts(true);

        $tmp = tempnam(sys_get_temp_dir(), 'rpt');
        try {
            $writer->save($tmp);
            $data = file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
        $wb->disconnectWorksheets();

        if ($data === false || $data === '') {
            throw new \RuntimeException('File Excel kosong setelah ditulis.');
        }

        return $data;
    }
}
