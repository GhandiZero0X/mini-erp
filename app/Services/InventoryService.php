<?php

namespace App\Services;

use App\Models\StockListModel;
use App\Models\StockMovementModel;

/**
 * Logika modul Inventory (UC-15 & UC-16). Read-only; tidak menyentuh stok/movement.
 */
class InventoryService
{
    private StockListModel $stocks;
    private StockMovementModel $movements;
    private array $msg;

    public function __construct(
        ?StockListModel $stocks = null,
        ?StockMovementModel $movements = null,
        ?\Config\Inventory $cfg = null
    ) {
        $this->stocks    = $stocks    ?? new StockListModel();
        $this->movements = $movements ?? new StockMovementModel();
        $this->msg       = ($cfg ?? config('Inventory'))->messages;
    }

    public function stockList(string $search, int $categoryId): array
    {
        $rows = $this->stocks->listWithStock($search, $categoryId, 10);

        return [
            'rows'       => $rows,
            'pager'      => $this->stocks->pager,
            'categories' => $this->stocks->categoryOptions(),
        ];
    }

    /**
     * @param array{product_id?:int|string,start?:string,end?:string} $filter
     */
    public function movementList(array $filter): array
    {
        $in = self::clean($filter);

        // Default periode = bulan berjalan bila tidak diisi
        if ($in['start'] === '') { $in['start'] = date('Y-m-01'); }
        if ($in['end']   === '') { $in['end']   = date('Y-m-t'); }

        $errors = self::validate($in, $this->msg);

        $rows  = [];
        $pager = null;
        if ($errors === []) {
            $rows  = $this->movements->listFiltered(
                $in['product_id'] > 0 ? $in['product_id'] : null,
                $in['start'],
                $in['end'],
                10
            );
            $pager = $this->movements->pager;
        }

        return [
            'rows'     => $rows,
            'pager'    => $pager,
            'errors'   => $errors,
            'filter'   => $in,
            'products' => $this->movements->productOptions(),
        ];
    }

    // ---------- Pure helpers ----------

    public static function clean(array $f): array
    {
        return [
            'product_id' => (int) ($f['product_id'] ?? 0),
            'start'      => trim((string) ($f['start'] ?? '')),
            'end'        => trim((string) ($f['end']   ?? '')),
        ];
    }

    /** @return array<string,string> */
    public static function validate(array $in, array $msg): array
    {
        $e = [];

        if ($in['start'] !== '' && ! self::isValidDate($in['start'])) {
            $e['start'] = $msg['date_invalid'];
        }
        if ($in['end'] !== '' && ! self::isValidDate($in['end'])) {
            $e['end'] = $msg['date_invalid'];
        }
        if (! isset($e['start']) && ! isset($e['end']) && $in['start'] !== '' && $in['end'] !== ''
            && strcmp($in['start'], $in['end']) > 0) {
            $e['period'] = $msg['period_invalid'];   // VR-14
        }
        return $e;
    }

    private static function isValidDate(string $s): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $s);
        return $d !== false && $d->format('Y-m-d') === $s;
    }
}