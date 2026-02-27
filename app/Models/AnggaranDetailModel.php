<?php

namespace App\Models;

use CodeIgniter\Model;

class AnggaranDetailModel extends Model
{
    protected $table = 'anggaran_detail';
    protected $allowedFields = [
        'tahun',
        'bulan',
        'subdit',
        'anggaran_diajukan',
        'anggaran_terserap',
        'created_at',
        'updated_at'
    ];
}