<?php

namespace App\Controllers;

use App\Models\AnggaranDetailModel;
use App\Models\Deden;
use App\Models\TahunAnggaranModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $session = session();
        $tahunModel = new TahunAnggaranModel();
        $detailModel = new AnggaranDetailModel();
        $userModel = new Deden();

        // Prioritas tahun: query param -> tahun sekarang -> tahun terbaru di detail
        $tahunAktif = $this->request->getGet('tahun') ?? $this->request->getGet('tahun_anggaran');
        $tahunAktif = is_numeric($tahunAktif) ? (int) $tahunAktif : null;

        if ($tahunAktif === null) {
            $tahunSekarang = (int) date('Y');
            $cekSekarang = $detailModel->where('tahun', $tahunSekarang)->countAllResults();
            if ($cekSekarang > 0) {
                $tahunAktif = $tahunSekarang;
            }
        }

        if ($tahunAktif === null) {
            $tahunTerbaru = $detailModel->builder()
                ->select('tahun')
                ->groupBy('tahun')
                ->orderBy('tahun', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            if (!empty($tahunTerbaru['tahun']) && is_numeric(trim((string) $tahunTerbaru['tahun']))) {
                $tahunAktif = (int) trim((string) $tahunTerbaru['tahun']);
            }
        }

        // Ambil bulan_awal dari tabel tahun_anggaran jika ada, kalau tidak default Jan
        $bulanAwal = 1;
        if ($tahunAktif !== null) {
            $tahunAktifRow = $tahunModel->where('tahun', $tahunAktif)->first();
            if (!empty($tahunAktifRow['bulan_awal']) && (int) $tahunAktifRow['bulan_awal'] >= 1 && (int) $tahunAktifRow['bulan_awal'] <= 12) {
                $bulanAwal = (int) $tahunAktifRow['bulan_awal'];
            }
        }

        $rows = [];
        if ($tahunAktif !== null) {
            $rows = $detailModel->where('tahun', $tahunAktif)->findAll();
        }

        $dataMap = [];
        $summarySubdit = [];
        $grafikSubdit = [];

        foreach ($rows as $r) {
            $subdit = $r['subdit'];
            $bulan = (int) $r['bulan'];

            $dataMap[$subdit][$bulan] = $r;

            if (!isset($summarySubdit[$subdit])) {
                $summarySubdit[$subdit] = [
                    'diajukan' => 0,
                    'terserap' => 0,
                    'persen' => 0
                ];
            }

            if ($summarySubdit[$subdit]['diajukan'] == 0 && (int) $r['anggaran_diajukan'] > 0) {
                $summarySubdit[$subdit]['diajukan'] = (int) $r['anggaran_diajukan'];
            }

            $summarySubdit[$subdit]['terserap'] += (int) $r['anggaran_terserap'];

            $persenBulanan = 0;
            if ((int) $r['anggaran_diajukan'] > 0) {
                $persenBulanan = round((((int) $r['anggaran_terserap'] / (int) $r['anggaran_diajukan']) * 100), 2);
            }

            $grafikSubdit[$subdit][$bulan] = $persenBulanan;
        }

        foreach ($summarySubdit as $k => $v) {
            $summarySubdit[$k]['persen'] = $v['diajukan'] > 0
                ? round(($v['terserap'] / $v['diajukan']) * 100, 2)
                : 0;
        }

        $userTerakhir = $userModel
            ->select('pangkat, username, name, jabatan, subdit, createdDtm')
            ->orderBy('createdDtm', 'DESC')
            ->orderBy('userId', 'DESC')
            ->findAll(5);

        return view('app', [
            'title'   => 'D.O.A.S - Dashboard',
            'keadaan' => 'Home',
            'page'    => 'dashboard',

            // session data
            'userId'  => $session->get('userId'),
            'nama'    => $session->get('name'),
            'username' => $session->get('username'),
            'role'    => $session->get('role'),
            'tahunAktif' => $tahunAktif,
            'bulanAwal' => $bulanAwal,
            'dataMap' => $dataMap,
            'summarySubdit' => $summarySubdit,
            'grafikSubdit' => $grafikSubdit,
            'userTerakhir' => $userTerakhir,
        ]);
    }
}
