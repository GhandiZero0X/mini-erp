<?php

namespace App\Controllers;

use App\Services\ReportingService;

class Reports extends BaseController
{
    private ?ReportingService $service = null;
    
    private function service(): ReportingService
    {
        return $this->service ??= new ReportingService();
    }

    public function purchases()
    {
        $data = $this->service()->purchasesReport([
            'start' => $this->request->getGet('start'),
            'end'   => $this->request->getGet('end'),
        ]);

        return view('reports/purchases', array_merge(['title' => 'Laporan Pembelian'], $data));
    }

    public function sales()
    {
        $data = $this->service()->salesReport([
            'start' => $this->request->getGet('start'),
            'end'   => $this->request->getGet('end'),
        ]);

        return view('reports/sales', array_merge(['title' => 'Laporan Penjualan'], $data));
    }

    public function stock()
    {
        $data = $this->service()->stockReport([
            'start'       => $this->request->getGet('start'),
            'end'         => $this->request->getGet('end'),
            'category_id' => $this->request->getGet('category_id'),
        ]);

        return view('reports/stock', array_merge(['title' => 'Laporan Stok'], $data));
    }

    public function topProducts()
    {
        $data = $this->service()->topProductsReport([
            'start' => $this->request->getGet('start'),
            'end'   => $this->request->getGet('end'),
        ]);

        return view('reports/top_products', array_merge(['title' => 'Produk Terlaris'], $data));
    }
}