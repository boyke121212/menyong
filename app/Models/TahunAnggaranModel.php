<?php

namespace App\Models;

use CodeIgniter\Model;

class TahunAnggaranModel extends Model
{
    protected $table = 'tahun_anggaran';
    protected $allowedFields = ['tahun', 'bulan_awal', 'created_at', 'updated_at'];
}