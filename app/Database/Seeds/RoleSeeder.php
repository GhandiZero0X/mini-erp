<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['id' => 1, 'code' => 'admin',      'name' => 'Admin'],
            ['id' => 2, 'code' => 'purchasing', 'name' => 'Staf Purchasing'],
            ['id' => 3, 'code' => 'gudang',     'name' => 'Staf Gudang'],
            ['id' => 4, 'code' => 'sales',      'name' => 'Staf Sales'],
            ['id' => 5, 'code' => 'manajer',    'name' => 'Manajer'],
        ];

        foreach ($roles as $role) {
            // Idempotent: aman dijalankan berulang kali
            if ($this->db->table('roles')->where('code', $role['code'])->countAllResults() === 0) {
                $this->db->table('roles')->insert($role);
            }
        }

        // Insert dengan id eksplisit tidak menggeser sequence SERIAL, jadi disinkronkan
        $this->db->query("SELECT setval(pg_get_serial_sequence('roles','id'), (SELECT MAX(id) FROM roles))");
    }
}
