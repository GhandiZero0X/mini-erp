<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pager extends BaseConfig
{
    /**
     * @var array<string, string>
     */
    public array $templates = [
        'default_full'   => 'CodeIgniter\Pager\Views\default_full',
        'default_simple' => 'CodeIgniter\Pager\Views\default_simple',
        'default_head'   => 'CodeIgniter\Pager\Views\default_head',
        
        // Tambahkan baris ini:
        'bootstrap_full' => 'App\Views\Pager\bootstrap_full',
    ];

    /**
     * View yang digunakan secara default di seluruh aplikasi.
     * Ubah dari 'default_full' menjadi 'bootstrap_full'
     */
    public string $defaultView = 'bootstrap_full'; // <-- Tambahkan/ubah baris ini

    public int $perPage = 20; // Sesuaikan dengan kebutuhan (misal 10)
}