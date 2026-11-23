<?php

namespace App\Controllers;

use App\Services\SalesOrderService;

class SalesOrders extends BaseController
{
    private ?SalesOrderService $service = null;
    private function service(): SalesOrderService { return $this->service ??= new SalesOrderService(); }

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        // Gudang (bisa kirim barang, tidak bisa buat SO) lihat SO Open yang masih punya sisa
        $shippableOnly = auth_can('delivery', 'W') && ! auth_can('sales_order', 'W');
        $data = $this->service()->listPage($search, $status, $shippableOnly);

        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last = max(1, $data['pager']->getPageCount());
            $params = array_filter(
                ['q' => $search, 'status' => $status, 'page' => $last > 1 ? $last : null],
                static fn($v) => $v !== null && $v !== ''
            );
            return redirect()->to('/sales-orders' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('sales_orders/index', [
            'title'         => 'Sales Order',
            'rows'          => $data['rows'],
            'pager'         => $data['pager'],
            'search'        => $search,
            'status'        => $status,
            'statuses'      => config('Documents')->soStatuses,
            'canWrite'      => auth_can('sales_order', 'W'),
            'shippableOnly' => $shippableOnly,
            'canShip'       => auth_can('delivery', 'W'),
        ]);
    }

    public function new()
    {
        $form = $this->service()->formData();
        return view('sales_orders/form', [
            'title'     => 'Buat Sales Order',
            'customers' => $form['customers'],
            'products'  => $form['products'],
            'action'    => site_url('sales-orders'),
            'today'     => date('Y-m-d'),
            'errors'    => session()->getFlashdata('errors') ?? [],
            'old'       => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function create()
    {
        $input  = $this->request->getPost(['customer_id', 'so_date', 'items']);
        $result = $this->service()->create($input, auth_user()['id']);

        if (! $result['ok']) {
            $redirect = redirect()->to('/sales-orders/new')
                ->with('errors', $result['errors'])
                ->with('old', $input);
            return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
        }

        return redirect()->to('/sales-orders/' . $result['id'])->with('success', $result['message']);
    }

    public function show(int $id)
    {
        $so = $this->service()->find($id);
        if ($so === null) { return $this->notFound(); }

        return view('sales_orders/detail', [
            'title'    => 'Detail Sales Order',
            'so'       => $so,
            'canWrite' => auth_can('sales_order', 'W'),
            'canShip'  => auth_can('delivery', 'W'),
            'errors'   => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function cancel(int $id)
    {
        $input  = $this->request->getPost(['cancel_reason']);
        $result = $this->service()->cancel($id, $input, auth_user()['id']);
        if ($result['code'] === 'not_found') { return $this->notFound(); }

        $redirect = redirect()->to('/sales-orders/' . $id);
        if (! $result['ok'] && $result['errors'] !== []) { $redirect = $redirect->with('errors', $result['errors']); }
        return $redirect->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function notFound()
    {
        return redirect()->to('/sales-orders')->with('error', config('Sales')->messages['so_not_found']);
    }
}