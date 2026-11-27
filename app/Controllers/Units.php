<?php

namespace App\Controllers;

use App\Services\UnitService;

/**
 * Master Satuan (Units). Hanya Admin (fitur 'units', level W di Config\Auth::$permissions);
 * peran lain ditolak filter auth di Routes.php. Controller tipis: logika ada di UnitService.
 */
class Units extends BaseController
{
    private ?UnitService $service = null;

    private const FIELDS = ['code', 'name'];

    private function service(): UnitService
    {
        return $this->service ??= new UnitService();
    }

    /** GET /units */
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

            return redirect()->to('/units' . ($query !== [] ? '?' . http_build_query($query) : ''));
        }

        return view('units/index', [
            'title'    => 'Satuan',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'search'   => $data['search'],
            'canWrite' => auth_can('units', 'W'),
        ]);
    }

    /** GET /units/new */
    public function new()
    {
        return view('units/form', [
            'title'  => 'Tambah Satuan',
            'unit'   => null,
            'action' => site_url('units'),
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /units */
    public function create()
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->create($input);

        if (! $result['ok']) {
            return $this->backToForm('/units/new', $result, $input);
        }

        return redirect()->to('/units')->with('success', $result['message']);
    }

    /** GET /units/{id}/edit */
    public function edit(int $id)
    {
        $unit = $this->service()->find($id);
        if ($unit === null) {
            return $this->notFound();
        }

        return view('units/form', [
            'title'  => 'Ubah Satuan',
            'unit'   => $unit,
            'action' => site_url('units/' . $id),
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /units/{id} */
    public function update(int $id)
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->update($id, $input);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return $this->backToForm('/units/' . $id . '/edit', $result, $input);
        }

        return redirect()->to('/units')->with('success', $result['message']);
    }

    /** POST /units/{id}/delete (HTML form hanya mengenal GET/POST) */
    public function delete(int $id)
    {
        $result = $this->service()->delete($id);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/units')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Kembali ke form dengan pesan; isian tidak hilang (UI standar SRS). */
    private function backToForm(string $url, array $result, array $input)
    {
        $redirect = redirect()->to($url)->with('errors', $result['errors'])->with('old', $input);

        return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
    }

    /** Data yang dituju sudah tidak ada. */
    private function notFound()
    {
        $msg = config('Master')->messages['not_found'] ?? UnitService::DEFAULTS['not_found'];

        return redirect()->to('/units')->with('error', $msg);
    }
}
