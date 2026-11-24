<?php

namespace App\Controllers;

use App\Services\ProductService;

/**
 * UC-04 Mengelola Produk (UI-04).
 * Hak akses diatur di Routes.php: lihat = auth:master_data (Admin, Purchasing, Sales),
 * tambah/ubah/hapus = auth:master_data,W (Admin). Controller tipis: logika ada di ProductService.
 */
class Products extends BaseController
{
    private ?ProductService $service = null;

    // ← tambah min_stock & max_stock supaya ikut ditangkap dari POST
    private const FIELDS = [
        'code', 'name', 'unit_id', 'purchase_price', 'selling_price',
        'category_id', 'min_stock', 'max_stock',
    ];

    private function service(): ProductService
    {
        return $this->service ??= new ProductService();
    }

    /** GET /products?q=...&page=... */
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $data   = $this->service()->listPage($search);

        $page = (int) $this->request->getGet('page');
        if ($data['rows'] === [] && $page > 1) {
            $last   = max(1, $data['pager']->getPageCount());
            $params = array_filter(
                ['q' => $search, 'page' => $last > 1 ? $last : null],
                static fn ($v) => $v !== null && $v !== ''
            );

            return redirect()->to('/products' . ($params ? '?' . http_build_query($params) : ''));
        }

        return view('products/index', [
            'title'    => 'Produk',
            'rows'     => $data['rows'],
            'pager'    => $data['pager'],
            'search'   => $search,
            'canWrite' => auth_can('master_data', 'W'),
        ]);
    }

    /** GET /products/new */
    public function new()
    {
        return view('products/form', [
            'title'      => 'Tambah Produk',
            'product'    => null,
            'categories' => $this->service()->categoryOptions(),
            'units'      => $this->service()->unitOptions(),
            'action'     => site_url('products'),
            'errors'     => session()->getFlashdata('errors') ?? [],
            'old'        => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /products */
    public function create()
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->create($input);

        if (! $result['ok']) {
            return $this->backToForm('/products/new', $result, $input);
        }

        return redirect()->to('/products')->with('success', $result['message']);
    }

    /** GET /products/{id}/edit */
    public function edit(int $id)
    {
        $product = $this->service()->find($id);
        if ($product === null) {
            return $this->notFound();
        }

        return view('products/form', [
            'title'      => 'Ubah Produk',
            'product'    => $product,
            'categories' => $this->service()->categoryOptions(),
            'units'      => $this->service()->unitOptions(),
            'action'     => site_url('products/' . $id),
            'errors'     => session()->getFlashdata('errors') ?? [],
            'old'        => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /products/{id} */
    public function update(int $id)
    {
        $input  = $this->request->getPost(self::FIELDS);
        $result = $this->service()->update($id, $input);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return $this->backToForm('/products/' . $id . '/edit', $result, $input);
        }

        $redirect = redirect()->to('/products')->with('success', $result['message']);

        return $result['warning'] !== '' ? $redirect->with('warning', $result['warning']) : $redirect;
    }

    /** POST /products/{id}/delete */
    public function delete(int $id)
    {
        $result = $this->service()->delete($id);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/products')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function backToForm(string $url, array $result, array $input)
    {
        $redirect = redirect()->to($url)->with('errors', $result['errors'])->with('old', $input);

        return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
    }

    private function notFound()
    {
        $msg = config('Master')->messages['not_found'] ?? ProductService::DEFAULTS['not_found'];

        return redirect()->to('/products')->with('error', $msg);
    }
}