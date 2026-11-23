<?php

namespace App\Controllers;

use App\Services\GoodsReceiptService;

/**
 * UC-09 Mencatat Goods Receipt (UI-10, UI-21).
 * Hak akses di Routes.php: fitur 'goods_receipt' (Staf Gudang). Controller tipis: logika ada di GoodsReceiptService.
 */
class GoodsReceipts extends BaseController
{
    private ?GoodsReceiptService $service = null;

    private function service(): GoodsReceiptService
    {
        return $this->service ??= new GoodsReceiptService();
    }

    /** GET /goods-receipts?q=...&page=... */
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $data   = $this->service()->listPage($search);

        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last   = max(1, $data['pager']->getPageCount());
            $params = array_filter(['q' => $search, 'page' => $last > 1 ? $last : null], static fn ($v) => $v !== null && $v !== '');

            return redirect()->to('/goods-receipts' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('goods_receipts/index', [
            'title'  => 'Goods Receipt',
            'rows'   => $data['rows'],
            'pager'  => $data['pager'],
            'search' => $search,
        ]);
    }

    /** GET /goods-receipts/new/{poId}: form penerimaan untuk satu PO (UI-10) */
    public function new(int $poId)
    {
        $po = $this->service()->formData($poId);
        if ($po === null) {
            return $this->poNotFound();
        }
        if (! $po['receivable']) {
            // A1: belum Approved, Dibatalkan, atau sudah diterima penuh (VR-10)
            return redirect()->to('/purchase-orders')->with('error', config('Purchasing')->messages['gr_not_receivable']);
        }

        return view('goods_receipts/form', [
            'title'  => 'Terima Barang',
            'po'     => $po,
            'action' => site_url('goods-receipts/new/' . $poId),
            'today'  => date('Y-m-d'),
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /goods-receipts/new/{poId} */
    public function create(int $poId)
    {
        $input  = $this->request->getPost(['receipt_date', 'quantities']);
        $result = $this->service()->create($poId, $input, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return $this->poNotFound();
        }
        if ($result['code'] === 'not_receivable') {
            return redirect()->to('/purchase-orders')->with('error', $result['message']);
        }
        if (! $result['ok']) {
            $redirect = redirect()->to('/goods-receipts/new/' . $poId)
                ->with('errors', $result['errors'])
                ->with('old', $input); // isian tidak hilang (UI standar SRS)

            return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
        }

        // UC-09 langkah 7: pesan berhasil beserta detail GR
        return redirect()->to('/goods-receipts/' . $result['id'])->with('success', $result['message']);
    }

    /** GET /goods-receipts/{id} (UI-21) */
    public function show(int $id)
    {
        $gr = $this->service()->find($id);
        if ($gr === null) {
            return redirect()->to('/goods-receipts')->with('error', config('Purchasing')->messages['not_found']);
        }

        return view('goods_receipts/detail', [
            'title' => 'Detail Goods Receipt',
            'gr'    => $gr,
        ]);
    }

    /** POST /goods-receipts/{id}/cancel */
    public function cancel(int $id)
    {
        $input  = $this->request->getPost(['cancel_reason']);
        $result = $this->service()->cancel($id, $input, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return redirect()->to('/goods-receipts')->with('error', config('Purchasing')->messages['not_found']);
        }

        $redirect = redirect()->to('/goods-receipts/' . $id);
        if (! $result['ok'] && $result['errors'] !== []) {
            $redirect = $redirect->with('errors', $result['errors']);
        }

        return $redirect->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function poNotFound()
    {
        return redirect()->to('/purchase-orders')->with('error', config('Purchasing')->messages['not_found']);
    }
}