<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $units = [
            ['code' => 'PCS', 'name' => 'Pieces'],
            ['code' => 'KG',  'name' => 'Kilogram'],
            ['code' => 'BOX', 'name' => 'Box'],
            ['code' => 'LTR', 'name' => 'Liter'],
            ['code' => 'MTR', 'name' => 'Meter'],
        ];

        foreach ($units as $u) {
            $exists = $this->db->table('units')
                ->where('code', $u['code'])
                ->countAllResults();

            if ($exists === 0) {
                $u['created_at'] = $now;
                $u['updated_at'] = $now;
                $this->db->table('units')->insert($u);
            }
        }
    }
}