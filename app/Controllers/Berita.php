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

    public function add()
    {
        $session  = session();
        $username = $session->get('username');
        $user     = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Tambah Berita',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'Home',
            'page'    => 'berita_add',
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
            0 => 'id',
            1 => 'judul',
            2 => 'isi',
            3 => 'tanggal',
            4 => 'foto',
            5 => 'pdf'
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
            $builder->like('judul', $search);
            $builder->orLike('isi', $search);
            $builder->groupEnd();
        }

        $totalFiltered = $builder->countAllResults(false);

        if ($order) {
            $builder->orderBy(
                $columns[$order[0]['column']],
                $order[0]['dir']
            );
        } else {
            $builder->orderBy('id', 'DESC');
        }

        $builder->limit($length, $start);

        $query = $builder->get()->getResultArray();

        $data = [];

        foreach ($query as $row) {

            $foto = $row['foto']
                ? "<img src='" . base_url('tampilberita/' . $row['foto']) . "' width='60'>"
                : "-";

            $pdf = $row['pdf']
                ? "<a target='_blank' href='" . site_url('berita/pdf/' . rawurlencode($row['pdf'])) . "'>PDF</a>"
                : "-";

            $data[] = [
                $row['id'],
                esc($row['judul']),
                substr(strip_tags($row['isi']), 0, 80) . '...',
                $row['tanggal'],
                $foto,
                $pdf,
                "<button class='btn-delete' data-id='" . $row['id'] . "'>Delete</button>",
                "<a class='btn-edit' href='" . site_url('berita/edit/' . $row['id']) . "'>Edit</a>",

            ];
        }

        return $this->response->setJSON([
            "draw" => intval($draw),
            "recordsTotal" => $this->berita->countAll(),
            "recordsFiltered" => $totalFiltered,
            "data" => $data,
            "csrfHash" => csrf_hash()
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

    public function save()
    {
        $judul   = trim((string) $this->request->getPost('judul'));
        $isi     = (string) $this->request->getPost('isi');
        $tanggal = (string) $this->request->getPost('tanggal');

        if ($judul === '' || trim(strip_tags($isi)) === '' || $tanggal === '') {
            return redirect()->back()->withInput()->with('flasherror', 'Judul, isi, dan tanggal wajib diisi');
        }

        $fotoName = null;
        $pdfName  = null;

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $ext      = strtolower($foto->getExtension());
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                return redirect()->back()->withInput()->with('flasherror', 'Foto harus jpg, jpeg, png, atau webp');
            }

            $uploadFotoDir = WRITEPATH . 'uploads/berita';
            if (!is_dir($uploadFotoDir)) {
                mkdir($uploadFotoDir, 0755, true);
            }

            $fotoName = $foto->getRandomName();
            $foto->move($uploadFotoDir, $fotoName);
        }

        $pdf = $this->request->getFile('pdf');
        if ($pdf && $pdf->isValid() && !$pdf->hasMoved()) {
            if ($pdf->getClientMimeType() !== 'application/pdf') {
                return redirect()->back()->withInput()->with('flasherror', 'Dokumen harus format PDF');
            }

            $uploadPdfDir = WRITEPATH . 'uploads/pdf';
            if (!is_dir($uploadPdfDir)) {
                mkdir($uploadPdfDir, 0755, true);
            }

            $pdfName = $pdf->getRandomName();
            $pdf->move($uploadPdfDir, $pdfName);
        }

        $this->berita->insert([
            'judul'   => $judul,
            'isi'     => $isi,
            'tanggal' => $tanggal,
            'foto'    => $fotoName,
            'pdf'     => $pdfName,
        ]);

        return redirect()->to(site_url('berita'))->with('flashsuccess', 'Berita berhasil ditambahkan');
    }
    public function edit($id = null)
    {
        $id = $id ?? $this->request->getPost('id') ?? $this->request->getGet('id');
        if (!$id && isset($this->request->uri)) {
            $id = $this->request->uri->getSegment(3);
        }

        if (!$id) {
            return redirect()->to(site_url('berita'))->with('flasherror', 'ID berita tidak ditemukan');
        }

        $item = $this->berita->find($id);
        if (!$item) {
            return redirect()->to(site_url('berita'))->with('flasherror', 'Data berita tidak ditemukan');
        }

        $session  = session();
        $username = $session->get('username');
        $user     = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Edit Berita',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'Home',
            'page'    => 'berita_edit',
            'item'    => $item,
        ]);
    }

    public function update()
    {
        $id      = $this->request->getPost('id');
        $judul   = $this->request->getPost('judul');
        $isi     = $this->request->getPost('isi');
        $tanggal = $this->request->getPost('tanggal');

        $item = $this->berita->find($id);
        if (!$item) {
            return redirect()->to(site_url('berita'))->with('flasherror', 'Data berita tidak ditemukan');
        }

        $fotoName = $item['foto'] ?? null;
        $pdfName  = $item['pdf'] ?? null;

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $ext      = strtolower($foto->getExtension());
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                return redirect()->back()->withInput()->with('flasherror', 'Foto harus jpg, jpeg, png, atau webp');
            }

            $newFoto = $foto->getRandomName();
            $foto->move(WRITEPATH . 'uploads/berita', $newFoto);

            if (!empty($fotoName)) {
                $oldFotoPath = WRITEPATH . 'uploads/berita/' . $fotoName;
                if (is_file($oldFotoPath)) {
                    unlink($oldFotoPath);
                }
            }
            $fotoName = $newFoto;
        }

        $pdf = $this->request->getFile('pdf');
        if ($pdf && $pdf->isValid() && !$pdf->hasMoved()) {
            $mime = $pdf->getClientMimeType();
            if ($mime !== 'application/pdf') {
                return redirect()->back()->withInput()->with('flasherror', 'Dokumen harus format PDF');
            }

            $newPdf = $pdf->getRandomName();
            $pdf->move(WRITEPATH . 'uploads/pdf', $newPdf);

            if (!empty($pdfName)) {
                $oldPdfPath = WRITEPATH . 'uploads/pdf/' . $pdfName;
                if (is_file($oldPdfPath)) {
                    unlink($oldPdfPath);
                }
            }
            $pdfName = $newPdf;
        }

        $this->berita->update($id, [
            'judul'   => $judul,
            'isi'     => $isi,
            'tanggal' => $tanggal,
            'foto'    => $fotoName,
            'pdf'     => $pdfName,
        ]);

        return redirect()->to(site_url('berita/edit/' . $id))->with('flashsuccess', 'Berita berhasil diperbarui');
    }

    public function tampilPdf($filename)
    {
        $filename = basename((string) $filename);
        $path = WRITEPATH . 'uploads/pdf/' . $filename;

        if (!$filename || !is_file($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('PDF tidak ditemukan');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody(file_get_contents($path));
    }
}
