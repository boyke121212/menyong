<?php

namespace App\Models;

use CodeIgniter\Model;

class Deden extends Model
{
    protected $table      = 'usn';
    protected $primaryKey = 'userId';

    protected $allowedFields = [
        'username',
        'password',
        'name',
        'roleId',
        'nip',
        'pangkat',
        'jabatan',
        'subdit',
        'token',
        'status',
        'createdBy',
        'createdDtm',
        'app_signature',
        'device_hash',
        'access_token',
        'refresh_token'
    ];

    protected $columnSearch = [
        'username',
        'name',
        'jabatan',
        'nip',
        'subdit',
        'status',
        'pangkat'
    ];

    public function findByUsername(string $username)
    {
        return $this->where('username', $username)->first();
    }

    // tetap dipakai kalau butuh non-datatables
    public function getAllWithoutSecret()
    {
        return $this->select(
            'userId, username, name, jabatan, nip, subdit, status, pangkat'
        )->findAll();
    }

    // ==============================
    // 🔥 DATATABLES SERVER SIDE
    // ==============================

    private function applyRoleVisibilityFilter($builder, int $viewerRoleId): void
    {
        if ($viewerRoleId === 2) {
            // Role 2 bisa lihat semua kecuali role 1
            $builder->where('roleId !=', 1);
            return;
        }

        if ($viewerRoleId === 3) {
            // Role 3 bisa lihat semua kecuali role 1
            $builder->where('roleId !=', 1);
            return;
        }
        // Role 1: tanpa filter (lihat semua)
    }

    private function _datatableQuery($search, int $viewerRoleId = 1)
    {
        $builder = $this->db->table($this->table);
        $builder->select(
            'userId, username, name, jabatan, nip, subdit, status, pangkat,roleId'
        );

        $this->applyRoleVisibilityFilter($builder, $viewerRoleId);

        if (!empty($search)) {
            $builder->groupStart();
            foreach ($this->columnSearch as $col) {
                $builder->orLike($col, $search);
            }
            $builder->groupEnd();
        }

        return $builder;
    }

    public function getDatatables($start, $length, $search, $orderColumn = null, $orderDir = 'asc', int $viewerRoleId = 1)
    {
        $builder = $this->_datatableQuery($search, $viewerRoleId);

        // 🔐 whitelist kolom yang boleh di-sort
        $allowedSort = [
            'username',
            'name',
            'jabatan',
            'nip',
            'subdit',
            'status',
            'pangkat',
            'email',
            'roleId',
            'userId'
        ];

        if ($orderColumn === 'roleId') {
            if ($orderDir === 'asc') {
                $builder->orderBy("FIELD(roleId, 1, 2, 3, 4, 5, 6, 7, 8)", 'ASC', false);
            } else {
                $builder->orderBy("FIELD(roleId, 8, 7, 6, 5, 4, 3, 2, 1)", 'ASC', false);
            }
        } elseif ($orderColumn && in_array($orderColumn, $allowedSort)) {
            $builder->orderBy($orderColumn, $orderDir);
        } else {
            $builder->orderBy('userId', 'DESC');
        }
        $builder->limit($length, $start);

        $query = $builder->get()->getResultArray();

        $data = [];
        $no = $start + 1;

        foreach ($query as $row) {
            $row['no'] = $no++;
            $row['aksi'] = '
            <button onclick="editUser(' . $row['userId'] . ')">Edit</button>
            <button onclick="hapusUser(' . $row['userId'] . ')">Hapus</button>
        ';
            $data[] = $row;
        }

        return $data;
    }


    public function countAllData(int $viewerRoleId = 1)
    {
        $builder = $this->db->table($this->table);
        $this->applyRoleVisibilityFilter($builder, $viewerRoleId);
        return $builder->countAllResults();
    }

    public function countFilteredData($search, int $viewerRoleId = 1)
    {
        $builder = $this->_datatableQuery($search, $viewerRoleId);
        return $builder->countAllResults();
    }

    public function getdatauser($userId)
    {
        return $this->select(
            'userId, username, name, jabatan, nip, subdit, status, pangkat,roleId'
        )
            ->where('userId', $userId)
            ->first(); // karena 1 user
    }
}
