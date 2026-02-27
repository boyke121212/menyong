<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

use App\Models\Dauo;

class Absensi extends BaseController
{
    public function __construct()
    {
        $this->dauo = new Dauo();
    }

    public function laporan()
    {
        $session = session();
        $username = $session->get('username');

        $user = $this->dauo->where('username', $username)->first();

        // Hardcode daftar subdit (tidak query database)
        $subditList = [
            'SUBDIT 1',
            'SUBDIT 2',
            'SUBDIT 3',
            'SUBDIT 4',
            'SUBDIT 5',
            'STAFF PIMPINAN'
        ];

        return view('app', [
            'title'   => 'D.O.A.S - Edit User',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'Edit',
            'subditList' => $subditList,
            'page'    => 'laporan_absensi'
        ]);
    }

    private function applyFilter($builder, $searchValue = null)
    {
        $subdit = $this->request->getPost('subdit');
        $dari = $this->request->getPost('dari');
        $sampai = $this->request->getPost('sampai');
        $keterangan = $this->request->getPost('keterangan');

        if ($subdit) {
            $builder->where('subdit', $subdit);
        }

        if ($dari && $sampai) {
            $builder->where('tanggal >=', $dari);
            $builder->where('tanggal <=', $sampai);
        }

        if ($keterangan) {
            $builder->where('keterangan', $keterangan);
        }

        // SEARCH SEMUA FIELD
        if ($searchValue) {
            $builder->groupStart()
                ->like('userId', $searchValue)
                ->orLike('masuk', $searchValue)
                ->orLike('pulang', $searchValue)
                ->orLike('keterangan', $searchValue)
                ->orLike('latitude', $searchValue)
                ->orLike('longitude', $searchValue)
                ->orLike('tanggal', $searchValue)
                ->orLike('selesai', $searchValue)
                ->orLike('nama', $searchValue)
                ->orLike('namapimpinan', $searchValue)
                ->orLike('jabatan', $searchValue)
                ->orLike('subdit', $searchValue)
                ->orLike('pangkat', $searchValue)
                ->orLike('nip', $searchValue)
                ->orLike('bulan', $searchValue)
                ->orLike('tipeizin', $searchValue)
                ->groupEnd();
        }

        return $builder;
    }


    public function ajaxList()
    {
        $model = new AbsensiModel();

        $builder = $model->builder();

        $searchValue = $this->request->getPost('search')['value'] ?? '';

        // APPLY FILTER + SEARCH
        $builder = $this->applyFilter($builder, $searchValue);

        // COUNT FILTERED
        $countBuilder = clone $builder;
        $recordsFiltered = $countBuilder->countAllResults(false);

        // PAGINATION (DATATABLES SERVER SIDE)
        $length = $this->request->getPost('length');
        $start  = $this->request->getPost('start');

        $data = $builder
            ->limit($length, $start)
            ->orderBy('tanggal', 'DESC')
            ->get()
            ->getResultArray();

        $rows = [];
        $no = $start + 1;

        foreach ($data as $r) {
            $rows[] = [
                "no" => $no++,
                "nama" => $r['nama'],
                "nip" => $r['nip'],
                "subdit" => $r['subdit'],
                "tanggal" => $r['tanggal'],
                "keterangan" => $r['keterangan'],
                "masuk" => $r['masuk'],
                "pulang" => $r['pulang'],
                "jabatan" => $r['jabatan'],
                "pangkat" => $r['pangkat'],
                "tipeizin" => $r['tipeizin'],
                "namapimpinan" => $r['namapimpinan'],
                "pangkatpimpinan" => $r['pangkatpimpinan'],
                "jabatanpimpinan" => $r['jabatanpimpinan']
            ];
        }


        return $this->response->setJSON([
            "draw" => intval($this->request->getPost('draw')),
            "recordsTotal" => $model->countAll(),
            "recordsFiltered" => $recordsFiltered,
            "data" => $rows
        ]);
    }
    public function grafik()
    {
        $db = \Config\Database::connect();

        $subdit      = $this->request->getPost('subdit');
        $dari        = $this->request->getPost('dari');
        $sampai      = $this->request->getPost('sampai');
        $keterangan  = $this->request->getPost('keterangan');

        $builder = $db->table('absensi');

        if ($subdit) {
            $builder->where('subdit', $subdit);
        }

        if ($dari && $sampai) {
            $builder->where('tanggal >=', $dari);
            $builder->where('tanggal <=', $sampai);
        }

        if ($keterangan) {
            $builder->where('keterangan', $keterangan);
        }

        $result = $builder
            ->select('keterangan, COUNT(*) as total')
            ->groupBy('keterangan')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($result);
    }


