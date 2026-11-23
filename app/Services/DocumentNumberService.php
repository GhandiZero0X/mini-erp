<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Documents;

/**
 * Nomor dokumen otomatis: PREFIX-YYYYMM-0001 (PO, GR, SO, DL).
 *
 * Urutan dihitung dari nomor terbesar pada bulan dokumen + 1. Agar dua pengguna yang menyimpan
 * bersamaan tidak mendapat nomor sama, dipakai advisory lock tingkat transaction (pg_advisory_xact_lock)
 * per awalan-bulan. Lock dilepas otomatis saat transaction pemanggil commit/rollback, jadi next()
 * HARUS dipanggil di dalam transaction yang juga menyimpan dokumennya.
 * Constraint UNIQUE pada kolom nomor tetap menjadi pengaman terakhir.
 */
class DocumentNumberService
{
    private BaseConnection $db;
    private Documents $cfg;

    public function __construct(?BaseConnection $db = null, ?Documents $cfg = null)
    {
        $this->db  = $db ?? db_connect(); // koneksi bersama: sama dengan yang dipakai Model
        $this->cfg = $cfg ?? config('Documents');
    }

    /**
     * @param string $type  kunci di Config\Documents::$prefixes (mis. 'purchase_order')
     * @param string $date  tanggal dokumen (Y-m-d); menentukan bagian YYYYMM
     */
    public function next(string $type, string $date): string
    {
        if (! isset($this->cfg->prefixes[$type], $this->cfg->numberColumns[$type])) {
            throw new \InvalidArgumentException("Jenis dokumen tidak dikenal: {$type}");
        }
        if ($this->db->transDepth < 1) {
            throw new \LogicException('DocumentNumberService::next() harus dipanggil di dalam transaction.');
        }

        $ts = strtotime($date);
        if ($ts === false) {
            throw new \InvalidArgumentException("Tanggal dokumen tidak valid: {$date}");
        }

        [$table, $column] = $this->cfg->numberColumns[$type];
        $stem             = $this->cfg->prefixes[$type] . '-' . date('Ym', $ts) . '-';

        $this->db->query('SELECT pg_advisory_xact_lock(hashtext(?))', [$stem]);

        // Nama tabel/kolom berasal dari konfigurasi konstan, bukan input pengguna.
        $sql = 'SELECT COALESCE(MAX(CAST(substring(' . $column . " from '[0-9]+\$') AS INTEGER)), 0) AS last_no"
             . ' FROM ' . $table . ' WHERE ' . $column . ' LIKE ?';
        $row = $this->db->query($sql, [$stem . '%'])->getRowArray();

        return $stem . str_pad((string) (((int) ($row['last_no'] ?? 0)) + 1), 4, '0', STR_PAD_LEFT);
    }
}
