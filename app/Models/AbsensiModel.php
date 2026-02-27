<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsensiModel extends Model
{
    protected $table = 'absensi';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'userId',
        'masuk',
        'pulang',
        'keterangan',
        'latitude',
        'longitude',
        'foto',
        'foto2',
        'tanggal',
        'selesai',
        'nama',
        'nip',
        'jabatan',
        'subdit',
        'pangkat',
        'ketam',
        'latpulang',
        'lonpulang',
        'fotopulang2',
        'fotopulang',
        'statuspulang',
        'statusmasuk',
        'ketpul',
        'sudahkah',
    ];
}
