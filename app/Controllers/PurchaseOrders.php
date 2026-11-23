<?php

namespace App\Controllers;

use App\Services\PurchaseOrderService;

/**
 * UC-07 Membuat Purchase Order dan UC-08 Menyetujui Purchase Order (UI-07, UI-08, UI-09).
 * Hak akses diatur di Routes.php: lihat = auth:purchase_order (Purchasing, Gudang),
 * buat/setujui = auth:purchase_order,W (Purchasing). Controller tipis: logika ada di PurchaseOrderService.
 */
class PurchaseOrders extends BaseController
{
    private ?PurchaseOrderService $service = null;

    private function service(): PurchaseOrderService
    {
        return $this->service ??= new PurchaseOrderService();
    }

    /** GET /purchase-orders?q=...&status=...&page=... */
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        // Staf Gudang (bisa mencatat GR, tidak bisa membuat PO) melihat PO Approved yang belum diterima penuh (UI-07)
        $receivableOnly = auth_can('goods_receipt', 'W') && ! auth_can('purchase_order', 'W');
        $data           = $this->service()->listPage($search, $status, $receivableOnly);

        // Halaman di luar jangkauan (mis. filter diganti): mundur ke halaman terakhir
        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last   = max(1, $data['pager']->getPageCount());
            $params = array_filter(
                ['q' => $search, 'status' => $status, 'page' => $last > 1 ? $last : null],
                static fn ($v) => $v !== null && $v !== ''
            );

            return redirect()->to('/purchase-orders' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('purchase_orders/index', [
            'title'    => 'Purchase Order',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'search'   => $search,
            'status'   => $status,
            'statuses' => config('Documents')->poStatuses,
            'canWrite' => auth_can('purchase_order', 'W'),
            'receivableOnly' => $receivableOnly,
            'canReceive'     => auth_can('goods_receipt', 'W'),
        ]);
    }

    /** GET /purchase-orders/new */
    public function new()
    {
        $form = $this->service()->formData();

        return view('purchase_orders/form', [
            'title'     => 'Buat Purchase Order',
            'suppliers' => $form['suppliers'],
            'products'  => $form['products'],
            'action'    => site_url('purchase-orders'),
            'today'     => date('Y-m-d'),
            'errors'    => session()->getFlashdata('errors') ?? [],
            'old'       => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /purchase-orders */
    public function create()
    {
        $input  = $this->request->getPost(['supplier_id', 'po_date', 'items']);
        $result = $this->service()->create($input, auth_user()['id']);

        if (! $result['ok']) {
            $redirect = redirect()->to('/purchase-orders/new')
                ->with('errors', $result['errors'])
                ->with('old', $input); // isian tidak hilang (UI standar SRS)

            return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
        }

        // UC-07 langkah 9: tampilkan detail PO yang baru dibuat
        return redirect()->to('/purchase-orders/' . $result['id'])->with('success', $result['message']);
    }

    /** GET /purchase-orders/{id} */
    public function show(int $id)
    {
        $po = $this->service()->find($id);
        if ($po === null) {
            return $this->notFound();
        }

        return view('purchase_orders/detail', [
            'title'    => 'Detail Purchase Order',
            'po'         => $po,
            'canWrite'   => auth_can('purchase_order', 'W'),
            'canReceive' => auth_can('goods_receipt', 'W'),
        ]);
    }

    /** POST /purchase-orders/{id}/approve */
    public function approve(int $id)
    {
        $result = $this->service()->approve($id, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/purchase-orders/' . $id)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** POST /purchase-orders/{id}/cancel */
    public function cancel(int $id)
    {
        $input  = $this->request->getPost(['cancel_reason']);
        $result = $this->service()->cancel($id, $input, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        $redirect = redirect()->to('/purchase-orders/' . $id);
        if (! $result['ok'] && $result['errors'] !== []) {
            $redirect = $redirect->with('errors', $result['errors']);
        }

        return $redirect->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** A3: data yang dituju sudah tidak ada. */
    private function notFound()
    {
        return redirect()->to('/purchase-orders')->with('error', config('Purchasing')->messages['not_found']);
    }
}