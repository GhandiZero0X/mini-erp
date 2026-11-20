<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        // [nama, alamat, telepon, email]
        $rows = [
            ['Toko Komputer Maju Jaya', 'Jl. Tunjungan No. 20, Surabaya', '031-5552001', 'majujaya@contoh.co.id'],
            ['CV Cipta Solusi Digital', 'Jl. Ahmad Yani No. 150, Sidoarjo', '031-5552002', 'purchasing@ciptasolusi-contoh.co.id'],
            ['PT Mitra Sekolah Teknologi', 'Jl. Raya Gubeng No. 9, Surabaya', '031-5552003', null],
            ['Warnet Bintang Net', 'Jl. Kertajaya No. 33, Surabaya', '0812-5552004', null],
            ['Kantor Notaris Hartono', 'Jl. Basuki Rahmat No. 5, Surabaya', null, 'admin@hartono-contoh.co.id'],
        ];

        foreach ($rows as [$name, $address, $phone, $email]) {
            if ($this->db->table('customers')->where('name', $name)->countAllResults() === 0) {
                $this->db->table('customers')->insert(compact('name', 'address', 'phone', 'email'));
            }
        }
    }
}
