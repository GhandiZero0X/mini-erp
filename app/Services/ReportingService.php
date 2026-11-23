<?php

namespace App\Services;

use App\Models\ReportModel;

class ReportingService
{
    private ReportModel $model;
    private array $msg;

    public function __construct(?ReportModel $model = null, ?\Config\Reporting $cfg = null)
    {
        $this->model = $model ?? new ReportModel();
        $this->msg   = ($cfg ?? config('Reporting'))->messages;
    }

    private function cleanFilter(array $f): array
    {
        $start = trim((string) ($f['start'] ?? ''));
        $end   = trim((string) ($f['end']   ?? ''));

        // Default ke bulan berjalan jika kosong
        if ($start === '') { $start = date('Y-m-01'); }
        if ($end   === '') { $end   = date('Y-m-t'); }

        return ['start' => $start, 'end' => $end];
    }

    private function validatePeriod(array $in): array
    {
        $e = [];
        if (! PurchaseOrderService::isValidDate($in['start'])) { $e['start'] = $this->msg['date_invalid']; }
        if (! PurchaseOrderService::isValidDate($in['end']))   { $e['end']   = $this->msg['date_invalid']; }
        if (! isset($e['start']) && ! isset($e['end']) && strcmp($in['start'], $in['end']) > 0) {
            $e['period'] = $this->msg['period_invalid'];
        }
        return $e;
    }

    public function purchasesReport(array $filter): array
    {
        $in = $this->cleanFilter($filter);
        $errors = $this->validatePeriod($in);
        
        $rows = $errors ? [] : $this->model->purchases($in['start'], $in['end']);
        $total = array_sum(array_column($rows, 'total_amount'));

        return compact('rows', 'filter', 'errors', 'total') + ['filter' => $in];
    }

    public function salesReport(array $filter): array
    {
        $in = $this->cleanFilter($filter);
        $errors = $this->validatePeriod($in);
        
        $rows = $errors ? [] : $this->model->sales($in['start'], $in['end']);
        $total = array_sum(array_column($rows, 'total_amount'));

        return ['rows' => $rows, 'filter' => $in, 'errors' => $errors, 'total' => $total];
    }

    public function stockReport(array $filter): array
    {
        $in = $this->cleanFilter($filter);
        $errors = $this->validatePeriod($in);
        $categoryId = (int) ($filter['category_id'] ?? 0);
        
        $rows = $errors ? [] : $this->model->stock($in['start'], $in['end'], $categoryId);
        
        return [
            'rows'       => $rows,
            'filter'     => $in, 
            'errors'     => $errors,
            'categoryId' => $categoryId,
            'categories' => $this->model->categoryOptions(),
            'totalItems' => count($rows),
        ];
    }

    public function topProductsReport(array $filter): array
    {
        $in = $this->cleanFilter($filter);
        $errors = $this->validatePeriod($in);
        
        $rows = $errors ? [] : $this->model->topProducts($in['start'], $in['end']);

        return ['rows' => $rows, 'filter' => $in, 'errors' => $errors];
    }
}