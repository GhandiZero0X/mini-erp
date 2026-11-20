<?php

namespace App\Controllers;

use App\Services\CustomerService;

/**
 * UC-06 Mengelola Customer (UI-06).
 * Hak akses diatur di Routes.php: lihat = auth:master_data (Admin, Purchasing, Sales),
 * tambah/ubah/hapus = auth:master_data,W (Admin). Controller tipis: logika ada di CustomerService.
 */
class Customers extends BaseController
{
    private ?CustomerService $service = null;

    private const FIELDS = ['name', 'address', 'phone', 'email'];

    private function service(): CustomerService
    {
        return $this->service ??= new CustomerService();
    }

    /** GET /customers */
    public function index()
    {
        $data = $this->service()->listPage();

        // Menghapus baris terakhir di halaman terakhir meninggalkan halaman kosong: mundur ke halaman terakhir
        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last = max(1, $data['pager']->getPageCount());

            return redirect()->to('/customers' . ($last > 1 ? '?page=' . $last : ''));
        }

        return view('customers/index', [
            'title'    => 'Customer',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'canWrite' => auth_can('master_data', 'W'),
        ]);
    }

    /** GET /customers/new */
    public function new()
    {
        return view('customers/form', [
            'title'    => 'Tambah Customer',
            'customer' => null,
            'action'   => site_url('customers'),
            'errors'   => session()->getFlashdata('errors') ?? [],
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /customers */
    public function create()
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->create($input);

        if (! $result['ok']) {
            return $this->backToForm('/customers/new', $result, $input);
        }

        return redirect()->to('/customers')->with('success', $result['message']);
    }

    /** GET /customers/{id}/edit */
    public function edit(int $id)
    {
        $customer = $this->service()->find($id);
        if ($customer === null) {
            return $this->notFound();
        }

        return view('customers/form', [
            'title'    => 'Ubah Customer',
            'customer' => $customer,
            'action'   => site_url('customers/' . $id),
            'errors'   => session()->getFlashdata('errors') ?? [],
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /customers/{id} */
    public function update(int $id)
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->update($id, $input);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return $this->backToForm('/customers/' . $id . '/edit', $result, $input);
        }

        return redirect()->to('/customers')->with('success', $result['message']);
    }

    /** POST /customers/{id}/delete (HTML form hanya mengenal GET/POST) */
    public function delete(int $id)
    {
        $result = $this->service()->delete($id);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/customers')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Kembali ke form dengan pesan; isian tidak hilang (UI standar SRS). */
    private function backToForm(string $url, array $result, array $input)
    {
        $redirect = redirect()->to($url)->with('errors', $result['errors'])->with('old', $input);

        return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
    }

    /** A6: data yang dituju sudah tidak ada. */
    private function notFound()
    {
        $msg = config('Master')->messages['not_found'] ?? CustomerService::DEFAULTS['not_found'];

        return redirect()->to('/customers')->with('error', $msg);
    }
}
