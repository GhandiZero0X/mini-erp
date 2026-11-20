<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Urutan penting: tabel induk (roles, categories) harus terisi sebelum anaknya.
        $this->call('RoleSeeder');
        $this->call('UserSeeder');
        $this->call('CategorySeeder');
        $this->call('ProductSeeder');
        $this->call('SupplierSeeder');
        $this->call('CustomerSeeder');
    }
}