    public function ajaxRekap()
    {
        $model = new AbsensiModel();
        $builder = $model->builder();

        $builder = $this->applyFilter($builder);

        $builder->select("keterangan, COUNT(*) as total");
        $builder->groupBy("keterangan");

        $result = $builder->get()->getResultArray();

        $jenis = ['HADIR', 'TERLAMBAT', 'TK', 'LD', 'CUTI', 'DIK', 'BKO', 'DINAS', 'SAKIT', 'IZIN'];

        $rekap = array_fill_keys($jenis, 0);

        foreach ($result as $r) {
            if (isset($rekap[$r['keterangan']])) {
                $rekap[$r['keterangan']] = $r['total'];
            }
        }

        return $this->response->setJSON($rekap);
    }


    public function export()
    {
        $db = \Config\Database::connect();

        // ambil dari POST (form export)
        $subdit      = $this->request->getPost('subdit');
        $dari        = $this->request->getPost('dari');
        $sampai      = $this->request->getPost('sampai');
        $keterangan  = $this->request->getPost('keterangan');

        $builder = $db->table('absensi');

        // FILTER
        if ($subdit) {
            $builder->where('subdit', $subdit);
        }

        if ($dari && $sampai) {
            $builder->where('tanggal >=', $dari);
            $builder->where('tanggal <=', $sampai);
        }

        if ($keterangan) {
            $builder->where('keterangan', $keterangan);
        }

        $data = $builder->orderBy('tanggal', 'DESC')->get()->getResultArray();

        // =========================
        // HITUNG REKAP
        // =========================
        $allJenis = [
            'HADIR',
            'TERLAMBAT',
            'TK',
            'LD',
            'CUTI',
            'DIK',
            'BKO',
            'DINAS',
            'SAKIT',
            'IZIN'
        ];

        if ($keterangan) {
            $rekapJenis = [$keterangan => 0];
        } else {
            $rekapJenis = array_fill_keys($allJenis, 0);
        }

        foreach ($data as $d) {
            if (isset($rekapJenis[$d['keterangan']])) {
                $rekapJenis[$d['keterangan']]++;
            }
        }

        // =========================
        // SPREADSHEET INIT
        // =========================
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ROW HEIGHT
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(30);

        // MERGE
        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:G1');
        $sheet->mergeCells('C2:G2');

        // LOGO
        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setPath($logoPath);
            $logo->setHeight(70);
            $logo->setCoordinates('A1');
            $logo->setWorksheet($sheet);
        }

