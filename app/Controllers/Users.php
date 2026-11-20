<?php

namespace App\Controllers;

use App\Services\UserService;

/**
 * UC-21 Kelola Akun Pengguna. Hanya Admin (filter 'auth:users,W' di Routes.php).
 * Controller tipis: validasi dan aturan bisnis ada di UserService.
 */
class Users extends BaseController
{
    private ?UserService $service = null;

    private function service(): UserService
    {
        return $this->service ??= new UserService();
    }

    /** GET /users: daftar akun (UI-23) */
    public function index()
    {
        $data = $this->service()->listPage();

        return view('users/index', [
            'title'  => 'Pengguna',
            'users'  => $data['rows'],
            'pager'  => $data['pager'],
            'meId'   => auth_user()['id'],
        ]);
    }

    /** GET /users/new */
    public function new()
    {
        return view('users/form', [
            'title'  => 'Tambah Pengguna',
            'mode'   => 'create',
            'user'   => null,
            'roles'  => $this->service()->roleOptions(),
            'action' => site_url('users'),
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /users */
    public function create()
    {
        $input  = $this->request->getPost(['full_name', 'username', 'password', 'role_id']);
        $result = $this->service()->create($input);

        if (! $result['ok']) {
            return $this->backToForm('/users/new', $result, $input);
        }

        return redirect()->to('/users')->with('success', $result['message']);
    }

    /** GET /users/{id}/edit */
    public function edit(int $id)
    {
        $user = $this->service()->find($id);
        if ($user === null) {
            return $this->notFound();
        }

        return view('users/form', [
            'title'  => 'Ubah Pengguna',
            'mode'   => 'edit',
            'user'   => $user,
            'roles'  => $this->service()->roleOptions(),
            'action' => site_url('users/' . $id),
            'errors' => session()->getFlashdata('errors') ?? [],
            'old'    => session()->getFlashdata('old') ?? [],
        ]);
    }

    /** POST /users/{id}: username sengaja tidak dibaca dari input (VR-34) */
    public function update(int $id)
    {
        $input  = $this->request->getPost(['full_name', 'role_id']);
        $result = $this->service()->update($id, $input);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return $this->backToForm('/users/' . $id . '/edit', $result, $input);
        }

        return redirect()->to('/users')->with('success', $result['message']);
    }

    /** POST /users/{id}/toggle: aktifkan/nonaktifkan */
    public function toggle(int $id)
    {
        $result = $this->service()->toggleActive($id, auth_user()['id']);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/users')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** GET /users/{id}/reset-password */
    public function resetForm(int $id)
    {
        $user = $this->service()->find($id);
        if ($user === null) {
            return $this->notFound();
        }

        return view('users/reset_password', [
            'title'  => 'Reset Password',
            'user'   => $user,
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    /** POST /users/{id}/reset-password */
    public function resetPassword(int $id)
    {
        $result = $this->service()->resetPassword($id, (string) $this->request->getPost('password'));

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }
        if (! $result['ok']) {
            return redirect()->to('/users/' . $id . '/reset-password')
                ->with('errors', $result['errors'])
                ->with('error', $result['message'] ?: null);
        }

        return redirect()->to('/users')->with('success', $result['message']);
    }

    /** POST /users/{id}/unlock */
    public function unlock(int $id)
    {
        $result = $this->service()->unlock($id);

        if ($result['code'] === 'not_found') {
            return $this->notFound();
        }

        return redirect()->to('/users')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Kembali ke form membawa pesan; password TIDAK dikirim balik ke browser. */
    private function backToForm(string $url, array $result, array $input)
    {
        unset($input['password']);

        $redirect = redirect()->to($url)->with('errors', $result['errors'])->with('old', $input);

        return $result['message'] !== '' ? $redirect->with('error', $result['message']) : $redirect;
    }

    /** A7: data yang dituju sudah tidak ada. */
    private function notFound()
    {
        return redirect()->to('/users')->with('error', config('Auth')->userMessages['not_found']);
    }
}
