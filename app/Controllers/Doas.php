<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Dauo;

class Doas extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new Dauo();
    }

    /**
     * Simpan data DOAS + PDF
     */
    public function simpandoas()
    {
        $judul  = $this->request->getPost('judul');
        $isi    = $this->request->getPost('konten');
        $file   = $this->request->getFile('dokumen');

        $db = db_connect();

        // Ambil data DOAS (single row, id = 1)
        $cek = $db->table('pdf')->where('id', 1)->get()->getRow();

        // Default pakai PDF lama
        $namaPdf = $cek->pdf ?? '';

        // ======================
        // UPLOAD PDF BARU
        // ======================
        if ($file && $file->isValid() && !$file->hasMoved()) {

            if ($file->getClientMimeType() !== 'application/pdf') {
                return redirect()
                    ->to(site_url('apadoas'))
                    ->with('flasherror', 'File harus PDF');
            }

            // Nama PDF baru
            $pdfBaru = uniqid('doas_') . '.pdf';

            $file->move(
                WRITEPATH . 'uploads/pdf',
                $pdfBaru
            );

            // HAPUS PDF LAMA JIKA ADA
            if ($cek && !empty($cek->pdf)) {
                $pdfLamaPath = WRITEPATH . 'uploads/pdf/' . $cek->pdf;

                if (file_exists($pdfLamaPath)) {
                    unlink($pdfLamaPath);
                }
            }

            $namaPdf = $pdfBaru;
        }

        // Data ke database
        $data = [
            'judul' => $judul,
            'isi'   => $isi,
            'pdf'   => $namaPdf
        ];

        // ======================
        // INSERT / UPDATE
        // ======================
        if ($cek) {
            // UPDATE id = 1
            $result = $this->model->update_data(
                $data,
                'pdf',
                'id',
                1
            );
        } else {
            // INSERT pertama kali
            $data['id'] = 1;

            $result = $this->model->input_data(
                $data,
                'pdf'
            );
        }

        // ======================
        // FLASH MESSAGE
        // ======================
        if ($result === 'sukses') {
            return redirect()
                ->to(site_url('apadoas'))
                ->with('flashsuccess', 'Data DOAS berhasil disimpan');
        }

        return redirect()
            ->to(site_url('apadoas'))
            ->with('flasherror', 'Data DOAS gagal disimpan');
    }


    /**
     * Tampilkan PDF (seperti tampilfoto)
     */
    public function tampilpdf($filename)
    {
        $path = WRITEPATH . 'uploads/pdf/' . $filename;

        if (!file_exists($path) || empty($filename)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('PDF tidak ditemukan');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody(file_get_contents($path));
    }
    public function simpankantor()
    {
        // Ambil input dari form
        $jam       = $this->request->getPost('jam');
        $pulang  = $this->request->getPost('pulang');
        $batas_awal_absen = $this->request->getPost('batas_awal_absen');
        $batas_akhir_absen = $this->request->getPost('batas_akhir_absen');

        // Validasi sederhana
        if (!$jam || !$pulang || !$batas_awal_absen || !$batas_akhir_absen) {
            return redirect()->back()->with(
                'flasherror',
                'semua field wajib diisi'
            );
        }

        // Koneksi DB
        $db = \Config\Database::connect();
        $builder = $db->table('lokasi');

        // Cek apakah sudah ada data lokasi
        $existing = $builder->get()->getRow();

        if ($existing) {
            // ======================
            // UPDATE (hanya 1 baris)
            // ======================
            $builder->where('id', $existing->id)
                ->update([
                    'jam'       => $jam,
                    'pulang'  => $pulang,
                    'batasmulai' => $batas_awal_absen,
                    'batasakhir' => $batas_akhir_absen,
                ]);

            return redirect()->back()->with(
                'flashsuccess',
                'Lokasi kantor berhasil diperbarui'
            );
        } else {
            // ======================
            // INSERT (pertama kali)
            // ======================
            $builder->insert([
                'jam'       => $jam,
                'pulang'  => $pulang,
                'batasmulai' => $batas_awal_absen,
                'batasakhir' => $batas_akhir_absen,
            ]);

            return redirect()->back()->with(
                'flashsuccess',
                'Lokasi kantor berhasil disimpan'
            );
        }
    }
}
