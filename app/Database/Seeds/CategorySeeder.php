<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $names = [
            'Processor', 'Motherboard', 'Memory (RAM)', 'Storage', 'VGA Card',
            'Power Supply', 'Casing', 'Monitor', 'Periferal', 'Jaringan',
        ];

        foreach ($names as $name) {
            if ($this->db->table('categories')->where('name', $name)->countAllResults() === 0) {
                $this->db->table('categories')->insert(['name' => $name]);
            }
        }
    }
}
