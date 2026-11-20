<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table      = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /** Semua peran untuk pilihan pada form (VR-36). */
    public function all(): array
    {
        return $this->db->table('roles')->select('id, code, name')->orderBy('id')->get()->getResultArray();
    }

    public function exists(int $id): bool
    {
        $row = $this->db->table('roles')->select('id')->where('id', $id)->get()->getRowArray();

        return ! empty($row);
    }

    public function codeById(int $id): ?string
    {
        $row = $this->db->table('roles')->select('code')->where('id', $id)->get()->getRowArray();

        return $row['code'] ?? null;
    }
}
