<?php

namespace App\Controllers;

use App\Services\PurchaseOrderService;

class PurchaseOrders extends BaseController
{
    private ?PurchaseOrderService $service = null;

    private function service(): PurchaseOrderService
    {
        return $this->service ??= new PurchaseOrderService();
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $receivableOnly = auth_can('goods_receipt', 'W') && ! auth_can('purchase_order', 'W');
        $data           = $this->service()->listPage($search, $status, $receivableOnly);

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
            'title'          => 'Purchase Order',
            'rows'           => $data['rows'],
            'pager'          => $data['pager'],
            'search'         => $search,
            'status'         => $status,
            'statuses'       => config('Documents')->poStatuses,
            'canWrite'       => auth_can('purchase_order', 'W'),
            'canApprove'     => auth_can('po_approve', 'W'),
            'receivableOnly' => $receivableOnly,
            'canReceive'     => auth_can('goods_receipt', 'W'),
        ]);
    }

    /** GET /purchase-orders/new?product_id=X (opsional, pre-select dari dashboard) */
    public function new()
    {
        $form = $this->service()->formData();

        // ← BARU: pre-select produk dari dashboard (?product_id=X)
        $preselect = null;
        $pid       = (int) $this->request->getGet('product_id');
        if ($pid > 0) {
            foreach ($form['products'] as $p) {
                if ((int) $p['id'] === $pid) {
                    $preselect = $p;
                    break;
                }
            }
        }

        return view('purchase_orders/form', [
            'title'     => 'Buat Purchase Order',
            'suppliers' => $form['suppliers'],
            'products'  => $form['products'],
            'action'    => site_url('purchase-orders'),
            'today'     => date('Y-m-d'),
            'preselect' => $preselect,
            'errors'    => session()->getFlashdata('errors') ?? [],
            'old'       => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function create()
    {
        $input  = $this->request->getPost(['supplier_id', 'po_date', 'items']);
        $result = $this->service()->create($input, auth_user()['id']);

        if (! $result['ok']) {
            $redirect = redirect()->to('/purchase-orders/new')
                ->with('errors', $result['errors'])
                ->with('old', $input);

            return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
        }

        return redirect()->to('/purchase-orders/' . $result['id'])->with('success', $result['message']);
    }

        /** GET /purchase-orders/{id}/edit */
    public function edit(int $id)
    {
        $po = $this->service()->find($id);
        if ($po === null) {
            return $this->notFound();
        }

        if ($po['status'] !== \Config\Documents::PO_DRAFT) {
            return redirect()->to('/purchase-orders/' . $id)->with('error', config('Purchasing')->messages['edit_not_draft']);
        }

        $form = $this->service()->formData();

        // Siapkan array $old agar form langsung terisi data yang ada
        $old = [
            'supplier_id' => $po['supplier_id'],
            'po_date'     => $po['po_date'],
            'items'       => array_map(static fn($it) => [
                'product_id' => $it['product_id'],
                'quantity'   => $it['quantity'],
                'unit_price' => $it['unit_price'],
            ], $po['items']),
        ];

        return view('purchase_orders/form', [
            'title'     => 'Ubah Purchase Order',
            'suppliers' => $form['suppliers'],
            'products'  => $form['products'],
            'action'    => site_url('purchase-orders/' . $id . '/edit'),
            'today'     => date('Y-m-d'),
            'errors'    => session()->getFlashdata('errors') ?? [],
            'old'       => session()->getFlashdata('old') ?? $old,
            'preselect' => null,
        ]);
    }

    /** POST /purchase-orders/{id}/edit */
    public function update(int $id)
    {
        $input  = $this->request->getPost(['supplier_id', 'po_date', 'items']);
        $result = $this->service()->update($id, $input, auth_user()['id']);

        if (! $result['ok']) {
            $redirect = redirect()->to('/purchase-orders/' . $id . '/edit')
                ->with('errors', $result['errors'])
                ->with('old', $input);

            return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
        }

        return redirect()->to('/purchase-orders/' . $id)->with('success', $result['message']);
    }

    public function show(int $id)
    {
        $po = $this->service()->find($id);
        if ($po === null) {
            return $this->notFound();
        }

        return view('purchase_orders/detail', [
            'title'      => 'Detail Purchase Order',
            'po'         => $po,
            'canWrite'   => auth_can('purchase_order', 'W'),
            'canApprove' => auth_can('po_approve', 'W'),
            'canReceive' => auth_can('goods_receipt', 'W'),
        ]);
    }

    public function approve(int $id)
    {
        $result = $this->service()->approve($id, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/purchase-orders/' . $id)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

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

    private function notFound()
    {
        return redirect()->to('/purchase-orders')->with('error', config('Purchasing')->messages['not_found']);
    }
}