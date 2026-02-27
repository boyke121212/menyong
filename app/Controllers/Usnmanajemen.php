<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\JWT as JWTConfig;
use App\Services\LoginAuditService;
use App\Services\UsnService;
use App\Models\Deden;
use App\Models\Dauo;
use App\Services\UserService;
use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;



class Usnmanajemen extends BaseController
{
    protected $format = 'json';
    protected Dauo $dauo;
    protected Deden $userModel;
    protected $db;

    public function __construct()
    {
        $this->userModel = new Deden();
        $this->dauo = new Dauo();
        $this->db = Database::connect();
    }
    public function index() {}
    public function getuser()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();


        // dd($datauser);
        return view('app', [
            'title'   => 'D.O.A.S - User Manajemen',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'Home',
            'page'    => 'usnmanajemen',
        ]);
    }

    public function adduser()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();
        // dd($datauser);
        return view('app', [
            'title'   => 'D.O.A.S - Tambah User',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'adduser',
            'page'    => 'tambahuser',
        ]);
    }

    public function simpan()
    {

        // =========================
        // CEK DUPLIKAT DATA
        // =========================
        $name   = $this->request->getPost('name');
        $nip    = $this->request->getPost('nip');

        $builder = $this->userModel
            ->groupStart()
            ->where('name', $name)
            ->orWhere('nip', $nip)
            ->groupEnd();

        $existing = $builder->first();

        if ($existing) {

            $errors = [];

            if ($existing['name'] === $name) {
                $errors[] = 'Nama sudah terdaftar';
            }
            if ($existing['nip'] === $nip) {
                $errors[] = 'NIP sudah terdaftar';
            }

            return redirect()->back()
                ->withInput()
                ->with('flasherror', implode(', ', $errors));
        }


        // =========================
        // DATA INSERT
        // =========================
        $data = [
            'username'   => $this->request->getPost('nip'),
            'password'   => password_hash(
                $this->request->getPost('nip'),
                PASSWORD_DEFAULT
            ),
            'name'       => $this->request->getPost('name'),
            'roleId'     => "8",
            'nip'        => $this->request->getPost('nip'),
            'pangkat'        => $this->request->getPost('pangkat'),
            'jabatan'        => $this->request->getPost('jabatan'),
            'subdit'     => $this->request->getPost('subdit'),
            'status'     => 'belum',
            'createdBy'  => $_SESSION['userId'],
            'createdDtm' => date('Y-m-d H:i:s'),
        ];

        $this->userModel->insert($data);

        return redirect()->to('usnmanajemen')
            ->with('flashsuccess', 'User berhasil ditambahkan');
    }


    public function edit()
    {
        $userId =  $this->request->getGet('userId');

        $userservice = new Userservice;
        $datauser = $userservice->getDataUser($userId);
        return view('app', [
            'title'   => 'D.O.A.S - Edit User',
            'nama'    => $_SESSION['name'],
            'role'    => $_SESSION['role'],
            'keadaan' => 'Edit',
            'datauser' => $datauser,
            'page'    => 'edituser',
        ]);
    }


    public function updateuser()
    {
        $request = $this->request;

        $userId =  $this->request->getPost('userId');
        if (!$userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Ambil data lama
        $userLama = $this->userModel->where('userId', $userId)->first();
        if (!$userLama) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // =============================
        // CEK DUPLIKAT (EXCLUDE USER INI)
        // =============================
        $name   = $request->getPost('name');
        $nip    = $userLama['nip']; // nip biasanya tidak diubah

        $duplikat = $this->userModel
            ->where('userId !=', $userId)
            ->groupStart()
            ->where('name', $name)
            ->orWhere('nip', $nip)
            ->groupEnd()
            ->first();

        if ($duplikat) {

            $errors = [];

            if ($duplikat['name'] === $name) {
                $errors[] = 'Nama sudah digunakan user lain';
            }
            if ($duplikat['nip'] === $nip) {
                $errors[] = 'NIP sudah digunakan user lain';
            }

            return redirect()->back()
                ->withInput()
                ->with('flasherror', implode(', ', $errors));
        }

        // =============================
        // UPDATE DATA USER
        // =============================
        $dataUpdate = [
            'name'    => $request->getPost('name'),
            'pangkat' => $request->getPost('pangkat'),
            'jabatan' => $request->getPost('jabatan'),
            'subdit'  => $request->getPost('subdit')
        ];

        $this->userModel->where('userId', $userId)->update(null, $dataUpdate);

        return redirect()
            ->to(site_url('usnmanajemen'))
            ->with('flashsuccess', 'User ' . $userLama['name'] . ' berhasil diperbarui');
    }

    public function logoutuser()
    {
        $uid = $_SESSION['pelaku'] ?? null;


        $userId = $this->request->getPost('userId');
        $alasan = $this->request->getPost('alasan');
        if (!$userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Ambil data lama
        $userLama = $this->userModel->where('userId', $userId)->first();
        if (!$userLama) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $pelaku = $this->userModel->where('userId', $uid)->first();
        if (!$pelaku) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $agent = $this->request->getUserAgent();
        $terlogout = array(
            'userId' => $userLama['userId'],
            'role' => $userLama['roleId'],
            'name' => $userLama['name'],
            'nip' => $userLama['nip'],
            'jabatan' => $userLama['jabatan'],
            'subdit' => $userLama['subdit'],
            'last_activity' => $userLama['last']  // ⬅️ WAJIB ADA
        );
        $pelogout = array(
            'userId' => $pelaku['userId'],
            'role' => $pelaku['roleId'],
            'name' => $pelaku['name'],
            'nip' => $pelaku['nip'],
            'jabatan' => $pelaku['jabatan'],
            'subdit' => $pelaku['subdit'],
            'last_activity' => date('Y-m-d H:i:s')   // ⬅️ WAJIB ADA
        );
        $datalogout = [
            'userId'    => $userId,
            'alasan'   => $alasan,
            'oleh'  => $uid,
            "terlogout" => json_encode($terlogout),
            "pelogout" => json_encode($pelogout),
            "machineIp" => $_SERVER['REMOTE_ADDR'],
            "userAgent" => $_SERVER['HTTP_USER_AGENT'],
            "agentString" => $agent->getAgentString(),
            "platform" => $agent->getPlatform()
        ];
        $this->dauo->input_data($datalogout, 'logout');
        $dataUpdate = [
            'status'    => 'belum',
            'access_token'   => '',
            'refresh_token'  => '',
            'device_hash' => '',
            'app_signature' => ''
        ];

        $this->dauo->update_data($dataUpdate, 'usn', 'userId', $userId);
        session()->remove('pelaku');

        return redirect()
            ->to(site_url('usnmanajemen'))
            ->with('flashsuccess', 'User ' . $userLama['name'] . ' berhasil Di Logout dari aplikasi android dan ios');
    }
    public function hapususer()
    {
        $userId = $this->request->getPost('userId');

        if (!$userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $user = $this->userModel->where('userId', $userId)->first();

        if (!$user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }



        // =============================
        // HAPUS DATA USER
        // =============================
        $this->userModel->where('userId', $userId)->delete();

        return redirect()
            ->to(site_url('usnmanajemen'))
            ->with('flashsuccess', 'User ' . $user['name'] . ' berhasil dihapus');
    }

    public function apadoas()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();
        $doas = $this->dauo->getdata('pdf');
        // var_dump($doas); 
        // $userservice = new Userservice;
        // $datauser = $userservice->getDataUser($id);
        return view('app', [
            'title'   => 'D.O.A.S - Apa itu DOAS',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'data'    => $doas,
            'keadaan' => 'apa',
            'page'    => 'apaitudoas',
        ]);
    }


    public function login_log()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();
        // var_dump($doas); 
        // $userservice = new Userservice;
        // $datauser = $userservice->getDataUser($id);
        return view('app', [
            'title'   => 'D.O.A.S - Login Log',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'login_log',
        ]);
    }

    public function logout_log()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();
        // var_dump($doas); 
        // $userservice = new Userservice;
        // $datauser = $userservice->getDataUser($id);
        return view('app', [
            'title'   => 'D.O.A.S - Logout Log',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'logout_log',
        ]);
    }

    public function kantor()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();
        $doas = $this->dauo->getdata('lokasi');
        // var_dump($doas); 
        // $userservice = new Userservice;
        // $datauser = $userservice->getDataUser($id);
        return view('app', [
            'title'   => 'D.O.A.S - Set Lokasi',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'data'    => $doas,
            'keadaan' => 'apa',
            'page'    => 'kantor',
        ]);
    }


    public function hapususerbanyak()
    {
        $ids = $this->request->getPost('userIds');

        if (!$ids) {
            return redirect()->back();
        }

        // =========================
        // NORMALISASI ID
        // =========================
        $idArray = array_values(array_filter(
            array_map('intval', explode(',', $ids))
        ));

        if (empty($idArray)) {
            return redirect()->back()
                ->with('flashsuccess', 'Tidak ada data valid untuk dihapus');
        }

        // =========================
        // AMBIL USER ROLE 8 SAJA
        // =========================
        $users = $this->userModel
            ->select('userId')
            ->whereIn('userId', $idArray)
            ->where('roleId', 8) // 🔒 KUNCI UTAMA
            ->findAll();

        if (empty($users)) {
            return redirect()->back()
                ->with('flashsuccess', 'Tidak ada user yang boleh dihapus');
        }


        // =========================
        // HAPUS DATA USER (ROLE 8 SAJA)
        // =========================
        $this->userModel
            ->whereIn('userId', array_column($users, 'userId'))
            ->delete();

        return redirect()->back()
            ->with('flashsuccess', 'Data user berhasil dihapus');
    }

    public function datalog()
    {
        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('start_date');
        $endDate   = $request->getPost('end_date');

        $builder = $db->table('tbl_last_login');

        if ($startDate && $endDate) {
            $builder->where('createdDtm >=', $startDate . ' 00:00:00')
                ->where('createdDtm <=', $endDate . ' 23:59:59');
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('userId', $search)
                ->orLike('machineIp', $search)
                ->orLike('platform', $search)
                ->orLike('userAgent', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('createdDtm', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('tbl_last_login')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }

    public function datatable()
    {
        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search    = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('startDate'); // 🔥 FIX
        $endDate   = $request->getPost('endDate');   // 🔥 FIX

        $builder = $db->table('logout');

        if ($startDate && $endDate) {
            $builder->where('timestamp >=', $startDate . ' 00:00:00')
                ->where('timestamp <=', $endDate . ' 23:59:59');
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('userId', $search)
                ->orLike('alasan', $search)
                ->orLike('oleh', $search)
                ->orLike('machineIp', $search)
                ->orLike('platform', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('timestamp', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('logout')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }



    public function export()
    {
        $db = \Config\Database::connect();

        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');

        $builder = $db->table('tbl_last_login');

        if ($start && $end) {
            $builder->where('createdDtm >=', $start . ' 00:00:00')
                ->where('createdDtm <=', $end . ' 23:59:59');
        }

        $data = $builder->orderBy('createdDtm', 'DESC')->get()->getResultArray();

        // ==================================================
        // SPREADSHEET INIT
        // ==================================================
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ==================================================
        // ROW HEIGHT (WHITESPACE KOP)
        // ==================================================
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // ==================================================
        // MERGE AREA
        // ==================================================
        $sheet->mergeCells('A1:B3');   // LOGO
        $sheet->mergeCells('D1:G1');   // JUDUL
        $sheet->mergeCells('D2:G2');   // TANGGAL

        // ==================================================
        // LOGO (KIRI)
        // ==================================================
        $logo = new Drawing();
        $logo->setName('Logo DITTIPIDTER');
        $logo->setPath(WRITEPATH . 'uploads/photos/logodit.webp');
        $logo->setHeight(70);
        $logo->setCoordinates('A1');
        $logo->setOffsetX(20);
        $logo->setOffsetY(10);
        $logo->setWorksheet($sheet);

        // ==================================================
        // JUDUL (KANAN)
        // ==================================================
        $sheet->setCellValue('C1', 'DITTIPIDTER – LOGIN LOG');
        $sheet->getStyle('C1')->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('C1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // ==================================================
        // SUB JUDUL / PERIODE (OPSIONAL)
        // ==================================================
        if ($start && $end) {
            $sheet->setCellValue('C2', "Periode: {$start} s/d {$end}");
        } else {
            $sheet->setCellValue('C2', '');
        }

        $sheet->getStyle('C2')->getFont()
            ->setItalic(true)
            ->setSize(10);

        $sheet->getStyle('C2')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // ==================================================
        // CREATED AT REPORT (SELALU ADA)
        // ==================================================
        $sheet->mergeCells('C3:J3');
        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('d-m-Y H:i:s'));
        $sheet->getStyle('C3')->getFont()
            ->setSize(9);

        $sheet->getStyle('C3')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);



        // ==================================================
        // HEADER TABLE
        // ==================================================
        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'User ID',
            'C' => 'Machine IP',
            'D' => 'Platform',
            'E' => 'User Agent',
            'F' => 'Session Data',
            'G' => 'Login Time'
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // ==================================================
        // DATA
        // ==================================================
        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['userId']);
            $sheet->setCellValue('C' . $row, $d['machineIp']);
            $sheet->setCellValue('D' . $row, $d['platform']);
            $sheet->setCellValue('E' . $row, $d['userAgent']);
            $sheet->setCellValue('F' . $row, $d['sessionData']);
            $sheet->setCellValue('G' . $row, $d['createdDtm']);
            $row++;
        }

        // ==================================================
        // BORDER TABLE
        // ==================================================
        $sheet->getStyle("A{$headerRow}:G" . ($row - 1))
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ==================================================
        // AUTOSIZE COLUMN
        // ==================================================
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ==================================================
        // OUTPUT
        // ==================================================
        $filename = 'DITTIPIDTER_LOGIN_LOG_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }



    public function exportlogout()
    {
        $db = \Config\Database::connect();

        $start = $this->request->getGet('startDate');
        $end   = $this->request->getGet('endDate');

        $builder = $db->table('logout');

        if ($start && $end) {
            $builder->where('timestamp >=', $start . ' 00:00:00')
                ->where('timestamp <=', $end . ' 23:59:59');
        }

        $data = $builder->orderBy('timestamp', 'DESC')
            ->get()
            ->getResultArray();

        // ==================================================
        // SPREADSHEET INIT
        // ==================================================
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ==================================================
        // ROW HEIGHT (KOP)
        // ==================================================
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // ==================================================
        // MERGE AREA
        // ==================================================
        $sheet->mergeCells('A1:B3');   // LOGO
        $sheet->mergeCells('C1:J1');   // JUDUL
        $sheet->mergeCells('C2:J2');   // PERIODE

        // ==================================================
        // LOGO
        // ==================================================
        $logo = new Drawing();
        $logo->setName('Company Logo');
        $logo->setPath(WRITEPATH . 'uploads/photos/logodit.webp'); // ganti sesuai logo kamu
        $logo->setHeight(70);
        $logo->setCoordinates('A1');
        $logo->setOffsetX(20);
        $logo->setOffsetY(10);
        $logo->setWorksheet($sheet);

        // ==================================================
        // JUDUL
        // ==================================================
        $sheet->setCellValue('C1', 'DITTIPIDTER – LOGOUT LOG');
        $sheet->getStyle('C1')->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('C1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // ==================================================
        // SUB JUDUL / PERIODE (OPSIONAL)
        // ==================================================
        if ($start && $end) {
            $sheet->setCellValue('C2', "Periode: {$start} s/d {$end}");
        } else {
            $sheet->setCellValue('C2', '');
        }

        $sheet->getStyle('C2')->getFont()
            ->setItalic(true)
            ->setSize(10);

        $sheet->getStyle('C2')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // ==================================================
        // CREATED AT REPORT (SELALU ADA)
        // ==================================================
        $sheet->mergeCells('C3:J3');
        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('d-m-Y H:i:s'));
        $sheet->getStyle('C3')->getFont()
            ->setSize(9);

        $sheet->getStyle('C3')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);


        // ==================================================
        // HEADER TABLE
        // ==================================================
        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'User ID',
            'C' => 'Alasan',
            'D' => 'Oleh',
            'E' => 'Waktu Logout',
            'F' => 'IP Address',
            'G' => 'Platform',
            'H' => 'User Agent',
            'I' => 'Data Terlogout',
            'J' => 'Pelogout'
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // ==================================================
        // DATA
        // ==================================================
        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['userId']);
            $sheet->setCellValue('C' . $row, $d['alasan']);
            $sheet->setCellValue('D' . $row, $d['oleh']);
            $sheet->setCellValue('E' . $row, $d['timestamp']);
            $sheet->setCellValue('F' . $row, $d['machineIp']);
            $sheet->setCellValue('G' . $row, $d['platform']);
            $sheet->setCellValue('H' . $row, $d['userAgent']);
            $sheet->setCellValue('I' . $row, $d['terlogout']);
            $sheet->setCellValue('J' . $row, $d['pelogout']);
            $row++;
        }

        // ==================================================
        // BORDER TABLE
        // ==================================================
        $sheet->getStyle("A{$headerRow}:J" . ($row - 1))
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ==================================================
        // AUTOSIZE
        // ==================================================
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ==================================================
        // OUTPUT
        // ==================================================
        $filename = 'LOGOUT_LOG_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}