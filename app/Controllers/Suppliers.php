<?php

namespace App\Controllers;

use App\Services\SupplierService;

/**
 * UC-05 Mengelola Supplier (UI-05).
 * Hak akses diatur di Routes.php: lihat = auth:master_data (Admin, Purchasing, Sales),
 * tambah/ubah/hapus = auth:master_data,W (Admin). Controller tipis: logika ada di SupplierService.
 */
class Suppliers extends BaseController
{
    private ?SupplierService $service = null;

    private const FIELDS = ['name', 'address', 'phone', 'email'];

    private function service(): SupplierService
    {
        return $this->service ??= new SupplierService();
    }

    /** GET /suppliers */
    public function index()
    {
        $data = $this->service()->listPage();

        // Menghapus baris terakhir di halaman terakhir meninggalkan halaman kosong: mundur ke halaman terakhir
        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last = max(1, $data['pager']->getPageCount());

            return redirect()->to('/suppliers' . ($last > 1 ? '?page=' . $last : ''));
        }

        return view('suppliers/index', [
            'title'    => 'Supplier',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'canWrite' => auth_can('master_data', 'W'),
        ]);
    }

    /** GET /suppliers/new */
    public function new()
    {
        return view('suppliers/form', [
            'title'    => 'Tambah Supplier',
            'supplier' => null,
            'action'   => site_url('suppliers'),
            'errors'   => session()->getFlashdata('errors') ?? [],
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /suppliers */
    public function create()
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->create($input);

        if (! $result['ok']) {
            return $this->backToForm('/suppliers/new', $result, $input);
        }

        return redirect()->to('/suppliers')->with('success', $result['message']);
    }

    /** GET /suppliers/{id}/edit */
    public function edit(int $id)
    {
        $supplier = $this->service()->find($id);
        if ($supplier === null) {
            return $this->notFound();
        }

        return view('suppliers/form', [
            'title'    => 'Ubah Supplier',
            'supplier' => $supplier,
            'action'   => site_url('suppliers/' . $id),
            'errors'   => session()->getFlashdata('errors') ?? [],
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /suppliers/{id} */
    public function update(int $id)
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->update($id, $input);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return $this->backToForm('/suppliers/' . $id . '/edit', $result, $input);
        }

        return redirect()->to('/suppliers')->with('success', $result['message']);
    }

    /** POST /suppliers/{id}/delete (HTML form hanya mengenal GET/POST) */
    public function delete(int $id)
    {
        $result = $this->service()->delete($id);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/suppliers')->with($result['ok'] ? 'success' : 'error', $result['message']);
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
        $msg = config('Master')->messages['not_found'] ?? SupplierService::DEFAULTS['not_found'];

        return redirect()->to('/suppliers')->with('error', $msg);
    }
}
