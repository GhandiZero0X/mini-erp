<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Kumpulan query statistik untuk dashboard.
 * Hanya menjalankan query untuk widget yang diizinkan ($can), jadi peran
 * dengan hak akses sedikit tidak membebani database.
 */
class DashboardStats
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const MONTHS_FULL = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /**
     * Filter periode dashboard. 'unit' dipakai langsung di SQL (date_trunc / interval),
     * jadi HANYA boleh berasal dari konstanta ini, tidak pernah dari input pengguna.
     * 'buckets' = jumlah titik pada grafik tren (periode berjalan = titik terakhir).
     * Minggu dimulai hari Senin (perilaku date_trunc('week') di PostgreSQL).
     */
    private const PERIODS = [
        'day'   => ['unit' => 'day',   'buckets' => 14, 'label' => 'Hari Ini',   'prev' => 'kemarin',     'unit_name' => 'hari',   'span' => '14 hari terakhir'],
        'week'  => ['unit' => 'week',  'buckets' => 8,  'label' => 'Minggu Ini', 'prev' => 'minggu lalu', 'unit_name' => 'minggu', 'span' => '8 minggu terakhir'],
        'month' => ['unit' => 'month', 'buckets' => 6,  'label' => 'Bulan Ini',  'prev' => 'bulan lalu',  'unit_name' => 'bulan',  'span' => '6 bulan terakhir'],
        'year'  => ['unit' => 'year',  'buckets' => 5,  'label' => 'Tahun Ini',  'prev' => 'tahun lalu',  'unit_name' => 'tahun',  'span' => '5 tahun terakhir'],
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string,bool> $can  sales, purchase, products, customers, users, top, locked,
     *                                 approve, rep_purchase, rep_sales, rep_stock,
     *                                 wh_receipt, wh_delivery, wh_stock, wh_move,
     *                                 sl_create, sl_delivery, sl_customer, sl_stock,
     *                                 pu_create, pu_draft, pu_receipt, pu_supplier, suppliers
     * @param array<string,mixed> $opts topLimit (jumlah produk terlaris, default 5),
     *                                  period (day|week|month|year, default month; nilai lain -> month)
     */
    public function collect(array $can, array $opts = []): array
    {
        $topLimit = max(1, (int) ($opts['topLimit'] ?? 5));

        // Periode terpilih (whitelist). Nilai tak dikenal jatuh ke 'month'.
        $key = (string) ($opts['period'] ?? 'month');
        $key = isset(self::PERIODS[$key]) ? $key : 'month';

        $spec    = self::PERIODS[$key];
        $unit    = $spec['unit'];
        $buckets = $spec['buckets'];
        $range   = $this->periodRange($unit); // ['start' => 'Y-m-d', 'end' => 'Y-m-d' (eksklusif)]

        $out = [
            'periodInfo'    => [
                'key'        => $key,
                'label'      => $spec['label'],
                'prev'       => $spec['prev'],
                'unit_name'  => $spec['unit_name'],
                'span'       => $spec['span'],
                'range_text' => $this->rangeText($unit, $range),
            ],
            'kpi'           => [],
            'trend'         => null,
            'topProducts'   => [],
            'topCategories' => [],
            'lockedUsers'   => [],
            'topLimit'      => $topLimit,
            'pendingPOs'    => [],
            'repPurchase'   => [],
            'repSales'      => [],
            'repStock'      => [],
            'whReceipts'    => [],
            'whDeliveries'  => [],
            'whLowStock'    => [],
            'whHighStock'   => [],
            'whFlow'        => ['labels' => [], 'in' => [], 'out' => []],
            'whMovements'   => [],
            'slDeliveries'  => [],
            'slStock'       => ['total' => 0, 'rows' => []],
            'slCustomers'   => [],
            'puDrafts'      => [],
            'puReceipts'    => [],
            'puSuppliers'   => [],
        ];

        // ---- Tren N periode terakhir (sekaligus dipakai untuk kartu + persentase naik/turun) ----
        $sales    = ! empty($can['sales'])
            ? $this->series('sales_orders', 'so_date', "t.status <> 'Dibatalkan'", $unit, $buckets)
            : null;
        $purchase = ! empty($can['purchase'])
            ? $this->series('purchase_orders', 'po_date', "t.status = 'Approved'", $unit, $buckets)
            : null;

        $base = $sales ?? $purchase;
        if ($base !== null) {
            $out['trend'] = [
                'labels'   => array_column($base, 'label'),
                'sales'    => $sales !== null ? array_column($sales, 'total') : null,
                'purchase' => $purchase !== null ? array_column($purchase, 'total') : null,
            ];
        }

        if ($sales !== null) {
            [$out['kpi']['sales'], $out['kpi']['sales_delta']] = $this->currentAndDelta($sales);
        }
        if ($purchase !== null) {
            [$out['kpi']['purchase'], $out['kpi']['purchase_delta']] = $this->currentAndDelta($purchase);
        }

        // ---- Hitungan sederhana ----
        if (! empty($can['products'])) {
            $out['kpi']['products'] = $this->db->table('products')->countAllResults();
        }
        if (! empty($can['customers'])) {
            $out['kpi']['customers'] = $this->db->table('customers')->countAllResults();
        }
        if (! empty($can['users'])) {
            $out['kpi']['users'] = $this->db->table('users')->where('is_active', true)->countAllResults();
        }

        // ---- Produk & kategori terlaris (periode terpilih, berdasarkan jumlah unit terjual) ----
        if (! empty($can['top'])) {
            $out['topProducts']   = $this->topProducts($topLimit, $range);
            $out['topCategories'] = $this->topCategories(6, $range);
        }

        // ---- Akun yang sedang terkunci (NFR-17) ----
        if (! empty($can['locked'])) {
            $out['lockedUsers'] = $this->db->query(
                'SELECT id, full_name, username, failed_login_count, locked_until
                   FROM users
                  WHERE locked_until IS NOT NULL AND locked_until > now()
               ORDER BY locked_until DESC'
            )->getResultArray();
        }

        // ---- Panel Manajer ----
        if (! empty($can['approve'])) {
            $out['pendingPOs'] = $this->pendingPurchaseOrders();
        }
        if (! empty($can['rep_purchase'])) {
            $out['repPurchase'] = $this->purchaseReport($range);
        }
        if (! empty($can['rep_sales'])) {
            $out['repSales'] = $this->salesReport($range);
        }
        if (! empty($can['rep_stock'])) {
            $out['repStock'] = $this->stockReport($range);
        }

        // ---- Panel Staff Gudang ----
        if (! empty($can['wh_receipt'])) {
            $all                       = $this->pendingReceipts();
            $out['kpi']['wh_receipts'] = count($all);
            $out['whReceipts']         = array_slice($all, 0, 20);
        }
        if (! empty($can['wh_delivery'])) {
            $all                         = $this->pendingDeliveries();
            $out['kpi']['wh_deliveries'] = count($all);
            $out['whDeliveries']         = array_slice($all, 0, 20);
        }
        if (! empty($can['wh_stock'])) {
            $all                    = $this->lowStock();
            $out['kpi']['wh_low']   = count($all);
            $out['whLowStock']      = array_slice($all, 0, 10);
            $out['whHighStock']     = $this->highStock(10);
        }
        if (! empty($can['wh_move'])) {
            $tot                   = $this->movementTotals($range);
            $out['kpi']['wh_in']   = $tot['qty_in'];
            $out['kpi']['wh_out']  = $tot['qty_out'];
            $out['whFlow']         = $this->movementFlow($unit, $buckets);
            $out['whMovements']         = $this->recentMovements(10);
        }

        // ---- Panel Sales ----
        if (! empty($can['sl_customer'])) {
            // jumlah SO aktif pada periode (repSales sudah terisi di atas), di luar yang dibatalkan
            $out['kpi']['sl_so'] = count(array_filter(
                $out['repSales'],
                static fn (array $r): bool => $r['status'] !== 'Dibatalkan'
            ));
            $out['slCustomers'] = $this->topCustomers(5, $range);
        }
        if (! empty($can['sl_delivery'])) {
            $all                    = $this->pendingDeliveries();
            $out['kpi']['sl_open']  = count($all);
            $out['slDeliveries']    = array_slice($all, 0, 20);
        }
        if (! empty($can['sl_stock'])) {
            $out['slStock']        = $this->salesStock(10);
            $out['kpi']['sl_low']  = $out['slStock']['total'];
        }

        // ---- Panel Purchasing ----
        if (! empty($can['suppliers'])) {
            $out['kpi']['suppliers'] = $this->db->table('suppliers')->countAllResults();
        }
        if (! empty($can['pu_supplier'])) {
            // jumlah PO aktif pada periode (repPurchase sudah terisi di atas), di luar yang dibatalkan
            $out['kpi']['pu_po'] = count(array_filter(
                $out['repPurchase'],
                static fn (array $r): bool => $r['status'] !== 'Dibatalkan'
            ));
            $out['puSuppliers'] = $this->topSuppliers(5, $range);
        }
        if (! empty($can['pu_draft'])) {
            $all                     = $this->pendingPurchaseOrders();
            $out['kpi']['pu_draft']  = count($all);
            $out['puDrafts']         = array_slice($all, 0, 20);
        }
        if (! empty($can['pu_receipt'])) {
            $all                    = $this->pendingReceipts();
            $out['kpi']['pu_open']  = count($all);
            $out['puReceipts']      = array_slice($all, 0, 20);
        }

        return $out;
    }

    /**
     * Total per periode untuk $buckets periode terakhir (hari/minggu/bulan/tahun). generate_series
     * memastikan periode tanpa transaksi tetap muncul dengan nilai 0 (garis grafik tidak bolong).
     * $table/$dateCol/$statusCond/$unit/$buckets adalah konstanta internal, BUKAN input pengguna.
     */
    private function series(string $table, string $dateCol, string $statusCond, string $unit, int $buckets): array
    {
        $back = $buckets - 1;
        $sql  = "SELECT to_char(m.bucket, 'YYYY-MM-DD') AS d,
                        COALESCE(SUM(t.total_amount), 0) AS total
                   FROM generate_series(
                            date_trunc('{$unit}', CURRENT_DATE) - (interval '1 {$unit}' * {$back}),
                            date_trunc('{$unit}', CURRENT_DATE),
                            interval '1 {$unit}') AS m(bucket)
              LEFT JOIN {$table} t
                     ON t.{$dateCol} >= m.bucket
                    AND t.{$dateCol} <  m.bucket + interval '1 {$unit}'
                    AND {$statusCond}
               GROUP BY m.bucket
               ORDER BY m.bucket";

        $rows = [];
        foreach ($this->db->query($sql)->getResultArray() as $r) {
            $rows[] = [
                'label' => $this->bucketLabel($r['d'], $unit),
                'total' => (float) $r['total'],
            ];
        }

        return $rows;
    }

    /** Label sumbu-x: hari "26 Nov", minggu "Mgg 23 Nov" (tanggal Senin), bulan "Nov 26", tahun "2026". */
    private function bucketLabel(string $ymd, string $unit): string
    {
        $y = substr($ymd, 0, 4);
        $m = (int) substr($ymd, 5, 2) - 1;
        $d = (int) substr($ymd, 8, 2);

        return match ($unit) {
            'day'   => $d . ' ' . self::MONTHS[$m],
            'week'  => 'Mgg ' . $d . ' ' . self::MONTHS[$m],
            'month' => self::MONTHS[$m] . ' ' . substr($y, 2, 2),
            default => $y,
        };
    }

    /**
     * Rentang periode berjalan (tanggal mulai & akhir EKSKLUSIF) dihitung oleh PostgreSQL,
     * supaya konsisten dengan CURRENT_DATE / zona waktu sesi yang dipakai query lain.
     *
     * @return array{start: string, end: string}
     */
    private function periodRange(string $unit): array
    {
        $r = $this->db->query(
            "SELECT to_char(date_trunc('{$unit}', CURRENT_DATE), 'YYYY-MM-DD')                         AS s,
                    to_char(date_trunc('{$unit}', CURRENT_DATE) + interval '1 {$unit}', 'YYYY-MM-DD') AS e"
        )->getRowArray();

        return ['start' => $r['s'], 'end' => $r['e']];
    }

    /** Teks rentang untuk ditampilkan di UI, mis. "26 Nov 2026", "23 Nov - 29 Nov 2026", "November 2026", "2026". */
    private function rangeText(string $unit, array $range): string
    {
        $s   = new \DateTimeImmutable($range['start']);
        $e   = (new \DateTimeImmutable($range['end']))->modify('-1 day');
        $fmt = static fn (\DateTimeImmutable $d): string => (int) $d->format('j') . ' ' . self::MONTHS[(int) $d->format('n') - 1];

        return match ($unit) {
            'day'   => $fmt($s) . ' ' . $s->format('Y'),
            'week'  => $fmt($s) . ' - ' . $fmt($e) . ' ' . $e->format('Y'),
            'month' => self::MONTHS_FULL[(int) $s->format('n') - 1] . ' ' . $s->format('Y'),
            default => $s->format('Y'),
        };
    }

    /** @return array{0: float, 1: float|null} total bulan ini & persentase perubahan vs bulan lalu */
    private function currentAndDelta(array $series): array
    {
        $cur  = (float) end($series)['total'];
        $prev = (float) $series[count($series) - 2]['total'];

        return [$cur, $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null];
    }

    /** @param array{start: string, end: string} $range */
    private function topProducts(int $limit, array $range): array
    {
        return $this->db->query(
            "SELECT p.name, SUM(i.quantity) AS qty
               FROM sales_order_items i
               JOIN sales_orders s ON s.id = i.sales_order_id
               JOIN products p     ON p.id = i.product_id
              WHERE s.status <> 'Dibatalkan'
                AND s.so_date >= ?::date
                AND s.so_date <  ?::date
           GROUP BY p.id, p.name
           ORDER BY qty DESC, p.name
              LIMIT ?",
            [$range['start'], $range['end'], $limit]
        )->getResultArray();
    }

    /** @param array{start: string, end: string} $range */
    private function topCategories(int $limit, array $range): array
    {
        return $this->db->query(
            "SELECT c.name, SUM(i.quantity) AS qty
               FROM sales_order_items i
               JOIN sales_orders s ON s.id = i.sales_order_id
               JOIN products p     ON p.id = i.product_id
               JOIN categories c   ON c.id = p.category_id
              WHERE s.status <> 'Dibatalkan'
                AND s.so_date >= ?::date
                AND s.so_date <  ?::date
           GROUP BY c.id, c.name
           ORDER BY qty DESC, c.name
              LIMIT ?",
            [$range['start'], $range['end'], $limit]
        )->getResultArray();
    }

    /* =====================================================================
     * PANEL MANAJER
     * Laporan memakai periode yang dipilih (hari / minggu / bulan / tahun berjalan).
     * ===================================================================== */

    /** PO berstatus Draft yang menunggu persetujuan, yang paling lama menunggu di atas. */
    private function pendingPurchaseOrders(): array
    {
        return $this->db->query(
            "SELECT po.id, po.po_number, po.po_date, po.total_amount,
                    (CURRENT_DATE - po.po_date) AS waiting_days,
                    s.name      AS supplier_name,
                    u.full_name AS creator,
                    (SELECT COUNT(*) FROM purchase_order_items i
                      WHERE i.purchase_order_id = po.id) AS item_count
               FROM purchase_orders po
               JOIN suppliers s ON s.id = po.supplier_id
               JOIN users u     ON u.id = po.created_by
              WHERE po.status = 'Draft'
           ORDER BY po.po_date ASC, po.id ASC
              LIMIT 50"
        )->getResultArray();
    }

    /**
     * Daftar PO pada periode terpilih (semua status), terbaru di atas.
     *
     * @param array{start: string, end: string} $range
     */
    private function purchaseReport(array $range): array
    {
        return $this->db->query(
            "SELECT po.id, po.po_number, po.po_date, po.status, po.total_amount,
                    s.name AS party_name
               FROM purchase_orders po
               JOIN suppliers s ON s.id = po.supplier_id
              WHERE po.po_date >= ?::date
                AND po.po_date <  ?::date
           ORDER BY po.po_date DESC, po.po_number DESC",
            [$range['start'], $range['end']]
        )->getResultArray();
    }

    /**
     * Daftar SO pada periode terpilih (semua status), terbaru di atas.
     *
     * @param array{start: string, end: string} $range
     */
    private function salesReport(array $range): array
    {
        return $this->db->query(
            "SELECT so.id, so.so_number, so.so_date, so.status, so.total_amount,
                    c.name AS party_name
               FROM sales_orders so
               JOIN customers c ON c.id = so.customer_id
              WHERE so.so_date >= ?::date
                AND so.so_date <  ?::date
           ORDER BY so.so_date DESC, so.so_number DESC",
            [$range['start'], $range['end']]
        )->getResultArray();
    }

    /**
     * Rekap stok periode terpilih per produk, dihitung dari stock_movements:
     *   Stok Awal  = total mutasi sebelum awal periode
     *   Masuk      = jumlah mutasi positif dalam periode
     *   Keluar     = jumlah mutasi negatif dalam periode (bernilai negatif)
     *   Stok Akhir = Awal + Masuk + Keluar (dihitung di view/controller)
     * Produk tanpa mutasi tetap muncul dengan nilai 0.
     */
    /** @param array{start: string, end: string} $range */
    private function stockReport(array $range): array
    {
        $rows = $this->db->query(
            "SELECT p.code, p.name,
                    c.name AS category_name,
                    un.code AS unit_code,
                    COALESCE(SUM(CASE WHEN m.movement_date < d.period_start
                                      THEN m.quantity_change END), 0) AS opening,
                    COALESCE(SUM(CASE WHEN m.movement_date >= d.period_start
                                       AND m.quantity_change > 0
                                      THEN m.quantity_change END), 0) AS qty_in,
                    COALESCE(SUM(CASE WHEN m.movement_date >= d.period_start
                                       AND m.quantity_change < 0
                                      THEN m.quantity_change END), 0) AS qty_out
               FROM products p
               JOIN categories c ON c.id  = p.category_id
               JOIN units un     ON un.id = p.unit_id
         CROSS JOIN (SELECT ?::date AS period_start, ?::date AS period_end) d
          LEFT JOIN stock_movements m
                 ON m.product_id = p.id
                AND m.movement_date < d.period_end
           GROUP BY p.id, p.code, p.name, c.name, un.code
           ORDER BY p.name",
            [$range['start'], $range['end']]
        )->getResultArray();

        foreach ($rows as &$r) {
            $r['opening'] = (int) $r['opening'];
            $r['qty_in']  = (int) $r['qty_in'];
            $r['qty_out'] = (int) $r['qty_out'];
            $r['closing'] = $r['opening'] + $r['qty_in'] + $r['qty_out'];
        }
        unset($r);

        return $rows;
    }

    /* =====================================================================
     * PANEL PURCHASING
     * ===================================================================== */

    /**
     * Supplier dengan nilai PO Approved terbesar pada periode terpilih (sejalan dengan kartu
     * "Pembelian", yang hanya menghitung PO Approved). 'pct' = persentase terhadap supplier teratas.
     *
     * @param array{start: string, end: string} $range
     */
    private function topSuppliers(int $limit, array $range): array
    {
        $rows = $this->db->query(
            "SELECT s.id, s.name,
                    COUNT(*)              AS po_count,
                    SUM(po.total_amount)  AS total
               FROM purchase_orders po
               JOIN suppliers s ON s.id = po.supplier_id
              WHERE po.status = 'Approved'
                AND po.po_date >= ?::date
                AND po.po_date <  ?::date
           GROUP BY s.id, s.name
           ORDER BY total DESC, s.name
              LIMIT ?",
            [$range['start'], $range['end'], $limit]
        )->getResultArray();

        $max = $rows === [] ? 0.0 : (float) $rows[0]['total'];
        foreach ($rows as &$r) {
            $r['po_count'] = (int) $r['po_count'];
            $r['total']    = (float) $r['total'];
            $r['pct']      = $max > 0 ? (int) round($r['total'] / $max * 100) : 0;
        }
        unset($r);

        return $rows;
    }

    /* =====================================================================
     * PANEL SALES
     * ===================================================================== */

    /**
     * Customer dengan nilai SO terbesar pada periode terpilih (SO dibatalkan tidak dihitung).
     * 'pct' = persentase terhadap customer teratas, untuk panjang bar di UI.
     *
     * @param array{start: string, end: string} $range
     */
    private function topCustomers(int $limit, array $range): array
    {
        $rows = $this->db->query(
            "SELECT c.id, c.name,
                    COUNT(*)            AS so_count,
                    SUM(s.total_amount) AS total
               FROM sales_orders s
               JOIN customers c ON c.id = s.customer_id
              WHERE s.status <> 'Dibatalkan'
                AND s.so_date >= ?::date
                AND s.so_date <  ?::date
           GROUP BY c.id, c.name
           ORDER BY total DESC, c.name
              LIMIT ?",
            [$range['start'], $range['end'], $limit]
        )->getResultArray();

        $max = $rows === [] ? 0.0 : (float) $rows[0]['total'];
        foreach ($rows as &$r) {
            $r['so_count'] = (int) $r['so_count'];
            $r['total']    = (float) $r['total'];
            $r['pct']      = $max > 0 ? (int) round($r['total'] / $max * 100) : 0;
        }
        unset($r);

        return $rows;
    }

    /**
     * Ketersediaan stok untuk Sales: produk yang habis atau sudah mencapai batas minimum
     * (stok terendah di atas), lengkap dengan harga jual. 'total' = jumlah seluruh produk
     * yang memenuhi syarat, 'rows' dibatasi $limit.
     *
     * @return array{total: int, rows: array}
     */
    private function salesStock(int $limit): array
    {
        $rows = $this->db->query(
            "SELECT p.id, p.code, p.name, un.code AS unit_code, p.min_stock, p.selling_price,
                    COALESCE(sb.quantity, 0) AS current_stock,
                    COUNT(*) OVER ()         AS total_rows
               FROM products p
               JOIN units un ON un.id = p.unit_id
          LEFT JOIN stock_balances sb ON sb.product_id = p.id
              WHERE COALESCE(sb.quantity, 0) <= p.min_stock
           ORDER BY COALESCE(sb.quantity, 0) ASC, p.name
              LIMIT ?",
            [$limit]
        )->getResultArray();

        return [
            'total' => $rows === [] ? 0 : (int) $rows[0]['total_rows'],
            'rows'  => $rows,
        ];
    }

    /* =====================================================================
     * PANEL STAFF GUDANG
     * Sisa unit selalu dihitung dari selisih item (qty dipesan - qty tercatat
     * di dokumen yang masih Aktif), karena PO/SO tidak punya status "selesai".
     * ===================================================================== */

    /** PO Approved yang barangnya belum diterima penuh. Paling lama menunggu di atas. */
    private function pendingReceipts(): array
    {
        $rows = $this->db->query(
            "SELECT po.id, po.po_number, po.po_date,
                    s.name AS party_name,
                    SUM(x.quantity)  AS ordered_qty,
                    SUM(x.remaining) AS remaining_qty,
                    COUNT(*) FILTER (WHERE x.remaining > 0) AS open_items
               FROM purchase_orders po
               JOIN suppliers s ON s.id = po.supplier_id
               JOIN (
                    SELECT i.purchase_order_id, i.quantity,
                           GREATEST(i.quantity - COALESCE((
                               SELECT SUM(gi.quantity_received)
                                 FROM goods_receipt_items gi
                                 JOIN goods_receipts g ON g.id = gi.goods_receipt_id
                                WHERE gi.purchase_order_item_id = i.id
                                  AND g.status = 'Aktif'), 0), 0) AS remaining
                      FROM purchase_order_items i
                    ) x ON x.purchase_order_id = po.id
              WHERE po.status = 'Approved'
           GROUP BY po.id, po.po_number, po.po_date, s.name
             HAVING SUM(x.remaining) > 0
           ORDER BY po.po_date ASC, po.id ASC"
        )->getResultArray();

        return $this->castQueue($rows);
    }

    /**
     * SO Open yang belum terkirim penuh. short_items = jumlah item yang sisa
     * pesanannya melebihi stok saat ini (stok tidak boleh negatif, BR-07).
     */
    private function pendingDeliveries(): array
    {
        $rows = $this->db->query(
            "SELECT so.id, so.so_number, so.so_date,
                    c.name AS party_name,
                    SUM(x.quantity)  AS ordered_qty,
                    SUM(x.remaining) AS remaining_qty,
                    COUNT(*) FILTER (WHERE x.remaining > 0)            AS open_items,
                    COUNT(*) FILTER (WHERE x.remaining > x.stock_qty)  AS short_items
               FROM sales_orders so
               JOIN customers c ON c.id = so.customer_id
               JOIN (
                    SELECT i.sales_order_id, i.quantity,
                           GREATEST(i.quantity - COALESCE((
                               SELECT SUM(di.quantity_delivered)
                                 FROM delivery_items di
                                 JOIN deliveries d ON d.id = di.delivery_id
                                WHERE di.sales_order_item_id = i.id
                                  AND d.status = 'Aktif'), 0), 0) AS remaining,
                           COALESCE(sb.quantity, 0) AS stock_qty
                      FROM sales_order_items i
                 LEFT JOIN stock_balances sb ON sb.product_id = i.product_id
                    ) x ON x.sales_order_id = so.id
              WHERE so.status = 'Open'
           GROUP BY so.id, so.so_number, so.so_date, c.name
             HAVING SUM(x.remaining) > 0
           ORDER BY so.so_date ASC, so.id ASC"
        )->getResultArray();

        return $this->castQueue($rows);
    }

    private function castQueue(array $rows): array
    {
        foreach ($rows as &$r) {
            $r['ordered_qty']    = (int) $r['ordered_qty'];
            $r['remaining_qty']  = (int) $r['remaining_qty'];
            $r['open_items']     = (int) $r['open_items'];
            $r['short_items']    = (int) ($r['short_items'] ?? 0);
            $r['received_pct']   = $r['ordered_qty'] > 0
                ? (int) round(($r['ordered_qty'] - $r['remaining_qty']) / $r['ordered_qty'] * 100)
                : 0;
        }
        unset($r);

        return $rows;
    }

    /** Produk yang stoknya di bawah minimum, kekurangan terbesar di atas. */
    private function lowStock(): array
    {
        return $this->db->query(
            "SELECT p.id, p.code, p.name, un.code AS unit_code, p.min_stock,
                    COALESCE(sb.quantity, 0) AS current_stock
               FROM products p
               JOIN units un ON un.id = p.unit_id
          LEFT JOIN stock_balances sb ON sb.product_id = p.id
              WHERE COALESCE(sb.quantity, 0) < p.min_stock
           ORDER BY (p.min_stock - COALESCE(sb.quantity, 0)) DESC, p.name"
        )->getResultArray();
    }

    /** Produk yang stoknya sudah mencapai 90% batas maksimum (atau lebih). */
    private function highStock(int $limit): array
    {
        return $this->db->query(
            "SELECT p.id, p.code, p.name, un.code AS unit_code, p.max_stock,
                    COALESCE(sb.quantity, 0) AS current_stock
               FROM products p
               JOIN units un ON un.id = p.unit_id
          LEFT JOIN stock_balances sb ON sb.product_id = p.id
              WHERE p.max_stock IS NOT NULL AND p.max_stock > 0
                AND COALESCE(sb.quantity, 0) >= p.max_stock * 0.9
           ORDER BY COALESCE(sb.quantity, 0)::numeric / p.max_stock DESC, p.name
              LIMIT ?",
            [$limit]
        )->getResultArray();
    }

    /**
     * Total unit masuk & keluar pada periode terpilih.
     *
     * @param array{start: string, end: string} $range
     * @return array{qty_in:int, qty_out:int}
     */
    private function movementTotals(array $range): array
    {
        $r = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN quantity_change > 0 THEN quantity_change END), 0)  AS qty_in,
                    COALESCE(-SUM(CASE WHEN quantity_change < 0 THEN quantity_change END), 0) AS qty_out
               FROM stock_movements
              WHERE movement_date >= ?::date
                AND movement_date <  ?::date",
            [$range['start'], $range['end']]
        )->getRowArray();

        return ['qty_in' => (int) ($r['qty_in'] ?? 0), 'qty_out' => (int) ($r['qty_out'] ?? 0)];
    }

    /**
     * Unit masuk vs keluar per hari/minggu/bulan/tahun untuk $buckets periode terakhir
     * (periode tanpa mutasi = 0). $unit/$buckets konstanta internal, BUKAN input pengguna.
     */
    private function movementFlow(string $unit, int $buckets): array
    {
        $back = $buckets - 1;
        $rows = $this->db->query(
            "SELECT to_char(g.bucket, 'YYYY-MM-DD') AS d,
                    COALESCE(SUM(CASE WHEN m.quantity_change > 0 THEN m.quantity_change END), 0)  AS qty_in,
                    COALESCE(-SUM(CASE WHEN m.quantity_change < 0 THEN m.quantity_change END), 0) AS qty_out
               FROM generate_series(
                        date_trunc('{$unit}', now()) - (interval '1 {$unit}' * {$back}),
                        date_trunc('{$unit}', now()),
                        interval '1 {$unit}') AS g(bucket)
          LEFT JOIN stock_movements m
                 ON m.movement_date >= g.bucket
                AND m.movement_date <  g.bucket + interval '1 {$unit}'
           GROUP BY g.bucket
           ORDER BY g.bucket"
        )->getResultArray();

        $flow = ['labels' => [], 'in' => [], 'out' => []];
        foreach ($rows as $r) {
            $flow['labels'][] = $this->bucketLabel($r['d'], $unit);
            $flow['in'][]     = (int) $r['qty_in'];
            $flow['out'][]    = (int) $r['qty_out'];
        }

        return $flow;
    }

    /** Mutasi stok terbaru lintas produk. */
    private function recentMovements(int $limit): array
    {
        return $this->db->query(
            "SELECT m.movement_type, m.quantity_change, m.movement_date,
                    m.reference_number, p.code, p.name
               FROM stock_movements m
               JOIN products p ON p.id = m.product_id
           ORDER BY m.movement_date DESC, m.id DESC
              LIMIT ?",
            [$limit]
        )->getResultArray();
    }
}