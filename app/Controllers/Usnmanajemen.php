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

    private function getRoleOptions(): array
    {
        return [
            1 => 'Super Admin',
            2 => 'Admin Utama',
            3 => 'Admin User',
            4 => 'Admin Berita',
            5 => 'Admin Anggaran',
            6 => 'Admin Kantor',
            7 => 'Admin Laporan',
            8 => 'User',
        ];
    }

    private function getCreatableRolesByActor(int $actorRoleId): array
    {
        $allRoles = $this->getRoleOptions();

        if ($actorRoleId === 1) {
            return $allRoles;
        }

        if ($actorRoleId === 2) {
            return array_intersect_key($allRoles, array_flip([3, 4, 5, 6, 7, 8]));
        }

        if ($actorRoleId === 3) {
            return array_intersect_key($allRoles, array_flip([8]));
        }

        return [];
    }

    private function getCurrentActorRoleId(): int
    {
        $session = session();
        $roleId = (int) ($session->get('role') ?? 0);

        if ($roleId > 0) {
            return $roleId;
        }

        $username = $session->get('username');
        if (!$username) {
            return 0;
        }

        $actor = $this->dauo->where('username', $username)->first();
        return (int) ($actor['roleId'] ?? 0);
    }

    private function ensureUserManagementLogTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `user_management_log` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(30) NOT NULL,
                `targetUserId` INT NULL,
                `targetUsername` VARCHAR(100) NULL,
                `targetName` VARCHAR(150) NULL,
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
                KEY `idx_targetUserId` (`targetUserId`),
                KEY `idx_actorUserId` (`actorUserId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query($sql);
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

    private function ensureLogBeritaTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `logberita` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(30) NOT NULL,
                `targetBeritaId` INT NULL,
                `targetJudul` VARCHAR(255) NULL,
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
                KEY `idx_targetBeritaId` (`targetBeritaId`),
                KEY `idx_actorUserId` (`actorUserId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query($sql);
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

    private function writeUserManagementLog(
        string $action,
        ?array $targetUser = null,
        string $description = '',
        array $payload = []
    ): void {
        $this->ensureUserManagementLogTable();

        $session = session();
        $actorUserId = $session->get('userId');
        $actor = null;

        if ($actorUserId) {
            $actor = $this->userModel->where('userId', $actorUserId)->first();
        }

        $agent = $this->request->getUserAgent();

        $this->db->table('user_management_log')->insert([
            'action' => $action,
            'targetUserId' => $targetUser['userId'] ?? null,
            'targetUsername' => $targetUser['username'] ?? null,
            'targetName' => $targetUser['name'] ?? null,
            'actorUserId' => $actor['userId'] ?? $actorUserId ?? null,
            'actorUsername' => $actor['username'] ?? $session->get('username'),
            'actorName' => $actor['name'] ?? $session->get('name'),
            'description' => $description,
            'payload' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'ipAddress' => $this->request->getIPAddress(),
            'userAgent' => $agent ? $agent->getAgentString() : null,
        ]);
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
        $actorRoleId = (int) ($user['roleId'] ?? 0);
        $allowedRoles = $this->getCreatableRolesByActor($actorRoleId);

        if (empty($allowedRoles)) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Role Anda tidak memiliki izin menambahkan user');
        }
        // dd($datauser);
        return view('app', [
            'title'   => 'D.O.A.S - Tambah User',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'adduser',
            'allowedRoles' => $allowedRoles,
            'page'    => 'tambahuser',
        ]);
    }

    public function profile()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        if (!$user) {
            return redirect()->to(site_url('logout'));
        }

        return view('app', [
            'title'       => 'D.O.A.S - Profile',
            'nama'        => $user['name'],
            'role'        => $user['roleId'],
            'keadaan'     => 'Profile',
            'profileUser' => $user,
            'page'        => 'profile',
        ]);
    }

    public function updateProfile()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        if (!$user) {
            return redirect()->to(site_url('logout'));
        }

        $name = trim((string) $this->request->getPost('name'));
        $pangkat = trim((string) $this->request->getPost('pangkat'));
        $jabatan = trim((string) $this->request->getPost('jabatan'));
        $subdit = trim((string) $this->request->getPost('subdit'));

        if ($name === '' || $pangkat === '' || $jabatan === '' || $subdit === '') {
            return redirect()->back()
                ->withInput()
                ->with('flasherror', 'Nama, pangkat, jabatan, dan subdit wajib diisi');
        }

        $allowedSubdit = [
            'Staff Pimpinan',
            'Subdit 1',
            'Subdit 2',
            'Subdit 3',
            'Subdit 4',
            'Subdit 5',
        ];
        if (!in_array($subdit, $allowedSubdit, true)) {
            return redirect()->back()
                ->withInput()
                ->with('flasherror', 'Subdit tidak valid');
        }

        $oldData = [
            'name' => $user['name'] ?? null,
            'pangkat' => $user['pangkat'] ?? null,
            'jabatan' => $user['jabatan'] ?? null,
            'subdit' => $user['subdit'] ?? null,
        ];

        $newData = [
            'name' => $name,
            'pangkat' => $pangkat,
            'jabatan' => $jabatan,
            'subdit' => $subdit,
        ];

        $this->userModel->where('userId', $user['userId'])->update(null, $newData);
        $session->set('name', $name);

        $target = $this->userModel->where('userId', $user['userId'])->first();
        $this->writeUserManagementLog(
            'EDIT_PROFILE',
            $target ?: $user,
            'Mengubah profile sendiri',
            [
                'old' => $oldData,
                'new' => $newData,
            ]
        );

        return redirect()
            ->to(site_url('profile'))
            ->with('flashsuccess', 'Profile berhasil diperbarui');
    }

    public function updatePassword()
    {
        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        if (!$user) {
            return redirect()->to(site_url('logout'));
        }

        $oldPassword = (string) $this->request->getPost('old_password');
        $newPassword = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return redirect()->back()
                ->with('flasherror', 'Semua field password wajib diisi');
        }

        if (!password_verify($oldPassword, (string) ($user['password'] ?? ''))) {
            return redirect()->back()
                ->with('flasherror', 'Password lama tidak sesuai');
        }

        if (strlen($newPassword) < 6) {
            return redirect()->back()
                ->with('flasherror', 'Password baru minimal 6 karakter');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()
                ->with('flasherror', 'Konfirmasi password tidak sama');
        }

        if (password_verify($newPassword, (string) ($user['password'] ?? ''))) {
            return redirect()->back()
                ->with('flasherror', 'Password baru harus berbeda dari password lama');
        }

        $this->userModel->where('userId', $user['userId'])->update(null, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $target = $this->userModel->where('userId', $user['userId'])->first();
        $this->writeUserManagementLog(
            'CHANGE_PASSWORD',
            $target ?: $user,
            'Mengubah password sendiri',
            []
        );

        return redirect()
            ->to(site_url('profile'))
            ->with('flashsuccess', 'Password berhasil diubah');
    }

    public function simpan()
    {
        $session = session();
        $username = $session->get('username');
        $actor = $this->dauo->where('username', $username)->first();
        $actorRoleId = (int) ($actor['roleId'] ?? 0);
        $allowedRoles = $this->getCreatableRolesByActor($actorRoleId);

        if (empty($allowedRoles)) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Role Anda tidak memiliki izin menambahkan user');
        }

        $requestedRole = (int) $this->request->getPost('roleId');
        if (!array_key_exists($requestedRole, $allowedRoles)) {
            return redirect()->back()
                ->withInput()
                ->with('flasherror', 'Role yang dipilih tidak diizinkan');
        }

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
            'roleId'     => (string) $requestedRole,
            'nip'        => $this->request->getPost('nip'),
            'pangkat'        => $this->request->getPost('pangkat'),
            'jabatan'        => $this->request->getPost('jabatan'),
            'subdit'     => $this->request->getPost('subdit'),
            'status'     => 'belum',
            'createdBy'  => $_SESSION['userId'],
            'createdDtm' => date('Y-m-d H:i:s'),
        ];

        $this->userModel->insert($data);
        $insertedId = $this->userModel->getInsertID();
        $newUser = null;
        if ($insertedId) {
            $newUser = $this->userModel->where('userId', $insertedId)->first();
        }

        $this->writeUserManagementLog(
            'ADD_USER',
            $newUser ?: ['userId' => $insertedId, 'username' => $data['username'], 'name' => $data['name']],
            'Menambahkan user baru',
            [
                'new' => $newUser ?: $data
            ]
        );

        return redirect()->to('usnmanajemen')
            ->with('flashsuccess', 'User berhasil ditambahkan');
    }


    public function edit()
    {
        $userId =  $this->request->getGet('userId');
        $target = $this->userModel->where('userId', $userId)->first();
        $actorRoleId = $this->getCurrentActorRoleId();
        if (
            $target &&
            $actorRoleId === 2 &&
            (int) ($target['roleId'] ?? 0) === 2
        ) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Sesama Admin Utama tidak dapat saling edit');
        }

        if (
            $target &&
            $actorRoleId === 3 &&
            in_array((int) ($target['roleId'] ?? 0), [2, 3], true)
        ) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Role Admin User tidak diizinkan edit role 2/3');
        }

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

        $actorRoleId = $this->getCurrentActorRoleId();
        if (
            $actorRoleId === 2 &&
            (int) ($userLama['roleId'] ?? 0) === 2
        ) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Sesama Admin Utama tidak dapat saling edit');
        }

        if (
            $actorRoleId === 3 &&
            in_array((int) ($userLama['roleId'] ?? 0), [2, 3], true)
        ) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Role Admin User tidak diizinkan edit role 2/3');
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
        $userBaru = $this->userModel->where('userId', $userId)->first();

        $this->writeUserManagementLog(
            'EDIT_USER',
            $userBaru ?: $userLama,
            'Mengubah data user',
            [
                'old' => [
                    'name' => $userLama['name'] ?? null,
                    'pangkat' => $userLama['pangkat'] ?? null,
                    'jabatan' => $userLama['jabatan'] ?? null,
                    'subdit' => $userLama['subdit'] ?? null,
                ],
                'new' => $dataUpdate,
            ]
        );

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

        if ((int) ($user['roleId'] ?? 0) === 1) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'User role Super Admin tidak dapat dihapus');
        }

        $actorRoleId = $this->getCurrentActorRoleId();
        if (
            $actorRoleId === 2 &&
            (int) ($user['roleId'] ?? 0) === 2
        ) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Sesama Admin Utama tidak dapat saling hapus');
        }

        if (
            $actorRoleId === 3 &&
            in_array((int) ($user['roleId'] ?? 0), [2, 3], true)
        ) {
            return redirect()
                ->to(site_url('usnmanajemen'))
                ->with('flasherror', 'Role Admin User tidak diizinkan hapus role 2/3');
        }



        // =============================
        // HAPUS DATA USER
        // =============================
        $this->userModel->where('userId', $userId)->delete();

        $this->writeUserManagementLog(
            'DELETE_USER',
            $user,
            'Menghapus user',
            [
                'deleted' => $user
            ]
        );

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

    public function user_management_log()
    {
        $this->ensureUserManagementLogTable();

        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Log User Management',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'user_management_log',
        ]);
    }

    public function log_kantor()
    {
        $this->ensureLogKantorTable();

        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Log Perubahan Kantor',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'log_kantor',
        ]);
    }

    public function log_berita()
    {
        $this->ensureLogBeritaTable();

        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Log Berita',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'log_berita',
        ]);
    }

    public function log_anggaran()
    {
        $this->ensureLogAnggaranTable();

        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Log Anggaran',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'log_anggaran',
        ]);
    }

    public function log_about()
    {
        $this->ensureLogAboutTable();

        $session = session();
        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        return view('app', [
            'title'   => 'D.O.A.S - Log About',
            'nama'    => $user['name'],
            'role'    => $user['roleId'],
            'keadaan' => 'apa',
            'page'    => 'log_about',
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
        $deletedUsers = $this->userModel
            ->select('userId, username, name, nip, jabatan, subdit, roleId')
            ->whereIn('userId', array_column($users, 'userId'))
            ->findAll();

        $this->userModel
            ->whereIn('userId', array_column($users, 'userId'))
            ->delete();

        foreach ($deletedUsers as $deletedUser) {
            $this->writeUserManagementLog(
                'DELETE_USER',
                $deletedUser,
                'Menghapus user (bulk delete)',
                [
                    'deleted' => $deletedUser,
                    'mode' => 'bulk'
                ]
            );
        }

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

    public function userManagementLogData()
    {
        $this->ensureUserManagementLogTable();

        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search    = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('startDate');
        $endDate   = $request->getPost('endDate');
        $action    = $request->getPost('action');

        $builder = $db->table('user_management_log');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('targetUserId', $search)
                ->orLike('targetUsername', $search)
                ->orLike('targetName', $search)
                ->orLike('actorUserId', $search)
                ->orLike('actorUsername', $search)
                ->orLike('actorName', $search)
                ->orLike('description', $search)
                ->orLike('ipAddress', $search)
                ->orLike('userAgent', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('createdAt', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('user_management_log')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }

    public function logKantorData()
    {
        $this->ensureLogKantorTable();

        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search    = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('startDate');
        $endDate   = $request->getPost('endDate');
        $action    = $request->getPost('action');

        $builder = $db->table('logkantor');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('actorUserId', $search)
                ->orLike('actorUsername', $search)
                ->orLike('actorName', $search)
                ->orLike('description', $search)
                ->orLike('oldData', $search)
                ->orLike('newData', $search)
                ->orLike('ipAddress', $search)
                ->orLike('userAgent', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('createdAt', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('logkantor')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }

    public function logBeritaData()
    {
        $this->ensureLogBeritaTable();

        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search    = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('startDate');
        $endDate   = $request->getPost('endDate');
        $action    = $request->getPost('action');

        $builder = $db->table('logberita');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('targetBeritaId', $search)
                ->orLike('targetJudul', $search)
                ->orLike('actorUserId', $search)
                ->orLike('actorUsername', $search)
                ->orLike('actorName', $search)
                ->orLike('description', $search)
                ->orLike('payload', $search)
                ->orLike('ipAddress', $search)
                ->orLike('userAgent', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('createdAt', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('logberita')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }

    public function logAnggaranData()
    {
        $this->ensureLogAnggaranTable();

        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search    = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('startDate');
        $endDate   = $request->getPost('endDate');
        $action    = $request->getPost('action');

        $builder = $db->table('loganggaran');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('tahun', $search)
                ->orLike('actorUserId', $search)
                ->orLike('actorUsername', $search)
                ->orLike('actorName', $search)
                ->orLike('description', $search)
                ->orLike('payload', $search)
                ->orLike('ipAddress', $search)
                ->orLike('userAgent', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('createdAt', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('loganggaran')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }

    public function logAboutData()
    {
        $this->ensureLogAboutTable();

        $request = service('request');
        $db = \Config\Database::connect();

        $draw   = intval($request->getPost('draw'));
        $start  = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));

        $search    = $request->getPost('search')['value'] ?? '';
        $startDate = $request->getPost('startDate');
        $endDate   = $request->getPost('endDate');
        $action    = $request->getPost('action');

        $builder = $db->table('logabout');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('actorUserId', $search)
                ->orLike('actorUsername', $search)
                ->orLike('actorName', $search)
                ->orLike('description', $search)
                ->orLike('oldData', $search)
                ->orLike('newData', $search)
                ->orLike('ipAddress', $search)
                ->orLike('userAgent', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('createdAt', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        $recordsTotal = $db->table('logabout')->countAll();

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'csrfHash'        => csrf_hash()
        ]);
    }

    public function exportUserManagementLog()
    {
        $this->ensureUserManagementLogTable();

        $db = \Config\Database::connect();

        $startDate = $this->request->getGet('startDate');
        $endDate   = $this->request->getGet('endDate');
        $action    = $this->request->getGet('action');

        $builder = $db->table('user_management_log');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        $data = $builder->orderBy('createdAt', 'DESC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // KOP
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $sheet->mergeCells('A1:B3');   // logo
        $sheet->mergeCells('C1:J1');   // judul
        $sheet->mergeCells('C2:J2');   // periode
        $sheet->mergeCells('C3:J3');   // tanggal cetak

        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Logo DITTIPIDTER');
            $logo->setPath($logoPath);
            $logo->setHeight(70);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(20);
            $logo->setOffsetY(10);
            $logo->setWorksheet($sheet);
        }

        $sheet->setCellValue('C1', 'DITTIPIDTER – LOG USER MANAGEMENT');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('C1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $filterText = 'Filter: Semua Data';
        if ($startDate && $endDate) {
            $filterText = "Filter Tanggal: {$startDate} s/d {$endDate}";
        }
        if ($action) {
            $filterText .= " | Aksi: {$action}";
        }
        $sheet->setCellValue('C2', $filterText);
        $sheet->getStyle('C2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('C2')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('Y-m-d H:i:s'));
        $sheet->getStyle('C3')->getFont()->setSize(9);
        $sheet->getStyle('C3')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'Aksi',
            'C' => 'Target User ID',
            'D' => 'Target Username',
            'E' => 'Target Name',
            'F' => 'Actor',
            'G' => 'Deskripsi',
            'H' => 'IP',
            'I' => 'Waktu',
            'J' => 'Payload',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
        }

        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['action']);
            $sheet->setCellValue('C' . $row, $d['targetUserId']);
            $sheet->setCellValue('D' . $row, $d['targetUsername']);
            $sheet->setCellValue('E' . $row, $d['targetName']);
            $sheet->setCellValue('F' . $row, trim(($d['actorUserId'] ?? '-') . ' / ' . ($d['actorUsername'] ?? '-') . ' / ' . ($d['actorName'] ?? '-')));
            $sheet->setCellValue('G' . $row, $d['description']);
            $sheet->setCellValue('H' . $row, $d['ipAddress']);
            $sheet->setCellValue('I' . $row, $d['createdAt']);
            $sheet->setCellValue('J' . $row, $d['payload'] ?? '');
            $row++;
        }

        if ($row > $headerRow + 1) {
            $sheet->getStyle("A{$headerRow}:J" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'LOG_USER_MANAGEMENT_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportLogKantor()
    {
        $this->ensureLogKantorTable();

        $db = \Config\Database::connect();

        $startDate = $this->request->getGet('startDate');
        $endDate   = $this->request->getGet('endDate');
        $action    = $this->request->getGet('action');

        $builder = $db->table('logkantor');

        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }

        if ($action) {
            $builder->where('action', $action);
        }

        $data = $builder->orderBy('createdAt', 'DESC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:I1');
        $sheet->mergeCells('C2:I2');
        $sheet->mergeCells('C3:I3');

        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Logo DITTIPIDTER');
            $logo->setPath($logoPath);
            $logo->setHeight(70);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(20);
            $logo->setOffsetY(10);
            $logo->setWorksheet($sheet);
        }

        $sheet->setCellValue('C1', 'DITTIPIDTER - LOG PERUBAHAN KANTOR');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('C1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $filterText = 'Filter: Semua Data';
        if ($startDate && $endDate) {
            $filterText = "Filter Tanggal: {$startDate} s/d {$endDate}";
        }
        if ($action) {
            $filterText .= " | Aksi: {$action}";
        }
        $sheet->setCellValue('C2', $filterText);
        $sheet->getStyle('C2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('C2')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('Y-m-d H:i:s'));
        $sheet->getStyle('C3')->getFont()->setSize(9);
        $sheet->getStyle('C3')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'Aksi',
            'C' => 'Pelaku',
            'D' => 'Deskripsi',
            'E' => 'Data Lama',
            'F' => 'Data Baru',
            'G' => 'IP',
            'H' => 'User Agent',
            'I' => 'Waktu',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
        }

        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['action']);
            $sheet->setCellValue('C' . $row, trim(($d['actorUserId'] ?? '-') . ' / ' . ($d['actorUsername'] ?? '-') . ' / ' . ($d['actorName'] ?? '-')));
            $sheet->setCellValue('D' . $row, $d['description']);
            $sheet->setCellValue('E' . $row, $d['oldData'] ?? '');
            $sheet->setCellValue('F' . $row, $d['newData'] ?? '');
            $sheet->setCellValue('G' . $row, $d['ipAddress']);
            $sheet->setCellValue('H' . $row, $d['userAgent']);
            $sheet->setCellValue('I' . $row, $d['createdAt']);
            $row++;
        }

        if ($row > $headerRow + 1) {
            $sheet->getStyle("A{$headerRow}:I" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'LOG_PERUBAHAN_KANTOR_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportLogBerita()
    {
        $this->ensureLogBeritaTable();

        $db = \Config\Database::connect();
        $startDate = $this->request->getGet('startDate');
        $endDate   = $this->request->getGet('endDate');
        $action    = $this->request->getGet('action');

        $builder = $db->table('logberita');
        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }
        if ($action) {
            $builder->where('action', $action);
        }

        $data = $builder->orderBy('createdAt', 'DESC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:I1');
        $sheet->mergeCells('C2:I2');
        $sheet->mergeCells('C3:I3');

        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Logo DITTIPIDTER');
            $logo->setPath($logoPath);
            $logo->setHeight(70);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(20);
            $logo->setOffsetY(10);
            $logo->setWorksheet($sheet);
        }

        $sheet->setCellValue('C1', 'DITTIPIDTER - LOG BERITA');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->setCellValue('C2', 'Filter: ' . ($startDate && $endDate ? "{$startDate} s/d {$endDate}" : 'Semua Data') . ($action ? " | Aksi: {$action}" : ''));
        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('Y-m-d H:i:s'));

        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'Aksi',
            'C' => 'Berita',
            'D' => 'Pelaku',
            'E' => 'Deskripsi',
            'F' => 'Payload',
            'G' => 'IP',
            'H' => 'User Agent',
            'I' => 'Waktu',
        ];
        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
        }

        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['action']);
            $sheet->setCellValue('C' . $row, trim(($d['targetBeritaId'] ?? '-') . ' / ' . ($d['targetJudul'] ?? '-')));
            $sheet->setCellValue('D' . $row, trim(($d['actorUserId'] ?? '-') . ' / ' . ($d['actorUsername'] ?? '-') . ' / ' . ($d['actorName'] ?? '-')));
            $sheet->setCellValue('E' . $row, $d['description']);
            $sheet->setCellValue('F' . $row, $d['payload'] ?? '');
            $sheet->setCellValue('G' . $row, $d['ipAddress']);
            $sheet->setCellValue('H' . $row, $d['userAgent']);
            $sheet->setCellValue('I' . $row, $d['createdAt']);
            $row++;
        }

        if ($row > $headerRow + 1) {
            $sheet->getStyle("A{$headerRow}:I" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'LOG_BERITA_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportLogAnggaran()
    {
        $this->ensureLogAnggaranTable();

        $db = \Config\Database::connect();
        $startDate = $this->request->getGet('startDate');
        $endDate   = $this->request->getGet('endDate');
        $action    = $this->request->getGet('action');

        $builder = $db->table('loganggaran');
        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }
        if ($action) {
            $builder->where('action', $action);
        }

        $data = $builder->orderBy('createdAt', 'DESC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:I1');
        $sheet->mergeCells('C2:I2');
        $sheet->mergeCells('C3:I3');

        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Logo DITTIPIDTER');
            $logo->setPath($logoPath);
            $logo->setHeight(70);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(20);
            $logo->setOffsetY(10);
            $logo->setWorksheet($sheet);
        }

        $sheet->setCellValue('C1', 'DITTIPIDTER - LOG ANGGARAN');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->setCellValue('C2', 'Filter: ' . ($startDate && $endDate ? "{$startDate} s/d {$endDate}" : 'Semua Data') . ($action ? " | Aksi: {$action}" : ''));
        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('Y-m-d H:i:s'));

        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'Aksi',
            'C' => 'Tahun',
            'D' => 'Pelaku',
            'E' => 'Deskripsi',
            'F' => 'Payload',
            'G' => 'IP',
            'H' => 'User Agent',
            'I' => 'Waktu',
        ];
        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
        }

        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['action']);
            $sheet->setCellValue('C' . $row, $d['tahun']);
            $sheet->setCellValue('D' . $row, trim(($d['actorUserId'] ?? '-') . ' / ' . ($d['actorUsername'] ?? '-') . ' / ' . ($d['actorName'] ?? '-')));
            $sheet->setCellValue('E' . $row, $d['description']);
            $sheet->setCellValue('F' . $row, $d['payload'] ?? '');
            $sheet->setCellValue('G' . $row, $d['ipAddress']);
            $sheet->setCellValue('H' . $row, $d['userAgent']);
            $sheet->setCellValue('I' . $row, $d['createdAt']);
            $row++;
        }

        if ($row > $headerRow + 1) {
            $sheet->getStyle("A{$headerRow}:I" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'LOG_ANGGARAN_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportLogAbout()
    {
        $this->ensureLogAboutTable();

        $db = \Config\Database::connect();
        $startDate = $this->request->getGet('startDate');
        $endDate   = $this->request->getGet('endDate');
        $action    = $this->request->getGet('action');

        $builder = $db->table('logabout');
        if ($startDate && $endDate) {
            $builder->where('createdAt >=', $startDate . ' 00:00:00')
                ->where('createdAt <=', $endDate . ' 23:59:59');
        }
        if ($action) {
            $builder->where('action', $action);
        }

        $data = $builder->orderBy('createdAt', 'DESC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getRowDimension(1)->setRowHeight(42);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:I1');
        $sheet->mergeCells('C2:I2');
        $sheet->mergeCells('C3:I3');

        $logoPath = WRITEPATH . 'uploads/photos/logodit.webp';
        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Logo DITTIPIDTER');
            $logo->setPath($logoPath);
            $logo->setHeight(70);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(20);
            $logo->setOffsetY(10);
            $logo->setWorksheet($sheet);
        }

        $sheet->setCellValue('C1', 'DITTIPIDTER - LOG ABOUT');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
        $sheet->setCellValue('C2', 'Filter: ' . ($startDate && $endDate ? "{$startDate} s/d {$endDate}" : 'Semua Data') . ($action ? " | Aksi: {$action}" : ''));
        $sheet->setCellValue('C3', 'Tanggal Cetak: ' . date('Y-m-d H:i:s'));

        $headerRow = 5;
        $headers = [
            'A' => 'ID',
            'B' => 'Aksi',
            'C' => 'Pelaku',
            'D' => 'Deskripsi',
            'E' => 'Data Lama',
            'F' => 'Data Baru',
            'G' => 'IP',
            'H' => 'User Agent',
            'I' => 'Waktu',
        ];
        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $headerRow, $text);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
        }

        $row = $headerRow + 1;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['id']);
            $sheet->setCellValue('B' . $row, $d['action']);
            $sheet->setCellValue('C' . $row, trim(($d['actorUserId'] ?? '-') . ' / ' . ($d['actorUsername'] ?? '-') . ' / ' . ($d['actorName'] ?? '-')));
            $sheet->setCellValue('D' . $row, $d['description']);
            $sheet->setCellValue('E' . $row, $d['oldData'] ?? '');
            $sheet->setCellValue('F' . $row, $d['newData'] ?? '');
            $sheet->setCellValue('G' . $row, $d['ipAddress']);
            $sheet->setCellValue('H' . $row, $d['userAgent']);
            $sheet->setCellValue('I' . $row, $d['createdAt']);
            $row++;
        }

        if ($row > $headerRow + 1) {
            $sheet->getStyle("A{$headerRow}:I" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'LOG_ABOUT_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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
