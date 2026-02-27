<?php

namespace App\Models;

use CodeIgniter\Model;

class AnggaranModel extends Model
{
    protected $table = 'anggaran_bulanan';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'tahun_anggaran',
        'bulan_awal',
        'bulan',
        'subdit',
        'anggaran_diajukan',
        'anggaran_terserap',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
}