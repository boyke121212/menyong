<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TahunAnggaranModel;
use App\Models\AnggaranDetailModel;
use App\Models\Dauo;
use App\Models\Deden;
use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;


class Anggaran extends BaseController
{
    protected Dauo $dauo;
    protected Deden $userModel;
    protected $db;

    public function __construct()
    {
        $this->dauo = new Dauo();
        $this->userModel = new Deden();
        $this->db = Database::connect();
    }

    private function ensureLogAnggaranTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `loganggaran` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(40) NOT NULL,
                `tahun` VARCHAR(10) NULL,
                `actorUserId` INT NULL,
                `actorUsername` VARCHAR(100) NULL,
                `actorName` VARCHAR(150) NULL,
                `description` TEXT NULL,
                `payload` LONGTEXT NULL,
                `ipAddress` VARCHAR(45) NULL,
                `userAgent` TEXT NULL,
                `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_createdAt` (`createdAt`),
                KEY `idx_action` (`action`),
                KEY `idx_tahun` (`tahun`),
                KEY `idx_actorUserId` (`actorUserId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query($sql);
    }

    private function writeLogAnggaran(
        string $action,
        ?string $tahun = null,
        string $description = '',
        array $payload = []
    ): void {
        $this->ensureLogAnggaranTable();

        $session = session();
        $actorUserId = $session->get('userId');
        $actor = null;

        if ($actorUserId) {
            $actor = $this->userModel->where('userId', $actorUserId)->first();
        }

        $agent = $this->request->getUserAgent();

        $this->db->table('loganggaran')->insert([
            'action' => $action,
            'tahun' => $tahun,
            'actorUserId' => $actor['userId'] ?? $actorUserId ?? null,
            'actorUsername' => $actor['username'] ?? $session->get('username'),
            'actorName' => $actor['name'] ?? $session->get('name'),
            'description' => $description,
            'payload' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'ipAddress' => $this->request->getIPAddress(),
            'userAgent' => $agent ? $agent->getAgentString() : null,
        ]);
    }
    public function index()
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->route('asktoin');
        }

        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        if (! $user) {
            $session->destroy();
            return redirect()->route('asktoin');
        }

        $roleId = (int) $user['roleId'];

        if ($roleId === 8) {
            $session->destroy();
            $session->setFlashdata('flashfail', 'Anda Tidak Memiliki Akses Backend');
            return redirect()->route('asktoin');
        }

        $tahun = $this->request->getGet('tahun_anggaran') ?? date('Y');

        $tahunModel = new TahunAnggaranModel();
        $detailModel = new AnggaranDetailModel();

        $tahunRow = $tahunModel->where('tahun', $tahun)->first();

        $dataAda = $tahunRow ? true : false;
        $bulanAwal = $tahunRow['bulan_awal'] ?? null;

        $rows = $detailModel->where('tahun', $tahun)->findAll();

        $dataMap = [];
        $ranking = [];
        $grafikSubdit = [];
        $totalDiajukan = 0;
        $totalTerserap = 0;

        foreach ($rows as $r) {

            $dataMap[$r['subdit']][$r['bulan']] = $r;

            $totalDiajukan += $r['anggaran_diajukan'];
            $totalTerserap += $r['anggaran_terserap'];

            if (!isset($ranking[$r['subdit']])) {
                $ranking[$r['subdit']] = [
                    'diajukan' => 0,
                    'terserap' => 0,
                    'persen' => 0
                ];
            }

            $ranking[$r['subdit']]['diajukan'] += $r['anggaran_diajukan'];
            $ranking[$r['subdit']]['terserap'] += $r['anggaran_terserap'];

            $grafikSubdit[$r['subdit']][$r['bulan']] = $r['anggaran_terserap'];
        }

        foreach ($ranking as $k => $v) {
            $ranking[$k]['persen'] =
                $v['diajukan'] > 0 ? round(($v['terserap'] / $v['diajukan']) * 100, 2) : 0;
        }

        $persenTotal = $totalDiajukan > 0
            ? round(($totalTerserap / $totalDiajukan) * 100, 2)
            : 0;

        return view('app', [
            'title'   => 'D.O.A.S - Anggaran',
            'nama'    => $user['name'],
            'role'    => $roleId,
            'keadaan' => 'Home',
            'tahun' => $tahun,
            'dataAda' => $dataAda,
            'bulanAwal' => $bulanAwal,
            'dataMap' => $dataMap,
            'ranking' => $ranking,
            'grafikSubdit' => $grafikSubdit,
            'totalDiajukan' => $totalDiajukan,
            'totalTerserap' => $totalTerserap,
            'persen' => $persenTotal,
            'page'    => 'anggaran',
        ]);
    }


    public function buatTahun()
    {
        $model = new TahunAnggaranModel();

        $tahun = $this->request->getPost('tahun_anggaran');
        $bulanAwal = $this->request->getPost('bulan_awal');

        $cek = $model->where('tahun', $tahun)->first();

        if (!$cek) {
            $model->insert([
                'tahun' => $tahun,
                'bulan_awal' => $bulanAwal,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->writeLogAnggaran(
                'ADD_TAHUN_ANGGARAN',
                (string) $tahun,
                'Menambahkan tahun anggaran',
                [
                    'new' => [
                        'tahun' => $tahun,
                        'bulan_awal' => $bulanAwal,
                    ]
                ]
            );
        }

        return redirect()->to(base_url('anggaran?tahun_anggaran=' . $tahun));
    }

    public function saveDetail()
    {
        $model = new AnggaranDetailModel();

        $tahun     = $this->request->getPost('tahun_anggaran');
        $bulan     = $this->request->getPost('bulan') ?? [];
        $subdit    = $this->request->getPost('subdit') ?? [];
        $terserap  = $this->request->getPost('terserap') ?? [];
        $pengajuan = $this->request->getPost('pengajuan') ?? [];

        if (count($bulan) === 0) {
            return redirect()
                ->to(base_url('anggaran/detail?tahun=' . $tahun))
                ->with('flasherror', 'Tidak ada data yang dikirim')
                ->withInput();
        }

        $errorList = [];
        $totalSerapanSubdit = [];

        // ================= VALIDASI =================
        for ($i = 0; $i < count($bulan); $i++) {

            $namaSubdit = $subdit[$i];

            $nilaiPengajuan = isset($pengajuan[$namaSubdit])
                ? (int)$pengajuan[$namaSubdit]
                : 0;

            $nilaiTerserap = ($terserap[$i] !== '' && $terserap[$i] !== null)
                ? (int)$terserap[$i]
                : 0;

            // akumulasi total serapan per subdit
            if (!isset($totalSerapanSubdit[$namaSubdit])) {
                $totalSerapanSubdit[$namaSubdit] = 0;
            }

            $totalSerapanSubdit[$namaSubdit] += $nilaiTerserap;

            // validasi dasar
            if ($nilaiPengajuan <= 0 && $nilaiTerserap > 0) {
                $errorList[$namaSubdit] = "$namaSubdit Pengajuan harus diisi sebelum memasukkan serapan";
            }
        }

        // validasi total serapan > pengajuan
        foreach ($totalSerapanSubdit as $namaSubdit => $totalSerap) {

            $nilaiPengajuan = isset($pengajuan[$namaSubdit])
                ? (int)$pengajuan[$namaSubdit]
                : 0;

            if ($nilaiPengajuan > 0 && $totalSerap > $nilaiPengajuan) {
                $errorList[$namaSubdit] =
                    "$namaSubdit Total serapan (" .
                    number_format($totalSerap) .
                    ") melebihi pengajuan (" .
                    number_format($nilaiPengajuan) . ")";
            }
        }

        // ================= CEK ERROR =================
        if (!empty($errorList)) {
            return redirect()
                ->to(base_url('anggaran/detail?tahun=' . $tahun))
                ->with('validationErrors', $errorList)
                ->with('flashsalah', 'Terdapat kesalahan input')
                ->withInput();
        }

        // ================= SIMPAN =================
        $changes = [];
        for ($i = 0; $i < count($bulan); $i++) {

            $namaSubdit = $subdit[$i];

            $nilaiPengajuan = isset($pengajuan[$namaSubdit])
                ? (int)$pengajuan[$namaSubdit]
                : 0;

            $nilaiTerserap = ($terserap[$i] !== '' && $terserap[$i] !== null)
                ? (int)$terserap[$i]
                : 0;

            if ($nilaiPengajuan <= 0) {
                $nilaiTerserap = 0;
            }

            $data = [
                'tahun' => $tahun,
                'bulan' => $bulan[$i],
                'subdit' => $namaSubdit,
                'anggaran_diajukan' => $nilaiPengajuan,
                'anggaran_terserap' => $nilaiTerserap,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $existing = $model->where([
                'tahun' => $tahun,
                'bulan' => $bulan[$i],
                'subdit' => $namaSubdit
            ])->first();

            $old = null;
            if ($existing) {
                $old = [
                    'anggaran_diajukan' => (int) ($existing['anggaran_diajukan'] ?? 0),
                    'anggaran_terserap' => (int) ($existing['anggaran_terserap'] ?? 0),
                ];
            }

            if ($existing) {
                $model->update($existing['id'], $data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $model->insert($data);
            }

            $new = [
                'anggaran_diajukan' => (int) $nilaiPengajuan,
                'anggaran_terserap' => (int) $nilaiTerserap,
            ];

            if ($old === null || $old['anggaran_diajukan'] !== $new['anggaran_diajukan'] || $old['anggaran_terserap'] !== $new['anggaran_terserap']) {
                $changes[] = [
                    'subdit' => $namaSubdit,
                    'bulan' => $bulan[$i],
                    'old' => $old,
                    'new' => $new,
                ];
            }
        }

        if (!empty($changes)) {
            $this->writeLogAnggaran(
                'LOG_DETAIL_ANGGARAN',
                (string) $tahun,
                'Mengubah detail anggaran',
                [
                    'tahun' => $tahun,
                    'changed_count' => count($changes),
                    'changes' => $changes,
                ]
            );
        }

        return redirect()
            ->to(base_url('anggaran/detail?tahun=' . $tahun))
            ->with('flashsuccess', 'Anggaran berhasil disimpan');
    }





    public function detail()
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->route('asktoin');
        }

        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        if (! $user) {
            $session->destroy();
            return redirect()->route('asktoin');
        }

        $roleId = (int) $user['roleId'];

        if ($roleId === 8) {
            $session->destroy();
            $session->setFlashdata('flashfail', 'Anda Tidak Memiliki Akses Backend');
            return redirect()->route('asktoin');
        }

        // ambil tahun dari POST atau GET
        $tahun = $this->request->getPost('tahun');
        if (!$tahun) {
            $tahun = $this->request->getGet('tahun');
        }

        if (!$tahun) {
            return redirect()->to(base_url('anggaran'));
        }

        $tahunModel  = new TahunAnggaranModel();
        $detailModel = new AnggaranDetailModel();

        $tahunRow = $tahunModel->where('tahun', $tahun)->first();

        if (!$tahunRow) {
            return redirect()->to(base_url('anggaran'))
                ->with('flasherror', 'Tahun anggaran tidak ditemukan');
        }

        $bulanAwal = $tahunRow['bulan_awal'];

        $rows = $detailModel->where('tahun', $tahun)->findAll();

        $dataMap = [];
        $grafikSubdit = [];
        $summarySubdit = [];

        foreach ($rows as $r) {

            $dataMap[$r['subdit']][$r['bulan']] = $r;

            // inisialisasi summary subdit
            if (!isset($summarySubdit[$r['subdit']])) {
                $summarySubdit[$r['subdit']] = [
                    'diajukan' => 0,
                    'terserap' => 0,
                    'persen' => 0
                ];
            }

            // ambil pengajuan hanya sekali
            if ($summarySubdit[$r['subdit']]['diajukan'] == 0 && $r['anggaran_diajukan'] > 0) {
                $summarySubdit[$r['subdit']]['diajukan'] = $r['anggaran_diajukan'];
            }

            // serapan tetap dijumlahkan
            $summarySubdit[$r['subdit']]['terserap'] += $r['anggaran_terserap'];

            // persen bulanan untuk grafik
            $persenBulanan = 0;
            if ($r['anggaran_diajukan'] > 0) {
                $persenBulanan = round(
                    ($r['anggaran_terserap'] / $r['anggaran_diajukan']) * 100,
                    2
                );
            }

            $grafikSubdit[$r['subdit']][$r['bulan']] = $persenBulanan;
        }

        // hitung persen total per subdit
        foreach ($summarySubdit as $k => $v) {
            $summarySubdit[$k]['persen'] =
                $v['diajukan'] > 0
                ? round(($v['terserap'] / $v['diajukan']) * 100, 2)
                : 0;
        }

        return view('app', [
            'title'   => 'D.O.A.S - Dashboard',
            'nama'    => $user['name'],
            'role'    => $roleId,
            'keadaan' => 'Detail Anggaran',
            'tahun'      => $tahun,
            'bulanAwal'  => $bulanAwal,
            'dataMap'    => $dataMap,
            'grafikSubdit' => $grafikSubdit,
            'summarySubdit' => $summarySubdit,
            'page'    => 'anggaran_detail'
        ]);
    }

    public function deleteTahun()
    {
        $tahun = $this->request->getPost('tahun_anggaran');

        $tahunModel = new TahunAnggaranModel();
        $detailModel = new AnggaranDetailModel();

        $tahunData = $tahunModel->where('tahun', $tahun)->first();
        $detailCount = $detailModel->where('tahun', $tahun)->countAllResults();

        $tahunModel->where('tahun', $tahun)->delete();
        $detailModel->where('tahun', $tahun)->delete();

        $this->writeLogAnggaran(
            'DELETE_TAHUN_ANGGARAN',
            (string) $tahun,
            'Menghapus tahun anggaran',
            [
                'deleted_tahun' => $tahunData,
                'deleted_detail_count' => $detailCount,
            ]
        );

        return redirect()->to(base_url('anggaran'));
    }

    public function resetDetail()
    {
        $model = new AnggaranDetailModel();

        $tahun = $this->request->getPost('tahun_anggaran');

        if (!$tahun) {
            return redirect()->to(base_url('anggaran'))
                ->with('flasherror', 'Tahun tidak valid');
        }

        $beforeCount = $model->where('tahun', $tahun)->countAllResults();

        // hapus semua detail tahun tersebut
        $model->where('tahun', $tahun)->delete();

        $this->writeLogAnggaran(
            'RESET_DETAIL_ANGGARAN',
            (string) $tahun,
            'Mereset detail anggaran',
            [
                'tahun' => $tahun,
                'deleted_detail_count' => $beforeCount,
            ]
        );

        return redirect()->to(base_url('anggaran/detail?tahun=' . $tahun))
            ->with('flashsuccess', 'Detail anggaran berhasil direset');
    }

    public function exportDetail()
    {
        $tahun = $this->request->getPost('tahun_anggaran');

        $detailModel = new AnggaranDetailModel();
        $tahunModel  = new TahunAnggaranModel();

        $tahunRow = $tahunModel->where('tahun', $tahun)->first();
        if (!$tahunRow) {
            return redirect()->back()->with('flasherror', 'Tahun tidak ditemukan');
        }

        $bulanAwal = $tahunRow['bulan_awal'];
        $rows = $detailModel->where('tahun', $tahun)->findAll();

        $bulanList = [
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "Mei",
            6 => "Jun",
            7 => "Jul",
            8 => "Agu",
            9 => "Sep",
            10 => "Okt",
            11 => "Nov",
            12 => "Des"
        ];

        $subditList = [
            "Subdit 1",
            "Subdit 2",
            "Subdit 3",
            "Subdit 4",
            "Subdit 5",
            "Subdit Staff Pimpinan"
        ];

        // mapping data
        $dataMap = [];
        $summarySubdit = [];
        $grafikSubdit = [];

        foreach ($rows as $r) {

            $dataMap[$r['subdit']][$r['bulan']] = $r;

            if (!isset($summarySubdit[$r['subdit']])) {
                $summarySubdit[$r['subdit']] = [
                    'diajukan' => 0,
                    'terserap' => 0,
                    'persen' => 0
                ];
            }

            if ($summarySubdit[$r['subdit']]['diajukan'] == 0 && $r['anggaran_diajukan'] > 0) {
                $summarySubdit[$r['subdit']]['diajukan'] = $r['anggaran_diajukan'];
            }

            $summarySubdit[$r['subdit']]['terserap'] += $r['anggaran_terserap'];

            // persen bulanan untuk grafik
            $persenBulanan = 0;
            if ($r['anggaran_diajukan'] > 0) {
                $persenBulanan = round(
                    ($r['anggaran_terserap'] / $r['anggaran_diajukan']) * 100,
                    2
                );
            }

            $grafikSubdit[$r['subdit']][$r['bulan']] = $persenBulanan;
        }

        foreach ($summarySubdit as $k => $v) {
            $summarySubdit[$k]['persen'] =
                $v['diajukan'] > 0
                ? round(($v['terserap'] / $v['diajukan']) * 100, 2)
                : 0;
        }

        // urutan bulan sesuai periode
        $bulanUrut = [];
        for ($i = 0; $i < 12; $i++) {
            $idx = (($bulanAwal + $i - 1) % 12) + 1;
            $bulanUrut[$idx] = $bulanList[$idx];
        }

        // ====================
        // SPREADSHEET
        // ====================
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // LOGO
        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setPath($logoPath);
            $logo->setHeight(60);
            $logo->setCoordinates('A1');
            $logo->setWorksheet($sheet);
        }

        // JUDUL
        $sheet->mergeCells('C1:H1');
        $sheet->setCellValue('C1', 'LAPORAN ANGGARAN TAHUN ' . $tahun);
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);

        // ====================
        // RINGKASAN
        // ====================
        $rowRingkasan = 5;

        $sheet->setCellValue("A{$rowRingkasan}", 'Subdit');
        $sheet->setCellValue("B{$rowRingkasan}", 'Diajukan');
        $sheet->setCellValue("C{$rowRingkasan}", 'Terserap');
        $sheet->setCellValue("D{$rowRingkasan}", 'Persen (%)');

        $sheet->getStyle("A{$rowRingkasan}:D{$rowRingkasan}")
            ->getFont()->setBold(true);

        $rowData = $rowRingkasan + 1;

        foreach ($subditList as $subdit) {

            $row = $summarySubdit[$subdit] ?? [
                'diajukan' => 0,
                'terserap' => 0,
                'persen' => 0
            ];

            $sheet->setCellValue("A{$rowData}", $subdit);
            $sheet->setCellValue("B{$rowData}", $row['diajukan']);
            $sheet->setCellValue("C{$rowData}", $row['terserap']);
            $sheet->setCellValue("D{$rowData}", $row['persen']);

            $rowData++;
        }

        // ====================
        // DETAIL TABLE
        // ====================
        $rowHeader = $rowData + 2;

        $sheet->setCellValue("A{$rowHeader}", 'Subdit');
        $sheet->setCellValue("B{$rowHeader}", 'Pengajuan');

        $colIndex = 'C';
        foreach ($bulanUrut as $bulanNama) {
            $sheet->setCellValue($colIndex . $rowHeader, $bulanNama . ' Serap');
            $colIndex++;
        }

        $rowExcel = $rowHeader + 1;

        foreach ($subditList as $subdit) {

            $sheet->setCellValue("A{$rowExcel}", $subdit);

            $pengajuanVal = $summarySubdit[$subdit]['diajukan'] ?? 0;
            $sheet->setCellValue("B{$rowExcel}", $pengajuanVal);

            $colIndex = 'C';

            foreach ($bulanUrut as $bulanAngka => $bulanNama) {
                $serap = $dataMap[$subdit][$bulanAngka]['anggaran_terserap'] ?? 0;
                $sheet->setCellValue($colIndex . $rowExcel, $serap);
                $colIndex++;
            }

            $rowExcel++;
        }

        // ====================
        // DATA GRAFIK
        // ====================
        $rowGrafikStart = $rowExcel + 2;

        $sheet->setCellValue("A{$rowGrafikStart}", "DATA GRAFIK PERSENTASE");
        $rowGrafikStart++;

        $sheet->setCellValue("A{$rowGrafikStart}", "Subdit");

        $col = 'B';
        foreach ($bulanUrut as $bulanNama) {
            $sheet->setCellValue($col . $rowGrafikStart, $bulanNama);
            $col++;
        }

        $rowGrafikStart++;
        $grafikDataStart = $rowGrafikStart;

        foreach ($subditList as $subdit) {

            $sheet->setCellValue("A{$rowGrafikStart}", $subdit);

            $col = 'B';
            foreach ($bulanUrut as $bulanAngka => $bulanNama) {
                $persen = $grafikSubdit[$subdit][$bulanAngka] ?? 0;
                $sheet->setCellValue($col . $rowGrafikStart, $persen);
                $col++;
            }

            $rowGrafikStart++;
        }

        $grafikDataEnd = $rowGrafikStart - 1;

        // ====================
        // CHART
        // ====================
        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "Worksheet!B" . ($grafikDataStart - 1) . ":" . chr(ord('A') + count($bulanUrut)) . ($grafikDataStart - 1),
                null,
                count($bulanUrut)
            ),
        ];

        $values = [];
        $categories = [];

        $rowIndex = $grafikDataStart;

        foreach ($subditList as $index => $subdit) {

            $values[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "Worksheet!B{$rowIndex}:" . chr(ord('A') + count($bulanUrut)) . "{$rowIndex}",
                null,
                count($bulanUrut)
            );

            $categories[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "Worksheet!A{$rowIndex}",
                null,
                1
            );

            $rowIndex++;
        }

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            $categories,
            $labels,
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend   = new Legend(Legend::POSITION_RIGHT, null, false);
        $title    = new Title('Grafik Persentase Serapan per Subdit');

        $chart = new Chart(
            'chart_serapan',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('A' . ($grafikDataEnd + 2));
        $chart->setBottomRightPosition('L' . ($grafikDataEnd + 20));

        $sheet->addChart($chart);

        // OUTPUT
        $filename = 'ANGGARAN_' . $tahun . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save('php://output');
        exit;
    }
}
