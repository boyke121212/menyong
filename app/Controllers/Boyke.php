<?php

namespace App\Controllers;


use CodeIgniter\RESTful\ResourceController;
use App\Models\Dauo;
use App\Services\UsnService;


class Boyke extends BaseController
{
    protected Dauo $dauo;
    protected $format = 'json';

    public function __construct()
    {
        $this->dauo = new Dauo();
    }


    public function index() {}

    public function login()
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


        return view('app', [
            'title'   => 'D.O.A.S - Dashboard',
            'nama'    => $user['name'],
            'role'    => $roleId,
            'keadaan' => 'Home',
            'page'    => 'dashboard',
        ]);
    }



    public function asktoin(): string
    {
        return view('rumah');
    }

    public function logout()
    {

        $session = session();
        $session->destroy();
        return redirect()->route('asktoin');
    }
}