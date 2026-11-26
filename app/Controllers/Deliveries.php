<?php

namespace App\Controllers;

use App\Services\DeliveryService;

class Deliveries extends BaseController
{
    private ?DeliveryService $service = null;
    private function service(): DeliveryService { return $this->service ??= new DeliveryService(); }

    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $data   = $this->service()->listPage($search);

        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last   = max(1, $data['pager']->getPageCount());
            $params = array_filter(['q' => $search, 'page' => $last > 1 ? $last : null], static fn($v) => $v !== null && $v !== '');
            return redirect()->to('/deliveries' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('deliveries/index', [
            'title'  => 'Delivery',
            'rows'   => $data['rows'],
            'pager'  => $data['pager'],
            'search' => $search,
        ]);
    }

    /** GET /deliveries/new/{soId} */
    public function new(int $soId)
    {
        $so = $this->service()->formData($soId);
        if ($so === null) {
            return redirect()->to('/sales-orders')->with('error', config('Sales')->messages['so_not_found']);
        }
        if (! $so['deliverable']) {
            return redirect()->to('/sales-orders')->with('error', config('Sales')->messages['delivery_not_receivable']);
        }

        return view('deliveries/form', [
            'title'  => 'Kirim Barang',
            'so'     => $so,
            'action' => site_url('deliveries/new/' . $soId),
            'today'  => date('Y-m-d'),
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function create(int $soId)
    {
        $input  = $this->request->getPost(['delivery_date', 'quantities']);
        $result = $this->service()->create($soId, $input, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return redirect()->to('/sales-orders')->with('error', config('Sales')->messages['so_not_found']);
        }
        if ($result['code'] === 'not_receivable') {
            return redirect()->to('/sales-orders')->with('error', $result['message']);
        }
        if (! $result['ok']) {
            $redirect = redirect()->to('/deliveries/new/' . $soId)
                ->with('errors', $result['errors'])
                ->with('old', $input);
            return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
        }

        return redirect()->to('/deliveries/' . $result['id'])->with('success', $result['message']);
    }

    public function show(int $id)
    {
        $dl = $this->service()->find($id);
        if ($dl === null) {
            return redirect()->to('/deliveries')->with('error', config('Sales')->messages['delivery_not_found']);
        }
        return view('deliveries/detail', [
            'title'  => 'Detail Delivery',
            'dl'     => $dl,
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function cancel(int $id)
    {
        $input  = $this->request->getPost(['cancel_reason']);
        $result = $this->service()->cancel($id, $input, auth_user()['id']);
        if ($result['code'] === 'not_found') {
            return redirect()->to('/deliveries')->with('error', config('Sales')->messages['delivery_not_found']);
        }

        $redirect = redirect()->to('/deliveries/' . $id);
        if (! $result['ok'] && $result['errors'] !== []) { $redirect = $redirect->with('errors', $result['errors']); }
        return $redirect->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function activate(int $id)
    {
        $result = $this->service()->activate($id);
        return redirect()->to('/deliveries/' . $id)->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}