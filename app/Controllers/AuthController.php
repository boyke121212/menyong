<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\JWT as JWTConfig;
use App\Services\LoginAuditService;
use App\Services\UsnService;

class AuthController extends ResourceController
{
    protected $format = 'json';
    protected Dauo $dauo;
    // JWT pertama: login



    public function login()
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['message' => 'Method not allowed']);
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if (!$username || !$password) {
            // return $this->response->setStatusCode(400)
            //     ->setJSON(['message' => 'Username dan password wajib diisi']);
            $session = session();
            $session->setFlashdata("flashfail", "Username dan password wajib diisi");
            return redirect()->to('asktoin');
        }

        $authService = new UsnService();

        // 🔐 STEP 1: AUTH DULU
        $user = $authService->authThis($username, $password);

        if ($user === null) {
            // return $this->response->setStatusCode(401)
            //     ->setJSON(['message' => 'Username / Password salah']);
            $session = session();
            $session->setFlashdata("flashfail", "Username / Password salah");
            return redirect()->to('asktoin');
        }


        $session = session();
        $agent = $this->request->getUserAgent();


        $sessionArray = array(
            'userId' => $user['userId'],
            'role' => $user['roleId'],
            'name' => $user['name'],
            'username' => $user['username'],
            'lastLogin' => date('Y-m-d H:i:s'),
            'isLoggedIn' => TRUE,
            'last_activity' => time()   // ⬅️ WAJIB ADA
        );


        $loginInfo = array(
            "userId" => $user['userId'],
            "sessionData" => json_encode($sessionArray),
            "machineIp" => $_SERVER['REMOTE_ADDR'],
            "userAgent" => $_SERVER['HTTP_USER_AGENT'],
            "agentString" => $agent->getAgentString(),
            "platform" => $agent->getPlatform()
        );

        $auditService = new LoginAuditService();
        $hasil = $auditService->logLogin($loginInfo);


        $session->set('keadaan', "home");
        $session->set($sessionArray);

        $roleId = (int) $user['roleId'];
        if ($roleId === 8) {

            $session->setFlashdata('flashfail', 'Anda Tidak Memiliki Akses Backend');
            $session->destroy();
            return redirect()->route('asktoin');
        } else {

            $session->regenerate(true);
            return redirect()
                ->route('dashboard')
                ->setStatusCode(303);


            // return view('app', [
            //     'title'   => 'D.O.A.S - Dashboard',
            //     'nama'    => $user['name'],
            //     'foto'    => $user['foto'],
            //     'role'    => $roleId,
            //     'keadaan' => 'Home',
            //     'page'    => 'dashboard',
            // ]);
        }
    }

}