        // JUDUL
        $sheet->setCellValue('C1', 'LAPORAN ABSENSI DITTIPIDTER');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);

        // PERIODE
        $periodeText = "Semua Data";

        if ($dari && $sampai) {
            $periodeText = "Periode: {$dari} s/d {$sampai}";
        }
        if ($subdit) {
            $periodeText .= " | Subdit: {$subdit}";
        }
        if ($keterangan) {
            $periodeText .= " | Keterangan: {$keterangan}";
        }

        $sheet->setCellValue('C2', $periodeText);
        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('d-m-Y H:i:s'));

        // =========================
        // HEADER TABLE
        // =========================
        $headerRow = 5;

        $headers = [
            'A' => 'Nama',
            'B' => 'NIP',
            'C' => 'Subdit',
            'D' => 'Tanggal',
            'E' => 'Keterangan',
            'F' => 'Masuk',
            'G' => 'Pulang',
            'H' => 'Tipe Izin',
            'I' => 'Nama Pimpinan',
            'J' => 'Pangkat Pimpinan',
            'K' => 'Jabatan Pimpinan',
            'L' => 'Keterangan Tambahan',

        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // DATA
        $row = $headerRow + 1;

        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['nama']);
            $sheet->setCellValue('B' . $row, $d['nip']);
            $sheet->setCellValue('C' . $row, $d['subdit']);
            $sheet->setCellValue('D' . $row, $d['tanggal']);
            $sheet->setCellValue('E' . $row, $d['keterangan']);
            $sheet->setCellValue('F' . $row, $d['masuk']);
            $sheet->setCellValue('G' . $row, $d['pulang']);
            $sheet->setCellValue('H' . $row, $d['tipeizin']);
            $sheet->setCellValue('I' . $row, $d['namapimpinan']);
            $sheet->setCellValue('J' . $row, $d['pangkatpimpinan']);
            $sheet->setCellValue('K' . $row, $d['jabatanpimpinan']);
            $sheet->setCellValue('L' . $row, $d['ketam']);
            $row++;
        }

        // BORDER
        if ($row > $headerRow + 1) {
            $sheet->getStyle("A{$headerRow}:L" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // TOTAL DATA
        $sheet->setCellValue('E' . ($row + 1), 'TOTAL DATA');
        $sheet->setCellValue('F' . ($row + 1), count($data));

        // =========================
        // REKAP
        // =========================
        $rekapStart = $row + 3;
        $sheet->setCellValue('A' . $rekapStart, 'REKAP ABSENSI');
        $sheet->getStyle('A' . $rekapStart)->getFont()->setBold(true);

        $rekapRow = $rekapStart + 1;

        foreach ($rekapJenis as $jenis => $jumlah) {
            $sheet->setCellValue('A' . $rekapRow, $jenis);
            $sheet->setCellValue('B' . $rekapRow, $jumlah);
            $rekapRow++;
        }

        // =========================
        // CHART REKAP
        // =========================
        $chartStartRow = $rekapStart + 1;
        $chartEndRow   = $rekapRow - 1;

        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "Worksheet!A{$chartStartRow}:A{$chartEndRow}",
                null,
                ($chartEndRow - $chartStartRow + 1)
            ),
        ];

        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "Worksheet!B{$chartStartRow}:B{$chartEndRow}",
                null,
                ($chartEndRow - $chartStartRow + 1)
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            [],
            $labels,
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend   = null;
        $title    = new Title('Grafik Rekap Absensi');

        $chart = new Chart(
            'chart_rekap',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('D' . $rekapStart);
        $chart->setBottomRightPosition('K' . ($rekapStart + 15));

        $sheet->addChart($chart);

        // AUTOSIZE
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // OUTPUT
        $filename = 'LAPORAN_ABSENSI_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true); // penting
        $writer->save('php://output');
        exit;
    }



    public function exporssst()
    {
        $model = new AbsensiModel();
        $builder = $model->builder();

        $subdit = $this->request->getGet('subdit');
        $dari = $this->request->getGet('dari');
        $sampai = $this->request->getGet('sampai');
        $keterangan = $this->request->getGet('keterangan');

        if ($subdit) $builder->where('subdit', $subdit);
        if ($dari && $sampai) {
            $builder->where('tanggal >=', $dari);
            $builder->where('tanggal <=', $sampai);
        }
        if ($keterangan) $builder->where('keterangan', $keterangan);

        $rows = $builder->orderBy('tanggal', 'DESC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['Nama', 'NIP', 'Subdit', 'Tanggal', 'Keterangan', 'Masuk', 'Pulang'],
            NULL,
            'A1'
        );

        $rowNum = 2;

        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['nama'],
                $r['nip'],
                $r['subdit'],
                $r['tanggal'],
                $r['keterangan'],
                $r['masuk'],
                $r['pulang']
            ], NULL, 'A' . $rowNum);
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="laporan_absensi.xlsx"');
        $writer->save('php://output');
        exit;
    }
}