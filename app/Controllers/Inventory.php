<?php

namespace App\Controllers;

use App\Services\InventoryService;

/**
 * UC-15 Stok Produk (UI-14) & UC-16 Histori Mutasi Stok (UI-15).
 * Hak akses di Routes.php: 'stock' & 'stock_movement' (level R, read-only).
 */
class Inventory extends BaseController
{
    private ?InventoryService $service = null;
    private function service(): InventoryService
    {
        return $this->service ??= new InventoryService();
    }

    /** GET /stock?q=...&category_id=...&page=... */
    public function stock()
    {
        $search     = trim((string) $this->request->getGet('q'));
        $categoryId = (int) $this->request->getGet('category_id');
        $data       = $this->service()->stockList($search, $categoryId);

        // Halaman di luar jangkauan → mundur ke halaman terakhir
        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last   = max(1, $data['pager']->getPageCount());
            $params = array_filter(
                ['q' => $search, 'category_id' => $categoryId ?: null, 'page' => $last > 1 ? $last : null],
                static fn($v) => $v !== null && $v !== ''
            );
            return redirect()->to('/stock' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('inventory/stock', [
            'title'      => 'Stok Produk',
            'rows'       => $data['rows'],
            'pager'      => $data['pager'],
            'categories' => $data['categories'],
            'search'     => $search,
            'categoryId' => $categoryId,
        ]);
    }

    /** GET /stock-movements?product_id=...&start=...&end=...&page=... */
    public function movements()
    {
        $filter = [
            'product_id' => $this->request->getGet('product_id'),
            'start'      => $this->request->getGet('start'),
            'end'        => $this->request->getGet('end'),
        ];
        $data = $this->service()->movementList($filter);

        // Halaman di luar jangkauan → mundur ke halaman terakhir
        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1 && $data['pager'] !== null) {
            $last   = max(1, $data['pager']->getPageCount());
            $params = array_filter([
                'product_id' => $data['filter']['product_id'] ?: null,
                'start'      => $data['filter']['start'] !== '' ? $data['filter']['start'] : null,
                'end'        => $data['filter']['end']   !== '' ? $data['filter']['end']   : null,
                'page'       => $last > 1 ? $last : null,
            ], static fn($v) => $v !== null && $v !== '');
            return redirect()->to('/stock-movements' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('inventory/movements', [
            'title'    => 'Histori Mutasi Stok',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'products' => $data['products'],
            'filter'   => $data['filter'],
            'errors'   => $data['errors'],
        ]);
    }
}