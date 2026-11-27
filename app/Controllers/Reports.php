<?php

namespace App\Controllers;

use App\Libraries\ReportWorkbook;
use App\Services\ReportingService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Reports extends BaseController
{
    private ?ReportingService $service = null;

    private function service(): ReportingService
    {
        return $this->service ??= new ReportingService();
    }

    // ==================================================================
    // HALAMAN LAPORAN (view)
    // ==================================================================

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

    // ==================================================================
    // DOWNLOAD EXCEL — semua method mengikuti pola yang sama
    // ==================================================================

    public function downloadPurchases()
    {
        $data = $this->service()->purchasesReport($this->periodFilter());

        if ($data['errors'] !== []) {
            return $this->invalidFilter($data['errors']);
        }

        $wb = (new ReportWorkbook())->purchases(
            $data['rows'],
            $data['filter'],
            $this->printedBy()
        );

        return $this->streamWorkbook($wb, 'Laporan-Pembelian', $data['filter']);
    }

    public function downloadSales()
    {
        $data = $this->service()->salesReport($this->periodFilter());

        if ($data['errors'] !== []) {
            return $this->invalidFilter($data['errors']);
        }

        $wb = (new ReportWorkbook())->sales(
            $data['rows'],
            $data['filter'],
            $this->printedBy()
        );

        return $this->streamWorkbook($wb, 'Laporan-Penjualan', $data['filter']);
    }

    public function downloadStock()
    {
        $data = $this->service()->stockReport([
            'start'       => $this->request->getGet('start'),
            'end'         => $this->request->getGet('end'),
            'category_id' => $this->request->getGet('category_id'),
        ]);

        if ($data['errors'] !== []) {
            return $this->invalidFilter($data['errors']);
        }

        // Cari nama kategori terpilih untuk header Excel
        $categoryName = null;
        if ((int) $data['categoryId'] > 0) {
            foreach ($data['categories'] as $c) {
                if ((int) $c['id'] === (int) $data['categoryId']) {
                    $categoryName = $c['name'];
                    break;
                }
            }
        }

        $wb = (new ReportWorkbook())->stock(
            $data['rows'],
            $data['filter'],
            $this->printedBy(),
            $categoryName
        );

        return $this->streamWorkbook($wb, 'Laporan-Stok', $data['filter']);
    }

    public function downloadTopProducts()
    {
        $data = $this->service()->topProductsReport($this->periodFilter());

        if ($data['errors'] !== []) {
            return $this->invalidFilter($data['errors']);
        }

        $wb = (new ReportWorkbook())->topProducts(
            $data['rows'],
            $data['filter'],
            $this->printedBy()
        );

        return $this->streamWorkbook($wb, 'Produk-Terlaris', $data['filter']);
    }

    // ==================================================================
    // Helper privat
    // ==================================================================

    /** @return array{start:?string,end:?string} */
    private function periodFilter(): array
    {
        return [
            'start' => $this->request->getGet('start'),
            'end'   => $this->request->getGet('end'),
        ];
    }

    private function printedBy(): string
    {
        $u = auth_user();

        return $u['full_name'] ?? 'Pengguna';
    }

    private function invalidFilter(array $errors)
    {
        return redirect()
            ->back()
            ->with('error', implode(' ', array_values($errors)));
    }

    /**
     * Kirim workbook sebagai file download .xlsx.
     * Filename otomatis berisi rentang periode agar tidak bentrok saat diunduh berulang.
     */
    private function streamWorkbook(Spreadsheet $wb, string $prefix, array $filter)
    {
        $binary = ReportWorkbook::toBinary($wb);

        $filename = sprintf(
            '%s_%s_sd_%s.xlsx',
            $prefix,
            $filter['start'],
            $filter['end']
        );

        return $this->response
            ->download($filename, $binary)
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}