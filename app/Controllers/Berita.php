<?php

namespace App\Controllers;

use App\Models\BeritaModel;
use App\Models\Deden;

class Berita extends BaseController
{
    protected $berita;
    protected $dauo;

    public function __construct()
    {
        $this->berita = new BeritaModel();
        $this->dauo   = new Deden();
    }

    /* ===============================
       VIEW PAGE (STYLE KAMU)
    =============================== */
    public function index()
    {
        $session  = session();
        $username = $session->get('username');
        $user     = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Data Berita',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'Home',
            'page'    => 'berita_view',
        ]);
    }

    /* ===============================
       DATATABLE SERVER SIDE
    =============================== */
public function getData()
{
    $request = service('request');

    $draw   = $request->getPost('draw');
    $start  = $request->getPost('start');
    $length = $request->getPost('length');

    $search = $request->getPost('search')['value'] ?? '';
    $order  = $request->getPost('order');

    // RANGE TANGGAL
    $tgl_awal  = $request->getPost('tgl_awal');
    $tgl_akhir = $request->getPost('tgl_akhir');

    $columns = [
        0=>'id',1=>'judul',2=>'isi',
        3=>'tanggal',4=>'foto',5=>'pdf'
    ];

    $builder = $this->berita->builder();

    /* ===== FILTER RANGE ===== */
    if ($tgl_awal && $tgl_akhir) {
        $builder->where('tanggal >=', $tgl_awal);
        $builder->where('tanggal <=', $tgl_akhir);
    }

    /* ===== SEARCH ===== */
    if ($search) {
        $builder->groupStart();
        $builder->like('judul',$search);
        $builder->orLike('isi',$search);
        $builder->groupEnd();
    }

    $totalFiltered = $builder->countAllResults(false);

    if ($order) {
        $builder->orderBy(
            $columns[$order[0]['column']],
            $order[0]['dir']
        );
    } else {
        $builder->orderBy('id','DESC');
    }

    $builder->limit($length,$start);

    $query = $builder->get()->getResultArray();

    $data = [];

    foreach($query as $row){

        $foto = $row['foto']
            ? "<img src='".base_url('tampilberita/'.$row['foto'])."' width='60'>"
            : "-";

        $pdf = $row['pdf']
            ? "<a target='_blank' href='".base_url('uploads/'.$row['pdf'])."'>PDF</a>"
            : "-";

        $data[] = [
            $row['id'],
            esc($row['judul']),
            substr(strip_tags($row['isi']),0,80).'...',
            $row['tanggal'],
            $foto,
            $pdf,
            "<button class='btn-delete' data-id='".$row['id']."'>Delete</button>",
              "<button class='btn-edit' data-id='".$row['id']."'>Edit</button>",

        ];
    }

    return $this->response->setJSON([
        "draw"=>intval($draw),
        "recordsTotal"=>$this->berita->countAll(),
        "recordsFiltered"=>$totalFiltered,
        "data"=>$data,
        "csrfHash"=>csrf_hash()
    ]);
}

    /* ===============================
       DELETE (CSRF SAFE)
    =============================== */
    public function delete()
    {
        $id = $this->request->getPost('id');

        if ($id) {
            $this->berita->delete($id);
        }

        return $this->response->setJSON([
            'status' => true,
            'csrfHash' => csrf_hash()
        ]);
    }
}