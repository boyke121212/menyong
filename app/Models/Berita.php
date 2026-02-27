<?php

namespace App\Models;

use CodeIgniter\Model;

class Berita extends Model
{
    protected $table            = 'berita';
    protected $primaryKey       = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'judul',
        'isi',
        'tanggal',
        'foto',
        'pdf'
    ];

    // optional, kalau pakai soft delete nanti
    protected $useSoftDeletes   = false;

    // timestamps TIDAK dipakai karena tabel pakai field "tanggal" sendiri
    protected $useTimestamps    = false;
}