<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Dauo;
use App\Models\Deden;
use Config\Database;

class Doas extends BaseController
{
    protected $model;
    protected Deden $userModel;
    protected $db;

    public function __construct()
    {
        $this->model = new Dauo();
        $this->userModel = new Deden();
        $this->db = Database::connect();
    }

    private function ensureLogKantorTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `logkantor` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(20) NOT NULL,
                `actorUserId` INT NULL,
                `actorUsername` VARCHAR(100) NULL,
                `actorName` VARCHAR(150) NULL,
                `description` TEXT NULL,
                `oldData` LONGTEXT NULL,
                `newData` LONGTEXT NULL,
                `ipAddress` VARCHAR(45) NULL,
                `userAgent` TEXT NULL,
                `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_createdAt` (`createdAt`),
                KEY `idx_action` (`action`),
                KEY `idx_actorUserId` (`actorUserId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query($sql);
    }

    private function ensureLogAboutTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `logabout` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(20) NOT NULL,
                `actorUserId` INT NULL,
                `actorUsername` VARCHAR(100) NULL,
                `actorName` VARCHAR(150) NULL,
                `description` TEXT NULL,
                `oldData` LONGTEXT NULL,
                `newData` LONGTEXT NULL,
                `ipAddress` VARCHAR(45) NULL,
                `userAgent` TEXT NULL,
                `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_createdAt` (`createdAt`),
                KEY `idx_action` (`action`),
                KEY `idx_actorUserId` (`actorUserId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query($sql);
    }

    private function writeLogKantor(string $action, string $description, array $oldData, array $newData): void
    {
        $this->ensureLogKantorTable();

        $session = session();
        $actorUserId = $session->get('userId');
        $actor = null;

        if ($actorUserId) {
            $actor = $this->userModel->where('userId', $actorUserId)->first();
        }

        $agent = $this->request->getUserAgent();

        $this->db->table('logkantor')->insert([
            'action' => $action,
            'actorUserId' => $actor['userId'] ?? $actorUserId ?? null,
            'actorUsername' => $actor['username'] ?? $session->get('username'),
            'actorName' => $actor['name'] ?? $session->get('name'),
            'description' => $description,
            'oldData' => !empty($oldData) ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            'newData' => !empty($newData) ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
            'ipAddress' => $this->request->getIPAddress(),
            'userAgent' => $agent ? $agent->getAgentString() : null,
        ]);
    }

    private function writeLogAbout(string $action, string $description, array $oldData, array $newData): void
    {
        $this->ensureLogAboutTable();

        $session = session();
        $actorUserId = $session->get('userId');
        $actor = null;

        if ($actorUserId) {
            $actor = $this->userModel->where('userId', $actorUserId)->first();
        }

        $agent = $this->request->getUserAgent();

        $this->db->table('logabout')->insert([
            'action' => $action,
            'actorUserId' => $actor['userId'] ?? $actorUserId ?? null,
            'actorUsername' => $actor['username'] ?? $session->get('username'),
            'actorName' => $actor['name'] ?? $session->get('name'),
            'description' => $description,
            'oldData' => !empty($oldData) ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            'newData' => !empty($newData) ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
            'ipAddress' => $this->request->getIPAddress(),
            'userAgent' => $agent ? $agent->getAgentString() : null,
        ]);
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

        $oldData = [
            'judul' => $cek->judul ?? null,
            'isi' => $cek->isi ?? null,
            'pdf' => $cek->pdf ?? null,
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
            $newData = [
                'judul' => $data['judul'],
                'isi' => $data['isi'],
                'pdf' => $data['pdf'],
            ];

            $this->writeLogAbout(
                $cek ? 'UPDATE_ABOUT' : 'CREATE_ABOUT',
                $cek ? 'Mengubah konten About DOAS' : 'Membuat konten About DOAS',
                $cek ? $oldData : [],
                $newData
            );

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

        $newData = [
            'jam' => $jam,
            'pulang' => $pulang,
            'batasmulai' => $batas_awal_absen,
            'batasakhir' => $batas_akhir_absen,
        ];

        if ($existing) {
            // ======================
            // UPDATE (hanya 1 baris)
            // ======================
            $oldData = [
                'jam' => $existing->jam ?? null,
                'pulang' => $existing->pulang ?? null,
                'batasmulai' => $existing->batasmulai ?? null,
                'batasakhir' => $existing->batasakhir ?? null,
            ];

            $isChanged = false;
            foreach ($newData as $key => $value) {
                if ((string) ($oldData[$key] ?? '') !== (string) $value) {
                    $isChanged = true;
                    break;
                }
            }

            $builder->where('id', $existing->id)->update($newData);

            if ($isChanged) {
                $this->writeLogKantor(
                    'UPDATE_KANTOR',
                    'Mengubah pengaturan jam kantor',
                    $oldData,
                    $newData
                );
            }

            return redirect()->back()->with(
                'flashsuccess',
                'Lokasi kantor berhasil diperbarui'
            );
        } else {
            // ======================
            // INSERT (pertama kali)
            // ======================
            $builder->insert($newData);

            $this->writeLogKantor(
                'CREATE_KANTOR',
                'Membuat pengaturan jam kantor pertama kali',
                [],
                $newData
            );

            return redirect()->back()->with(
                'flashsuccess',
                'Lokasi kantor berhasil disimpan'
            );
        }
    }
}
