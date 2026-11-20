<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        // [nama, alamat, telepon, email]
        $rows = [
            ['PT Synnex Metrodata Indonesia', 'Jl. Hayam Wuruk No. 12, Jakarta Barat', '021-5551001', 'sales@synnex-contoh.co.id'],
            ['PT Datascrip Komponen', 'Jl. Raya Darmo No. 45, Surabaya', '031-5551002', 'order@datascrip-contoh.co.id'],
            ['CV Mega Komputindo', 'Jl. Dr. Cipto No. 8, Bandung', '022-5551003', null],
            ['PT Erajaya Hardware Supply', 'Jl. Gajah Mada No. 101, Jakarta Pusat', '021-5551004', 'info@erajaya-contoh.co.id'],
            ['UD Sumber Jaya Teknik', 'Jl. Kembang Jepun No. 77, Surabaya', null, null],
        ];

        foreach ($rows as [$name, $address, $phone, $email]) {
            if ($this->db->table('suppliers')->where('name', $name)->countAllResults() === 0) {
                $this->db->table('suppliers')->insert(compact('name', 'address', 'phone', 'email'));
            }
        }
    }
}
