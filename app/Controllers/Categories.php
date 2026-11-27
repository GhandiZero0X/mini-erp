<?php

namespace App\Controllers;

use App\Services\CategoryService;

/**
 * UC-03 Mengelola Kategori (UI-03).
 * Hak akses diatur di Routes.php: lihat = auth:master_data (Admin, Purchasing, Sales),
 * tambah/ubah/hapus = auth:master_data,W (Admin).
 */
class Categories extends BaseController
{
    private ?CategoryService $service = null;

    private function service(): CategoryService
    {
        return $this->service ??= new CategoryService();
    }

    /** GET /categories */
    public function index()
    {
        $q    = $this->request->getGet('q');
        $data = $this->service()->listPage(is_string($q) ? $q : '');

        // Menghapus baris terakhir di halaman terakhir meninggalkan halaman kosong: mundur ke halaman terakhir
        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last = max(1, $data['pager']->getPageCount());

            // Pertahankan kata kunci pencarian saat mundur ke halaman terakhir
            $query = $data['search'] !== '' ? ['q' => $data['search']] : [];
            if ($last > 1) {
                $query['page'] = $last;
            }

            return redirect()->to('/categories' . ($query !== [] ? '?' . http_build_query($query) : ''));
        }

        return view('categories/index', [
            'title'    => 'Kategori',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'search'   => $data['search'],
            'canWrite' => auth_can('master_data', 'W'),
        ]);
    }

    /** GET /categories/new */
    public function new()
    {
        return view('categories/form', [
            'title'    => 'Tambah Kategori',
            'category' => null,
            'action'   => site_url('categories'),
            'errors'   => session()->getFlashdata('errors') ?? [],
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /categories */
    public function create()
    {
        $input  = $this->request->getPost(['name']);
        $result = $this->service()->create($input);

        if (! $result['ok']) {
            return $this->backToForm('/categories/new', $result, $input);
        }

        return redirect()->to('/categories')->with('success', $result['message']);
    }

    /** GET /categories/{id}/edit */
    public function edit(int $id)
    {
        $category = $this->service()->find($id);
        if ($category === null) {
            return $this->notFound();
        }

        return view('categories/form', [
            'title'    => 'Ubah Kategori',
            'category' => $category,
            'action'   => site_url('categories/' . $id),
            'errors'   => session()->getFlashdata('errors') ?? [],
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /categories/{id} */
    public function update(int $id)
    {
        $input  = $this->request->getPost(['name']);
        $result = $this->service()->update($id, $input);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return $this->backToForm('/categories/' . $id . '/edit', $result, $input);
        }

        return redirect()->to('/categories')->with('success', $result['message']);
    }

    /** POST /categories/{id}/delete (HTML form hanya mengenal GET/POST) */
    public function delete(int $id)
    {
        $result = $this->service()->delete($id);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/categories')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Kembali ke form dengan pesan; isian tidak hilang (UI standar SRS). */
    private function backToForm(string $url, array $result, array $input)
    {
        $redirect = redirect()->to($url)->with('errors', $result['errors'])->with('old', $input);

        return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
    }

    /** A5: data yang dituju sudah tidak ada. */
    private function notFound()
    {
        return redirect()->to('/categories')->with('error', config('Master')->messages['not_found']);
    }
}