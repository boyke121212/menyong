<?php

namespace App\Controllers;


class Dashboard extends BaseController
{
    public function index()
    {
        $session = session();

        return view('app', [
            'title'   => 'D.O.A.S - Dashboard',
            'keadaan' => 'Home',
            'page'    => 'dashboard',

            // session data
            'userId'  => $session->get('userId'),
            'nama'    => $session->get('name'),
            'username' => $session->get('username'),
            'role'    => $session->get('role'),
        ]);
    }
}