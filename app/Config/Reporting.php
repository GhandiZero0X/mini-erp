<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Reporting extends BaseConfig
{
    public array $messages = [
        'date_invalid'   => 'Format tanggal tidak valid.',
        'period_invalid' => 'Periode tidak valid: tanggal mulai tidak boleh melebihi tanggal akhir.',
    ];
}