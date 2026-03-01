<?php

namespace App\Controllers\api;

use App\Models\Deden;
use App\Controllers\BaseController;
use App\Models\Dauo;
use App\Models\BeritaModel;
use App\Models\AbsensiModel;
use App\Models\Berita;
use App\Models\AnggaranDetailModel;
use App\Models\TahunAnggaranModel;
use Config\Services;
use App\Services\LoginAuditService;
use App\Services\AppServices;
use App\Traits\AntiReplayTrait;


class Cekdata extends BaseController
{
    protected Dauo $dauo;
    protected Berita $berita;
    protected Deden $userModel;
    public function __construct()
    {
        $this->userModel = new Deden();
        $this->dauo = new Dauo();
        $this->berita = new Berita();
        date_default_timezone_set('Asia/Jakarta');
    }
    use AntiReplayTrait;

    public function index() {}

    public function login()
    {

        $agent = $this->request->getUserAgent();




        // // 🔐 HARDENING RINGAN (ANTI REPLAY LOGIN)
        // if (!$this->validateAntiReplay($this->request)) {
        //     return $this->response
        //         ->setStatusCode(403)
        //         ->setJSON([
        //             'status'  => 'error',
        //             'message' => 'Request login tidak valid'
        //         ]);
        // }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $app_signature = $this->request->getPost('app_signature');
        $device_hash = $this->request->getPost('device_hash');
        $is_rooted = $this->request->getPost('is_rooted');
        $is_emulator = $this->request->getPost('is_emulator');
        $is_fake_gps = $this->request->getPost('is_fake_gps');
        $is_installer_valid = $this->request->getPost('is_installer_valid');
        /** @var \CodeIgniter\Throttle\Throttler $throttler */
        $throttler = service('throttler');

        // IP asli client dari Cloudflare
        $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
        if (!$clientIp) {
            $clientIp = $this->request->getIPAddress();
        }

        // 🔐 CACHE KEY AMAN (TANPA KARAKTER TERLARANG)
        $key = 'login_' . hash('sha256', $clientIp . hash('sha256', $username));

        if ($throttler->check($key, 5, 60) === false) {
            return $this->response
                ->setStatusCode(429)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Terlalu banyak percobaan login'
                ]);
        }
        // $rules = [
        //     ['value' => $is_emulator, 'deny_on' => '1', 'msg' => 'Emulator tidak diizinkan'],
        //     ['value' => $is_rooted, 'deny_on' => '1', 'msg' => 'Perangkat tidak aman'],
        //     ['value' => $is_fake_gps, 'deny_on' => '1', 'msg' => 'Fake GPS terdeteksi'],
        //     ['value' => $is_installer_valid, 'deny_on' => '0', 'msg' => 'Aplikasi tidak resmi'],
        // ];

        // foreach ($rules as $r) {
        //     if ((string)$r['value'] === (string)$r['deny_on']) {
        //         return $this->response->setStatusCode(403)
        //             ->setJSON(['message' => $r['msg']]);
        //     }
        // }

        if (!$username || !$password) {
            return $this->response->setStatusCode(400)
                ->setJSON(['message' => 'Username dan password wajib diisi']);
        }

        if (!$username || !$password) {
            return $this->response->setStatusCode(400)
                ->setJSON(['message' => 'Username dan password wajib diisi']);
        }


        try {
            $authService = new AppServices();
            $tokens = $authService->login($username, $password);
            $accessTokenHash = hash('sha256', $tokens['access_token']);
            $refreshTokenHash = hash('sha256', $tokens['refresh_token']);
            // 🔹 Update token ke database
            $this->userModel
                ->where('userId', $tokens['userId'])
                ->set([
                    'status'        => 'active',
                    'app_signature'        => $app_signature,
                    'device_hash'        => $device_hash,
                    'access_token'  => $accessTokenHash,
                    'refresh_token' => $refreshTokenHash,
                    'updatedDtm'    => date('Y-m-d H:i:s'),
                    'updatedBy'     => '1'
                ])
                ->update();

            $userLama = $this->userModel->where('username', $username)->first();
            $sessionArray = array(
                'userId' => $userLama['userId'],
                'role' => $userLama['roleId'],
                'name' => $userLama['name'],
                'username' => $userLama['username'],
                'lastLogin' => date('Y-m-d H:i:s'),
                'isLoggedIn' => TRUE,
                'last_activity' => time()   // ⬅️ WAJIB ADA
            );

            $loginInfo = array(
                "userId" => $userLama['userId'],
                "sessionData" => json_encode($sessionArray),
                "machineIp" => $_SERVER['REMOTE_ADDR'],
                "userAgent" => $_SERVER['HTTP_USER_AGENT'],
                "agentString" => $agent->getAgentString(),
                "platform" => $agent->getPlatform()
            );

            $auditService = new LoginAuditService();
            $hasil = $auditService->logLogin($loginInfo);



            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Login berhasil',
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(401)
                ->setJSON(['message' => $e->getMessage()]);
        }
    }



    public function authcheck()
    {
        // UID dari MobileFilter
        $uid = $this->request->uid ?? null;
        if (!$uid) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['message' => 'UID tidak ditemukan']);
        }

        // Ambil user
        $user = $this->userModel->find($uid);
        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['message' => 'User tidak ditemukan']);
        }

        // Generate AES key (per session)
        $aesKey = bin2hex(random_bytes(32)); // 32 hex (AES-256)
        $tahunReq = $this->request->getPost('tahun')
            ?? $this->request->getGet('tahun')
            ?? $this->request->getPost('tahun_anggaran')
            ?? $this->request->getGet('tahun_anggaran');
        $dashboard = $this->buildDashboardPayload($tahunReq);

        // Enkripsi nama user
        $encryptedName = $this->encryptAES($user['name'], $aesKey);

        // Ambil 3 berita terbaru
        // Ambil 3 berita terbaru
        $berita = $this->berita
            ->orderBy('id', 'DESC')
            ->limit(3)
            ->find();

        // ENKRIPSI FIELD BERITA
        foreach ($berita as &$b) {
            if (isset($b['judul']))   $b['judul'] = $this->encryptAES($b['judul'], $aesKey);
            if (isset($b['isi']))     $b['isi']   = $this->encryptAES($b['isi'], $aesKey);
            if (isset($b['foto']))    $b['foto']  = $this->encryptAES($b['foto'], $aesKey);
            if (isset($b['pdf']))     $b['pdf']   = $this->encryptAES($b['pdf'], $aesKey);
        }
        $dataUpdate = [
            'last'    => date('Y-m-d H:i:s')
        ];

        $this->dauo->update_data($dataUpdate, 'usn', 'userId', $uid);

        unset($b); // good practice

        // Response
        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'aes_key' => $aesKey,
                'name' => $encryptedName,
                'berita'  => $berita,
                'dashboard' => $dashboard
            ]);
    }

    private function buildDashboardPayload($requestedTahun = null): array
    {
        $tahunModel = new TahunAnggaranModel();
        $detailModel = new AnggaranDetailModel();
        $userModel = new Deden();

        $tahunAktif = is_numeric($requestedTahun) ? (int) $requestedTahun : null;

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
            $bulan = (int) ($r['bulan'] ?? 0);
            $diajukan = (int) ($r['anggaran_diajukan'] ?? 0);
            $terserap = (int) ($r['anggaran_terserap'] ?? 0);

            $dataMap[$subdit][$bulan] = $r;

            if (!isset($summarySubdit[$subdit])) {
                $summarySubdit[$subdit] = [
                    'diajukan' => 0,
                    'terserap' => 0,
                    'persen' => 0
                ];
            }

            if ($summarySubdit[$subdit]['diajukan'] === 0 && $diajukan > 0) {
                $summarySubdit[$subdit]['diajukan'] = $diajukan;
            }

            $summarySubdit[$subdit]['terserap'] += $terserap;

            $persenBulanan = 0;
            if ($diajukan > 0) {
                $persenBulanan = round((($terserap / $diajukan) * 100), 2);
            }

            $grafikSubdit[$subdit][$bulan] = $persenBulanan;
        }

        foreach ($summarySubdit as $subdit => $summary) {
            $summarySubdit[$subdit]['persen'] = $summary['diajukan'] > 0
                ? round(($summary['terserap'] / $summary['diajukan']) * 100, 2)
                : 0;
        }

        $userTerakhir = $userModel
            ->select('pangkat, username, name, jabatan, subdit, createdDtm')
            ->orderBy('createdDtm', 'DESC')
            ->orderBy('userId', 'DESC')
            ->findAll(5);

        return [
            'tahunAktif' => $tahunAktif,
            'bulanAwal' => $bulanAwal,
            'dataMap' => $dataMap,
            'summarySubdit' => $summarySubdit,
            'grafikSubdit' => $grafikSubdit,
            'userTerakhir' => $userTerakhir,
        ];
    }

    public function absen()
    {

        // // 🔐 HARDENING (HANYA API MOBILE)
        // if (!$this->validateAntiReplay($this->request)) {
        //     return $this->error('Request tidak valid (replay detected)', 403);
        // }

        // if (!$this->validateRequestSignature($this->request)) {
        //     return $this->error('Signature request tidak valid', 403);
        // }

        // ===== TIMEZONE FIX =====

        // ===== AUTH =====

        if (!$this->validateAntiReplay($this->request)) {
            return $this->error(
                'Request tidak valid (replay detected)',
                403
            );
        }
        $uid = $this->request->uid ?? null;
        if (!$uid) {
            return $this->error('UID tidak ditemukan', 401);
        }
        // ===== RATE LIMIT (ABSEN SENSITIF) =====
        $throttler = service('throttler');

        // ambil device hash (sudah kamu pakai di MobileFilter)
        $deviceHash = $this->request->getHeaderLine('X-Device-Hash') ?? 'unknown';

        // key aman & stabil
        $rateKey = 'absen_' . hash('sha256', $uid . '|' . $deviceHash);

        // 3 request per 30 detik
        if ($throttler->check($rateKey, 3, 30) === false) {
            return $this->error(
                'Terlalu banyak percobaan absen, silakan tunggu sebentar',
                429
            );
        }
        $menu = strtoupper($this->request->getPost('menu') ?? '');
        if (!$menu) {
            return $this->error('Menu tidak valid');
        }

        $user = (new Deden())->find($uid);
        if (!$user) {
            return $this->error('User tidak ditemukan', 404);
        }

        // ===== CEK DOUBLE ABSEN (HARI INI) =====
        $today = date('Y-m-d');
        $absenModel = new AbsensiModel();

        $sudahAbsen = $absenModel
            ->where('userId', $uid)
            ->where('tanggal', $today)
            ->first();

        if ($sudahAbsen && in_array($menu, ['HADIR', 'TERLAMBAT'])) {
            return $this->error('Anda sudah absen hari ini');
        }

        // ===== ROUTING MENU =====
        switch ($menu) {

            case 'CUTI':
            case 'DIK':
            case 'BKO':
            case 'ADL':
            case 'SAKIT':
                return $this->handleAdl($uid, $user, $menu);
            case 'HADIR':
                return $this->handleHadir($uid, $user);

            case 'TERLAMBAT':
                return $this->handleTerlambat($uid, $user);

            case 'TK':
                return $this->handleBkoTk($uid, $user, $menu);

            case 'LD':
                return $this->handleLd($uid, $user);

            case 'DINAS':
                return $this->handleRangeDenganFoto($uid, $user, $menu);

            case 'IZIN':
                return $this->handleIzin($uid, $user);

            default:
                return $this->error('Menu tidak dikenali');
        }
    }

    /* =====================================================
     * HADIR / TERLAMBAT
     * ===================================================== */
    private function handleHadir($uid, $user)
    {
        return $this->handleHadirBase($uid, $user, true);
    }

    private function handleTerlambat($uid, $user)
    {
        return $this->handleHadirBase($uid, $user, false);
    }
    private function handleAdl($uid, $user, $menu)
    {
        // ===== INPUT =====
        $lat   = (float) $this->request->getPost('latitude');
        $lon   = (float) $this->request->getPost('longitude');
        $ketam = $this->request->getPost('ketam');

        if (!$lat || !$lon) {
            return $this->error('Lokasi wajib');
        }

        if (empty($ketam)) {
            return $this->error('Alasan wajib diisi');
        }

        // ===== FOTO =====
        $foto1 = $this->request->getFile('foto1');
        $foto2 = $this->request->getFile('foto2');

        if (!$foto1 || !$foto1->isValid()) {
            return $this->error('Foto pertama wajib');
        }

        if (!$foto2 || !$foto2->isValid()) {
            return $this->error('Foto kedua wajib');
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($foto1->getMimeType(), $allowedMime)) {
            return $this->error('Format foto pertama tidak valid');
        }

        if (!in_array($foto2->getMimeType(), $allowedMime)) {
            return $this->error('Format foto kedua tidak valid');
        }

        // ===== BATASI UKURAN FOTO (opsional tapi bagus) =====
        if ($foto1->getSize() > 500000) {
            return $this->error('Ukuran foto pertama terlalu besar');
        }

        if ($foto2->getSize() > 500000) {
            return $this->error('Ukuran foto kedua terlalu besar');
        }

        // ===== WAKTU SEKARANG =====
        $now      = new \DateTime();
        $tanggal  = $now->format('Y-m-d');
        $jamAbsen = $now->format('H:i:s');

        // ===== CEK SUDAH ABSEN ATAU BELUM =====
        $db = \Config\Database::connect();

        $sudahAbsen = $db->table('absensi')
            ->where('userId', $uid)
            ->where('tanggal', $tanggal)
            ->where('sudahkah', 'sudah')
            ->countAllResults();

        if ($sudahAbsen > 0) {
            return $this->error('Anda sudah absen hari ini');
        }

        // ===== AMBIL JAM MASUK DARI TABEL LOKASI =====
        $lokasi = $db->table('lokasi')
            ->where('id', 1)
            ->get()
            ->getRow();

        if (!$lokasi || empty($lokasi->jam)) {
            return $this->error('Jam absensi belum disetting');
        }

        $batasMulaiDb  = !empty($lokasi->batasmulai)
            ? $lokasi->batasmulai
            : '04:15:00';

        $batasAkhirDb = !empty($lokasi->batasakhir)
            ? $lokasi->batasakhir
            : '13:00:00';

        $tanggal = date('Y-m-d');

        $batasMulai  = new \DateTime($tanggal . ' ' . $batasMulaiDb);
        $batasAkhir  = new \DateTime($tanggal . ' ' . $batasAkhirDb);

        if ($now < $batasMulai) {
            return $this->error(
                "Batas mulai absen jam " . $batasMulai->format('H:i')
            );
        }

        if ($now > $batasAkhir) {
            return $this->error(
                "Batas absen jam " . $batasAkhir->format('H:i')
            );
        }

        $jamMasuk = new \DateTime($tanggal . ' ' . $lokasi->jam);

        $batasTepat = clone $jamMasuk;
        $batasTepat->modify('+5 minutes');

        if ($now < $jamMasuk) {
            $statusMasuk = 'MASUK LEBIH AWAL';
        } elseif ($now <= $batasTepat) {
            $statusMasuk = 'TEPAT WAKTU';
        } else {
            $statusMasuk = 'TERLAMBAT';
        }

        // ===== FOLDER PER TANGGAL =====
        $folder = WRITEPATH . 'uploads/absensi/' . date('Y/m/d');

        if (!is_dir($folder)) {
            if (!mkdir($folder, 0755, true)) {
                return $this->error('Gagal membuat folder upload');
            }
        }

        // ===== SIMPAN FOTO 1 =====
        $fotoName1 = $foto1->getRandomName();
        $foto1->move($folder, $fotoName1);

        // ===== SIMPAN FOTO 2 =====
        $fotoName2 = $foto2->getRandomName();
        $foto2->move($folder, $fotoName2);

        if ($menu == "ADL") {
            $menu = "DL";
        }

        // ===== SIMPAN ABSENSI =====
        (new AbsensiModel())->insert([
            'userId'       => $uid,
            'tanggal'      => $tanggal,
            'masuk'        => $jamAbsen,
            'keterangan'   => $menu,
            'ketam'        => $ketam,
            'latitude'     => $lat,
            'longitude'    => $lon,
            'foto'         => $fotoName1,
            'foto2'        => $fotoName2,
            'statusmasuk'  => $statusMasuk,
            'nama'         => $user['name'],
            'nip'          => $user['nip'],
            'jabatan'      => $user['jabatan'],
            'subdit'       => $user['subdit'],
            'pangkat'      => $user['pangkat'],
            'sudahkah'     => 'sudah'
        ]);

        return $this->success("Absen $menu berhasil ($statusMasuk)");
    }



    private function handleHadirBase($uid, $user, $cekJam)
    {
        // ===== INPUT =====
        $lat      = (float) $this->request->getPost('latitude');
        $lon      = (float) $this->request->getPost('longitude');
        if (!$lat || !$lon) {
            return $this->error('Lokasi wajib');
        }

        // ===== FOTO =====
        $foto = $this->request->getFile('foto');
        if (!$foto || !$foto->isValid()) {
            return $this->error('Foto wajib');
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($foto->getMimeType(), $allowedMime)) {
            return $this->error('Format foto tidak valid');
        }
        // ===== WAKTU SEKARANG (WIB) =====
        $now      = new \DateTime(); // timezone sudah Asia/Jakarta
        $tanggal  = $now->format('Y-m-d');
        $jamAbsen = $now->format('H:i:s');

        // ===== CEK SUDAH ABSEN ATAU BELUM =====
        $db = \Config\Database::connect();
        $sudahAbsen = $db->table('absensi')
            ->where('userId', $uid)
            ->where('tanggal', $tanggal)
            ->where('sudahkah', 'sudah')
            ->countAllResults();

        if ($sudahAbsen > 0) {
            return $this->error('Anda sudah absen hari ini');
        }

        // ===== BATAS JAM ABSEN =====
        $db = \Config\Database::connect();

        // ambil 1 baris konfigurasi lokasi
        $lokasi = $db->table('lokasi')
            ->where('id', 1)
            ->get()
            ->getRow();

        $jamAdmin = $lokasi && !empty($lokasi->jam)
            ? $lokasi->jam
            : '07:00';


        // fallback default kalau data kosong
        $batasMulaiDb  = $lokasi && !empty($lokasi->batasmulai)
            ? $lokasi->batasmulai
            : '04:15:00';

        $batasAkhirDb = $lokasi && !empty($lokasi->batasakhir)
            ? $lokasi->batasakhir
            : '13:00:00';

        // gabungkan dengan tanggal hari ini
        $tanggal = date('Y-m-d');

        $batasMulai  = new \DateTime($tanggal . ' ' . $batasMulaiDb);
        $batasAkhir  = new \DateTime($tanggal . ' ' . $batasAkhirDb);
        if ($now < $batasMulai) {
            return $this->error(
                'Batas mulai absen jam 04:15, silakan coba lagi nanti'
            );
        }

        if ($now > $batasAkhir) {
            return $this->error(
                'Batas absen jam 13:00 siang, silakan pilih jenis absen lain'
            );
        }

        // ===== AMBIL LOKASI KANTOR =====
        $lokasi = $db->table('lokasi')->get()->getRow();

        if (!$lokasi) {
            return $this->error('Lokasi kantor belum disetting');
        }

        $officeLat = (float) $lokasi->latitude;
        $officeLon = (float) $lokasi->longitude;

        // ===== CEK RADIUS =====
        $distance = $this->haversine(
            $officeLat,
            $officeLon,
            $lat,
            $lon
        );

        if ($distance > 100) {
            return $this->error('Di luar radius kantor', 403);
        }

        // ===== LOGIKA HADIR / TERLAMBAT (FINAL & BENAR) =====
        $jamMasuk = new \DateTime($tanggal . ' ' . $jamAdmin);

        // toleransi terlambat 5 menit
        $batasHadir = clone $jamMasuk;
        $batasHadir->modify('+5 minutes');

        if ($cekJam) {
            // menu HADIR
            $ket = ($now <= $batasHadir) ? 'HADIR' : 'TERLAMBAT';
        } else {
            // menu TERLAMBAT
            $ket = 'TERLAMBAT';
        }

        // ===== SIMPAN FOTO (KOMPRES + WEBP) =====
        $fotoName = pathinfo($foto->getRandomName(), PATHINFO_FILENAME) . '.webp';
        $pathAsli = WRITEPATH . 'uploads/temp/' . $foto->getRandomName();

        // pindahkan dulu ke temp
        $foto->move(WRITEPATH . 'uploads/temp', basename($pathAsli));

        // kompres + convert ke webp
        \Config\Services::image()
            ->withFile($pathAsli)
            ->resize(720, 720, true, 'auto')
            ->convert(IMAGETYPE_WEBP)
            ->save(WRITEPATH . 'uploads/absensi/' . $fotoName, 50); // 75 = kualitas

        // hapus file temp
        unlink($pathAsli);

        // ===== SIMPAN ABSENSI =====
        (new AbsensiModel())->insert([
            'userId'     => $uid,
            'tanggal'    => $tanggal,
            'masuk'      => $jamAbsen,
            'keterangan' => $ket,
            'latitude'   => $lat,
            'longitude'  => $lon,
            'foto'       => $fotoName,
            'nama'       => $user['name'],
            'nip'        => $user['nip'],
            'jabatan'    => $user['jabatan'],
            'subdit'     => $user['subdit'],
            'pangkat'    => $user['pangkat'],
            'bulan'      => $now->format('m')
        ]);

        return $this->success("Absen $ket berhasil");
    }

    /* =====================================================
     * BKO / TK
     * ===================================================== */
    private function handleBkoTk($uid, $user, $menu)
    {
        (new AbsensiModel())->insert([
            'userId'     => $uid,
            'tanggal'    => date('Y-m-d'),
            'keterangan' => $menu,
            'nama'       => $user['name'],
            'nip'        => $user['nip'],
            'jabatan'    => $user['jabatan'],
            'subdit'     => $user['subdit'],
            'pangkat'    => $user['pangkat'],
            'bulan'      => date('m')
        ]);

        return $this->success("$menu berhasil dicatat");
    }

    /* =====================================================
     * LD
     * ===================================================== */
    private function handleLd($uid, $user)
    {
        $foto = $this->request->getFile('foto');
        $ketam   = $this->request->getPost('ketam');

        // ===== VALIDASI FOTO =====
        $foto = $this->request->getFile('foto');
        if (!$foto || !$foto->isValid()) {
            return $this->error('Foto surat wajib');
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($foto->getMimeType(), $allowedMime)) {
            return $this->error('Format foto tidak valid');
        }
        // ===== SIMPAN FOTO (KOMPRES + WEBP) =====
        $randomTempName = $foto->getRandomName();
        $pathTemp = WRITEPATH . 'uploads/temp/' . $randomTempName;

        // pindahkan dulu ke temp
        $foto->move(WRITEPATH . 'uploads/temp', $randomTempName);

        // nama final
        $fotoName = pathinfo($randomTempName, PATHINFO_FILENAME) . '.webp';
        $pathFinal = WRITEPATH . 'uploads/absensi/' . $fotoName;

        // kompres + convert ke webp
        \Config\Services::image()
            ->withFile($pathTemp)
            ->resize(720, 720, true, 'auto')
            ->convert(IMAGETYPE_WEBP)
            ->save($pathFinal, 50);

        // hapus file temp
        if (file_exists($pathTemp)) {
            unlink($pathTemp);
        }
        $now      = new \DateTime(); // timezone sudah Asia/Jakarta
        $tanggal  = $now->format('Y-m-d');
        $jamAbsen = $now->format('H:i:s');
        (new AbsensiModel())->insert([
            'userId'     => $uid,
            'masuk'      => $jamAbsen,
            'tanggal'    =>  $tanggal,
            'keterangan' => 'LD',
            'foto'       => $fotoName,
            'nama'       => $user['name'],
            'nip'        => $user['nip'],
            'jabatan'    => $user['jabatan'],
            'subdit'     => $user['subdit'],
            'pangkat'    => $user['pangkat'],
            'ketam'    => $ketam,
            'bulan'      => date('m')
        ]);

        return $this->success("LD berhasil dicatat");
    }

    /* =====================================================
     * CUTI / DINAS / SAKIT
     * ===================================================== */
    private function handleRangeDenganFoto($uid, $user, $menu)
    {
        // ambil timestamp dari POST
        $startTs = $this->request->getPost('tanggal_mulai');
        $endTs   = $this->request->getPost('tanggal_selesai');
        $ketam   = $this->request->getPost('ketam');

        if (!$startTs || !$endTs) {
            return $this->error('Tanggal wajib');
        }

        // pastikan numeric
        if (!is_numeric($startTs) || !is_numeric($endTs)) {
            return $this->error('Format tanggal tidak valid');
        }

        // konversi millis ke detik lalu ke DateTime
        $start = (new \DateTime())->setTimestamp((int)($startTs / 1000));
        $end   = (new \DateTime())->setTimestamp((int)($endTs / 1000));

        // validasi range
        if ($end < $start) {
            return $this->error('Tanggal selesai tidak boleh sebelum tanggal mulai');
        }
        $model = new AbsensiModel();

        $startDate = $start->format('Y-m-d');
        $endDate   = $end->format('Y-m-d');

        // ===== CEK OVERLAP RANGE (FULL AMAN) =====
        $overlap = $model
            ->where('userId', $uid)
            ->groupStart()
            ->where('tanggal <=', $endDate)
            ->where('selesai >=', $startDate)
            ->groupEnd()
            ->first();

        if ($overlap) {
            return $this->error('Anda sudah memiliki jadwal absen/izin yang bentrok pada rentang tanggal tersebut');
        }


        // ===== VALIDASI FOTO =====
        $foto = $this->request->getFile('foto');
        if (!$foto || !$foto->isValid()) {
            return $this->error('Foto surat wajib');
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($foto->getMimeType(), $allowedMime)) {
            return $this->error('Format foto tidak valid');
        }
        // ===== SIMPAN FOTO (KOMPRES + WEBP) =====
        $randomTempName = $foto->getRandomName();
        $pathTemp = WRITEPATH . 'uploads/temp/' . $randomTempName;

        // pindahkan dulu ke temp
        $foto->move(WRITEPATH . 'uploads/temp', $randomTempName);

        // nama final
        $fotoName = pathinfo($randomTempName, PATHINFO_FILENAME) . '.webp';
        $pathFinal = WRITEPATH . 'uploads/absensi/' . $fotoName;

        // kompres + convert ke webp
        \Config\Services::image()
            ->withFile($pathTemp)
            ->resize(720, 720, true, 'auto')
            ->convert(IMAGETYPE_WEBP)
            ->save($pathFinal, 50);

        // hapus file temp
        if (file_exists($pathTemp)) {
            unlink($pathTemp);
        }

        // ===== BUAT RANGE TANGGAL =====
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            (clone $end)->modify('+1 day')
        );


        $now      = new \DateTime(); // timezone sudah Asia/Jakarta
        $jamAbsen = $now->format('H:i:s');
        $tanggalSelesai = $end->format('Y-m-d');

        foreach ($period as $d) {
            $model->insert([
                'userId'     => $uid,
                'masuk'     => $jamAbsen,
                'tanggal'    => $d->format('Y-m-d'),
                'keterangan' => $menu,
                'selesai' => $tanggalSelesai,
                'ketam'      => $ketam,
                'foto'       => $fotoName,
                'nama'       => $user['name'],
                'jabatan'       => $user['jabatan'],
                'pangkat'       => $user['pangkat'],
                'nip'        => $user['nip'],
                'subdit'     => $user['subdit'],
                'bulan'      => $d->format('m')
            ]);
        }

        return $this->success("$menu berhasil dicatat");
    }


    private function handleIzin($uid, $user)
    {
        $izinTipe = strtoupper($this->request->getPost('izin_tipe'));

        if (!in_array($izinTipe, ['RESMI', 'PIMPINAN'])) {
            return $this->error('Tipe izin tidak valid');
        }

        $ketam = trim($this->request->getPost('ketam') ?? '');

        if ($ketam === '') {
            return $this->error('Keterangan wajib ada');
        }
        $fotoName = null;

        // =====================
        // IZIN RESMI
        // =====================
        if ($izinTipe === 'RESMI') {

            $foto = $this->request->getFile('foto');

            if (!$foto || !$foto->isValid() || $foto->hasMoved()) {
                return $this->error('Foto wajib untuk izin resmi');
            }
            // VALIDASI MIME TYPE
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($foto->getMimeType(), $allowedMime)) {
                return $this->error('Format foto tidak valid');
            }

            // ===== SIMPAN FOTO (KOMPRES + WEBP) =====
            $randomTempName = $foto->getRandomName();
            $pathTemp = WRITEPATH . 'uploads/temp/' . $randomTempName;

            // pindahkan dulu ke temp
            $foto->move(WRITEPATH . 'uploads/temp', $randomTempName);

            // nama final
            $fotoName = pathinfo($randomTempName, PATHINFO_FILENAME) . '.webp';
            $pathFinal = WRITEPATH . 'uploads/absensi/' . $fotoName;

            // kompres + convert ke webp
            \Config\Services::image()
                ->withFile($pathTemp)
                ->resize(800, 800, true, 'auto')
                ->convert(IMAGETYPE_WEBP)
                ->save($pathFinal, 60);

            // hapus file temp
            if (file_exists($pathTemp)) {
                unlink($pathTemp);
            }
        }

        // =====================
        // IZIN PIMPINAN
        // =====================
        $namaPimpinan    = "";
        $jabatanPimpinan = "";
        $pangkatPimpinan = "";

        if ($izinTipe === 'PIMPINAN') {

            $namaPimpinan    = trim($this->request->getPost('nama_pimpinan') ?? '');
            $jabatanPimpinan = trim($this->request->getPost('jabatan_pimpinan') ?? '');
            $pangkatPimpinan = trim($this->request->getPost('pangkat_pimpinan') ?? '');

            if ($namaPimpinan === '' || $jabatanPimpinan === '' || $pangkatPimpinan === '') {
                return $this->error('Data pimpinan tidak lengkap');
            }
        }
        $now      = new \DateTime(); // timezone sudah Asia/Jakarta
        $jamAbsen = $now->format('H:i:s');

        // =====================
        // SIMPAN
        // =====================
        (new AbsensiModel())->insert([
            'userId'       => $uid,
            'tanggal'      => date('Y-m-d'),
            'keterangan'   => 'IZIN',
            'foto'         => $fotoName,
            'masuk'         => $jamAbsen,

            'nama'         => $user['name'],
            'nip'          => $user['nip'],
            'subdit'       => $user['subdit'],
            'jabatan'       => $user['jabatan'],
            'pangkat'       => $user['pangkat'],
            'namapimpinan' => $namaPimpinan,
            'jabatanpimpinan'      => $jabatanPimpinan,
            'pangkatpimpinan'      => $pangkatPimpinan,
            'ketam'        => $ketam,
            'tipeizin'     => $izinTipe,
            'bulan'        => date('m')
        ]);

        return $this->success("Izin $izinTipe berhasil dicatat");
    }



    /* =====================================================
     * HELPER
     * ===================================================== */
    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;
        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function success($msg)
    {
        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => $msg
        ]);
    }

    private function error($msg, $code = 400)
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON([
                'status'  => 'error',
                'message' => $msg
            ]);
    }

    public function cekabsenupdate()
    {

        // UID dari MobileFilter
        $uid = $this->request->uid ?? null;

        $tanggal = date('Y-m-d');

        // Ambil absen
        $dataabsen = $this->dauo->ambilin2parameter('absensi', 'userId', $uid, 'tanggal', $tanggal);
        if (empty($dataabsen)) {
            return $this->response
                ->setContentType('application/json')
                ->setJSON([
                    'status'  => 'ok',
                    'absen' => 'belum'
                ]);
        } else {
            return $this->response
                ->setContentType('application/json')
                ->setJSON([
                    'status'  => 'ok',
                    'absen' => 'Anda sudah absen'
                ]);
        }
    }

    public function pulang()
    {
        $uid = $this->request->uid ?? null;

        if (!$uid) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'UID tidak ditemukan'
            ]);
        }
        $throttler = service('throttler');

        $deviceHash = $this->request->getHeaderLine('X-Device-Hash') ?? 'unknown';

        $rateKey = 'pulang' . hash('sha256', $uid . '|' . $deviceHash);

        if ($throttler->check($rateKey, 3, 30) === false) {
            return $this->error(
                'Terlalu banyak percobaan absen, silakan tunggu sebentar',
                429
            );
        }

        $tanggal = date('Y-m-d');
        $jamPulang = date('H:i:s');

        $model = new \App\Models\AbsensiModel();

        // cek dulu apakah sudah pulang
        $cek = $model->where('userId', $uid)
            ->where('tanggal', $tanggal)
            ->whereIn('keterangan', ['HADIR', 'TERLAMBAT'])
            ->first();

        if (!$cek) {
            session()->remove('uidnya');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data absen tidak ditemukan'
            ]);
        }

        // kalau sudah pulang
        if (!empty($cek['pulang'])) {
            session()->remove('uidnya');
            return $this->response->setJSON([
                'status' => 'ok',
                'message' => 'Sudah pulang',
                'pulang' => $cek['pulang']
            ]);
        }

        // update pulang
        $model->where('userId', $uid)
            ->where('tanggal', $tanggal)
            ->whereIn('keterangan', ['HADIR', 'TERLAMBAT'])
            ->set(['pulang' => $jamPulang])
            ->update();


        return $this->response->setJSON([
            'status' => 'ok',
            'pulang' => $jamPulang
        ]);
    }




    public function ambil_absen()
    {
        $uid = $this->request->uid ?? null;

        if (!$uid) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'UID tidak ditemukan'
            ]);
        }

        $tanggal = date('Y-m-d');
        $aesKey = bin2hex(random_bytes(32));
        // pakai model AbsensiModel
        $model = new \App\Models\AbsensiModel();
        $berita = $this->berita
            ->orderBy('id', 'DESC')
            ->limit(3)
            ->find();

        // ENKRIPSI FIELD BERITA
        foreach ($berita as &$b) {
            if (isset($b['judul']))   $b['judul'] = $this->encryptAES($b['judul'], $aesKey);
            if (isset($b['isi']))     $b['isi']   = $this->encryptAES($b['isi'], $aesKey);
            if (isset($b['foto']))    $b['foto']  = $this->encryptAES($b['foto'], $aesKey);
            if (isset($b['pdf']))     $b['pdf']   = $this->encryptAES($b['pdf'], $aesKey);
        }

        $dataabsen = $model
            ->where('userId', $uid)
            ->where('tanggal', $tanggal)
            ->findAll();

        foreach ($dataabsen as &$row) {
            unset($row['bulan'], $row['userId']);
        }
        unset($row);
        // AES key untuk response ini


        // enkripsi semua field
        foreach ($dataabsen as &$row) {
            foreach ($row as $field => $value) {
                if ($value !== null && $value !== '') {
                    $row[$field] = $this->encryptAES((string)$value, $aesKey);
                }
            }
        }
        unset($row);
        $db = \Config\Database::connect();

        $lokasi = $db->table('lokasi')
            ->select('pulang')
            ->where('id', 1)
            ->get()
            ->getRowArray();

        $pulang = $lokasi['pulang'] ?? null;
        $jamserver = date('H:i');

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'    => 'ok',
                'pulang'    => $pulang,
                'jamserver'    => $jamserver,
                'dataabsen' => $dataabsen,
                'aes_key' => $aesKey,
                'berita'  => $berita
            ]);
    }


    public function ceka()
    {

        // UID dari MobileFilter
        $uid = $this->request->uid ?? null;


        $datalokasi = $this->dauo->getdata('lokasi');
        // Generate AES key (per session)

        $aesKey = bin2hex(random_bytes(32)); // 32 hex (AES-256)
        $row = $datalokasi[0] ?? null;

        $jam = $row ? $this->encryptAES($row->jam, $aesKey) : null;
        $latitude = $row ? $this->encryptAES($row->latitude, $aesKey) : null;
        $longitude = $row ? $this->encryptAES($row->longitude, $aesKey) : null;

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'aes_key' => $aesKey,
                'jam' => $jam,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
    }


    private function encryptAES(string $plain, string $aesKeyHex): string
    {
        // VALIDASI KEY
        if (!ctype_xdigit($aesKeyHex) || strlen($aesKeyHex) !== 64) {
            throw new \Exception('AES key invalid');
        }

        // AES-CBC → IV HARUS 16 BYTE
        $iv = random_bytes(16);

        $cipher = openssl_encrypt(
            $plain,
            'AES-256-CBC',
            hex2bin($aesKeyHex),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($cipher === false) {
            throw new \Exception('Encrypt failed');
        }

        return base64_encode($iv . $cipher);
    }


    public function sejarah()
    {
        $model = new AbsensiModel();

        $uid = $this->request->uid ?? null;

        $lastId        = $this->request->getPost('lastId');
        $statusFilter  = $this->request->getPost('status');
        $tanggalFilter = $this->request->getPost('tanggal');

        log_message('error', 'lastId=' . $lastId);
        log_message('error', 'statusFilter=' . $statusFilter);
        log_message('error', 'tanggalFilter=' . $tanggalFilter);

        $limit = 10;

        $builder = $model->where('userId', $uid);

        // filter status
        if (!empty($statusFilter)) {
            $builder->like('keterangan', trim($statusFilter));
        }

        // filter tanggal
        if (!empty($tanggalFilter)) {
            $builder->where('tanggal', $tanggalFilter);
        }

        // pagination pakai id
        if (!empty($lastId)) {
            $builder->where('id <', $lastId);
        }

        $builder->orderBy('id', 'DESC')
            ->limit($limit);

        $data = $builder->find();

        $aesKey = bin2hex(random_bytes(32));
        $result = [];

        foreach ($data as $row) {
            $result[] = [
                'id'           => $row['id'],
                'nama'      => $this->encryptAES($row['nama'] ?? '', $aesKey),
                'tanggal'      => $this->encryptAES($row['tanggal'] ?? '', $aesKey),
                'masuk'        => $this->encryptAES($row['masuk'] ?? '', $aesKey),
                'pulang'       => $this->encryptAES($row['pulang'] ?? '', $aesKey),
                'selesai'      => $this->encryptAES($row['selesai'] ?? '', $aesKey),
                'keterangan'   => $this->encryptAES($row['keterangan'] ?? '', $aesKey),
                'latitude'     => $this->encryptAES($row['latitude'] ?? '', $aesKey),
                'longitude'    => $this->encryptAES($row['longitude'] ?? '', $aesKey),
                'ketam'        => $this->encryptAES($row['ketam'] ?? '', $aesKey),
                'statusmasuk'     => $this->encryptAES($row['statusmasuk'] ?? '', $aesKey),
                'statuspulang' => $this->encryptAES($row['statuspulang'] ?? '', $aesKey),
                'fotopulang'      => $this->encryptAES($row['fotopulang'] ?? '', $aesKey),
                'fotopulang2'      => $this->encryptAES($row['fotopulang2'] ?? '', $aesKey),
                'foto'         => $this->encryptAES($row['foto'] ?? '', $aesKey),
                'foto2'         => $this->encryptAES($row['foto2'] ?? '', $aesKey),
                'subdit'       => $this->encryptAES($row['subdit'] ?? '', $aesKey),
                'latpulang'     => $this->encryptAES($row['latpulang'] ?? '', $aesKey),
                'lonpulang'    => $this->encryptAES($row['lonpulang'] ?? '', $aesKey),
                'ketpul'    => $this->encryptAES($row['ketpul'] ?? '', $aesKey),
                'nip'    => $this->encryptAES($row['nip'] ?? '', $aesKey),
                'pangkat'    => $this->encryptAES($row['pangkat'] ?? '', $aesKey),
                'jabatan'    => $this->encryptAES($row['jabatan'] ?? '', $aesKey),
            ];
        }

        return $this->response->setJSON([
            'status'  => 'ok',
            'aes_key' => $aesKey,
            'jumlah'  => count($result),
            'data'    => $result
        ]);
    }

    public function berita()
    {
        $model = new BeritaModel();

        $lastId = $this->request->getPost('lastId');
        $limit = 10;

        $builder = $model->orderBy('id', 'DESC');

        if (!empty($lastId) && $lastId != "0") {
            $builder->where('id <', $lastId);
        }

        $data = $builder->limit($limit)->findAll();

        $aesKey = bin2hex(random_bytes(32));
        $result = [];

        foreach ($data as $row) {
            $result[] = [
                'id'      => $row['id'],
                'judul'   => $this->encryptAES($row['judul'] ?? '', $aesKey),
                'isi'     => $this->encryptAES($row['isi'] ?? '', $aesKey),
                'tanggal' => $row['tanggal'],
                'foto'    => $this->encryptAES($row['foto'] ?? '', $aesKey),
                'pdf'     => $this->encryptAES($row['pdf'] ?? '', $aesKey),
            ];
        }

        return $this->response->setJSON([
            'status'  => 'ok',
            'aes_key' => $aesKey,
            'jumlah'  => count($result),
            'last_id' => end($data)['id'] ?? null, // penting
            'data'    => $result
        ]);
    }

    public function getdoas()
    {
        $db = \Config\Database::connect();

        // ambil 1 data saja
        $builder = $db->table('pdf');
        $builder->select("id, judul, isi, tanggal, pdf");
        $builder->orderBy('id', 'DESC');
        $builder->limit(1);

        $row = $builder->get()->getRowArray();

        if (!$row) {
            return $this->response->setJSON([
                'status' => 'kosong'
            ]);
        }

        $aesKey = bin2hex(random_bytes(32));
        $berita = $this->berita
            ->orderBy('id', 'DESC')
            ->limit(3)
            ->find();

        // ENKRIPSI FIELD BERITA
        foreach ($berita as &$b) {
            if (isset($b['judul']))   $b['judul'] = $this->encryptAES($b['judul'], $aesKey);
            if (isset($b['isi']))     $b['isi']   = $this->encryptAES($b['isi'], $aesKey);
            if (isset($b['foto']))    $b['foto']  = $this->encryptAES($b['foto'], $aesKey);
            if (isset($b['pdf']))     $b['pdf']   = $this->encryptAES($b['pdf'], $aesKey);
        }

        unset($b);
        return $this->response->setJSON([
            'status'  => 'ok',
            'aes_key' => $aesKey,
            'id'      => $row['id'],
            'judul'   => $this->encryptAES($row['judul'] ?? '', $aesKey),
            'isi'     => $this->encryptAES($row['isi'] ?? '', $aesKey),
            'tanggal' => $row['tanggal'],
            'pdf'     => $this->encryptAES($row['pdf'] ?? '', $aesKey),
            'berita'  => $berita

        ]);
    }


    //backup
    public function cekabsen()
    {
        $uid = $this->request->uid ?? null;

        if (!$uid) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'UID tidak ditemukan'
            ]);
        }

        $jenis = strtoupper(trim($this->request->getPost('jenis') ?? ''));
        $today = date('Y-m-d');

        $db = \Config\Database::connect();

        // =====================================================
        // 1️⃣ CEK APPROVAL
        // =====================================================
        $approval = $db->table('approval')
            ->select('keterangan, aproval, selesai')
            ->where('userId', $uid)
            ->where('tanggal <=', $today)
            ->where('selesai >=', $today)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($approval) {

            // masih menunggu admin
            if (strtolower($approval['aproval']) === 'belum') {

                return $this->response->setJSON([
                    'status' => 'ok',
                    'absen'  =>
                    'absen ' .
                        strtoupper($approval['keterangan']) .
                        ' anda menunggu persetujuan admin'
                ]);
            }

            // kalau approved → lanjut ke absensi
        }

        // =====================================================
        // 2️⃣ CEK ABSENSI HARI INI
        // =====================================================
        $absen = $db->table('absensi')
            ->select('keterangan, sudahkah, selesai')
            ->where('userId', $uid)
            ->where('tanggal', $today)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        // tidak ada data
        if (!$absen) {

            $ketamKemarin = '';

            // ======================
            // CEK DATA KEMARIN (JIKA PILIH JENIS)
            // ======================
            if ($jenis !== '') {

                $kemarin = $db->table('absensi')
                    ->select('ketam')
                    ->where('userId', $uid)
                    ->where('tanggal', date('Y-m-d', strtotime('-1 day')))
                    ->where('keterangan', $jenis)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRowArray();

                if ($kemarin) {
                    $ketamKemarin = $kemarin['ketam'] ?? '';
                }
            }

            return $this->response->setJSON([
                'status' => 'ok',
                'absen'  => 'belum',
                'ketam'  => $ketamKemarin
            ]);
        }
        // =====================================================
        // SUDAH ABSEN
        // =====================================================
        // if (strtolower($absen['sudahkah']) === 'sudah') {

        //     return $this->response->setJSON([
        //         'status' => 'ok',
        //         'absen'  => 'sudah absen'
        //     ]);
        // }

        $ket = strtoupper($absen['keterangan']);
        $sudahkah = strtoupper($absen['sudahkah']);
        // log_message('error', '$jenis=' . var_export($jenis, true));
        // log_message('error', '$ket=' . var_export($ket, true));
        // log_message('error', '$sudahkah=' . var_export($sudahkah, true));
        // =====================================================
        // JIKA DL → SELALU SUDAH ya sudah lagh
        // =====================================================
        if ($ket === 'DL' && $sudahkah === "SUDAH") {
            return $this->response->setJSON([
                'status' => 'ok',
                'absen'  => 'Anda sudah Absen'
            ]);
        }
        // =====================================================
        // MASIH BELUM → CEK JENIS
        // =====================================================


        if ($jenis !== '' && $jenis === $ket && $sudahkah === "BELUM") {

            return $this->response->setJSON([
                'status' => 'ok',
                'absen'  => 'belum'

            ]);
        }

        if ($jenis !== '' && $jenis === $ket && $sudahkah == "SUDAH") {

            return $this->response->setJSON([
                'status' => 'ok',
                'absen'  => 'Anda sudah absen'

            ]);
        }



        // =====================================================
        // JENIS BEDA (SELain DL)
        // =====================================================
        return $this->response->setJSON([
            'status' => 'ok',
            'absen'  =>
            'anda sudah absen ' .
                $ket
        ]);
    }


    public function getme()
    {
        $aesKey = bin2hex(random_bytes(32));

        // UID dari MobileFilter
        $uid = $this->request->uid ?? null;
        if (!$uid) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'UID tidak ditemukan'
            ]);
        }
        $user = (new Deden())->find($uid);
        if (!$user) {
            return $this->error('User tidak ditemukan', 404);
        }

        $payload = [
            'username' => (string) ($user['username'] ?? ''),
            'nip'      => (string) ($user['nip'] ?? ''),
            'nama'     => (string) ($user['name'] ?? ''),
            'jabatan'  => (string) ($user['jabatan'] ?? ''),
            'roleId'   => (string) ($user['roleId'] ?? ''),
            'pangkat'  => (string) ($user['pangkat'] ?? ''),
            'subdit'   => (string) ($user['subdit'] ?? ''),
        ];

        foreach ($payload as $key => $value) {
            $payload[$key] = $this->encryptAES($value, $aesKey);
        }

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'aes_key' => $aesKey,
                'data' => $payload
            ]);
    }

    public function simpanprofile()
    {
        // UID dari MobileFilter
        $uid = $this->request->uid ?? null;
        if (!$uid) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'UID tidak ditemukan'
            ]);
        }

        $nama = trim((string) $this->request->getPost('nama'));
        $pangkat = trim((string) $this->request->getPost('pangkat'));
        $jabatan = trim((string) $this->request->getPost('jabatan'));
        $subdit = trim((string) $this->request->getPost('subdit'));

        $user = $this->userModel->find($uid);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ]);
        }

        $updateData = [];
        if ($nama !== '') {
            $updateData['name'] = $nama;
        }
        if ($pangkat !== '') {
            $updateData['pangkat'] = $pangkat;
        }
        if ($jabatan !== '') {
            $updateData['jabatan'] = $jabatan;
        }
        if ($subdit !== '') {
            $updateData['subdit'] = $subdit;
        }

        if (empty($updateData)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Tidak ada data yang diupdate'
            ]);
        }

        $updated = $this->userModel->update($uid, $updateData);
        if (!$updated) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Gagal update profile'
            ]);
        }

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'message' => 'Profile berhasil diupdate'
            ]);
    }


    public function ubahpassword()
    {
        // UID dari MobileFilter
        $uid = $this->request->uid ?? null;
        if (!$uid) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'UID tidak ditemukan'
            ]);
        }

        $passwordlama = trim((string) $this->request->getPost('old_password'));
        $passwordbaru = trim((string) $this->request->getPost('new_password'));

        if ($passwordlama === '' || $passwordbaru === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Old password dan new password wajib diisi'
            ]);
        }

        $user = $this->userModel->find($uid);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ]);
        }

        $storedPassword = (string) ($user['password'] ?? '');
        $isOldPasswordValid = $storedPassword !== '' && password_verify($passwordlama, $storedPassword);
        if (!$isOldPasswordValid) {
            return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'message' => 'Password lama tidak sesuai'
            ]);
        
        }

        if (password_verify($passwordbaru, $storedPassword)) {
          return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'message' => 'Password baru tidak boleh sama dengan password lama'
            ]);
        }

        $updated = $this->userModel->update($uid, [
            'password' => password_hash($passwordbaru, PASSWORD_DEFAULT),
        ]);

        if (!$updated) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengubah password'
            ]);
        }

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'status'  => 'ok',
                'message' => 'Password berhasil diupdate'
            ]);
    }
}
