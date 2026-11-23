<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Password awal HANYA untuk development, wajib diganti di lingkungan lain.
        // Bisa di-override lewat .env:  seed.defaultPassword = 'isi-sendiri'
        $plain = env('seed.defaultPassword', ' Password123!');
        $hash  = password_hash($plain, PASSWORD_DEFAULT); // disimpan sebagai hash (NFR-01)

        $users = [
            ['Administrator',   'admin',      'admin'],
            ['Staf Purchasing', 'purchasing', 'purchasing'],
            ['Staf Gudang',     'gudang',     'gudang'],
            ['Staf Sales',      'sales',      'sales'],
            ['Manajer',         'manajer',    'manajer'],
        ];

        foreach ($users as [$fullName, $username, $roleCode]) {
            if ($this->db->table('users')->where('username', $username)->countAllResults() > 0) {
                continue;
            }

            // Cari role_id lewat code, jangan hardcode angka
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue; // jalankan RoleSeeder dulu
            }

            $this->db->table('users')->insert([
                'full_name'     => $fullName,
                'username'      => $username,
                'password_hash' => $hash,
                'role_id'       => $role['id'],
                'is_active'     => true,
            ]);
        }
    }
}