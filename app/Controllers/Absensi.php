<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

use App\Models\Dauo;

class Absensi extends BaseController
{
    private array $absensiFields = [
        'id',
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
        'jabatan',
        'subdit',
        'pangkat',
        'nip',
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

    private array $fieldLabels = [
        'id' => 'ID',
        'userId' => 'User ID',
        'masuk' => 'Masuk',
        'pulang' => 'Pulang',
        'keterangan' => 'Keterangan',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'foto' => 'Foto',
        'foto2' => 'Foto2',
        'tanggal' => 'Tanggal',
        'selesai' => 'Selesai',
        'nama' => 'Nama',
        'jabatan' => 'Jabatan',
        'subdit' => 'Subdit',
        'pangkat' => 'Pangkat',
        'nip' => 'NIP',
        'ketam' => 'Ketam',
        'latpulang' => 'Lat Pulang',
        'lonpulang' => 'Lon Pulang',
        'fotopulang2' => 'Foto Pulang 2',
        'fotopulang' => 'Foto Pulang',
        'statuspulang' => 'Status Pulang',
        'statusmasuk' => 'Status Masuk',
        'ketpul' => 'Ket Pul',
        'sudahkah' => 'Sudahkah',
    ];

    public function __construct()
    {
        $this->dauo = new Dauo();
    }

    public function laporan()
    {
        $session = session();
        $username = $session->get('username');

        $user = $this->dauo->where('username', $username)->first();

        $model = new AbsensiModel();

        $subditRows = $model->builder()
            ->select('subdit')
            ->where('subdit IS NOT NULL')
            ->where("TRIM(subdit) !=", '')
            ->groupBy('subdit')
            ->orderBy('subdit', 'ASC')
            ->get()
            ->getResultArray();

        $subditList = array_map(static fn($r) => $r['subdit'], $subditRows);

        $defaultFields = array_keys($this->fieldLabels);

        return view('app', [
            'title'   => 'D.O.A.S - Laporan Absensi',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'Laporan Absensi',
            'subditList' => $subditList,
            'keteranganList' => ['DL', 'DIK', 'SAKIT', 'CUTI', 'BKO'],
            'fieldLabels' => $this->fieldLabels,
            'defaultFields' => $defaultFields,
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

        // SEARCH SEMUA FIELD BARU
        if ($searchValue) {
            $builder->groupStart();
            foreach ($this->absensiFields as $i => $field) {
                if ($i === 0) {
                    $builder->like($field, $searchValue);
                } else {
                    $builder->orLike($field, $searchValue);
                }
            }
            $builder->groupEnd();
        }

        return $builder;
    }

    private function getSelectedFields(): array
    {
        $selected = $this->request->getPost('selected_fields');

        if (!is_array($selected) || empty($selected)) {
            return array_keys($this->fieldLabels);
        }

        $allowed = array_keys($this->fieldLabels);
        $selected = array_values(array_intersect($selected, $allowed));

        return empty($selected) ? $allowed : $selected;
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

        $selectedFields = $this->getSelectedFields();

        $selectFields = array_values(array_unique(array_merge(['id'], $selectedFields)));

        $data = $builder
            ->select(implode(',', $selectFields))
            ->limit($length, $start)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $rows = [];
        $no = $start + 1;

        foreach ($data as $r) {
            $row = ["no" => $no++];
            foreach ($selectedFields as $field) {
                $row[$field] = $r[$field] ?? '';
            }
            $rows[] = $row;
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

        $jenis = ['DL', 'DIK', 'SAKIT', 'CUTI', 'BKO'];

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
        $selectedFields = $this->getSelectedFields();

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

        $selectFields = array_values(array_unique(array_merge(['id'], $selectedFields)));
        $data = $builder
            ->select(implode(',', $selectFields))
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        // =========================
        // HITUNG REKAP
        // =========================
        $allJenis = ['DL', 'DIK', 'SAKIT', 'CUTI', 'BKO'];

        if ($keterangan) {
            $rekapJenis = [$keterangan => 0];
        } else {
            $rekapJenis = array_fill_keys($allJenis, 0);
        }

        foreach ($data as $d) {
            $jenis = $d['keterangan'] ?? '';
            if (isset($rekapJenis[$jenis])) {
                $rekapJenis[$jenis]++;
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
        $lastDataCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(count($selectedFields), 1));

        $col = 'A';
        foreach ($selectedFields as $field) {
            $sheet->setCellValue($col . $headerRow, $this->fieldLabels[$field] ?? $field);
            $col++;
        }

        // style header tabel
        $sheet->getStyle("A{$headerRow}:{$lastDataCol}{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // DATA
        $row = $headerRow + 1;

        foreach ($data as $d) {
            $col = 'A';
            foreach ($selectedFields as $field) {
                $sheet->setCellValue($col . $row, (string) ($d[$field] ?? ''));
                $col++;
            }
            $row++;
        }

        // BORDER + alignment isi tabel
        if ($row > $headerRow + 1) {
            $sheet->getStyle("A" . ($headerRow + 1) . ":{$lastDataCol}" . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // TOTAL DATA
        $sheet->setCellValue('A' . ($row + 1), 'TOTAL DATA');
        $sheet->setCellValue('B' . ($row + 1), count($data));
        $sheet->getStyle('A' . ($row + 1) . ':B' . ($row + 1))->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

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
        if ($rekapRow > $rekapStart + 1) {
            $sheet->getStyle("A{$rekapStart}:B" . ($rekapRow - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
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
        $sheet->freezePane('A6');
        $sheet->setAutoFilter("A{$headerRow}:{$lastDataCol}{$headerRow}");

        $lastDataCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(count($selectedFields), 2));
        foreach (range('A', $lastDataCol) as $col) {
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
