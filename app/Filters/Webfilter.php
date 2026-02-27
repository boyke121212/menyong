<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Webfilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // belum login
        if (! $session->get('isLoggedIn')) {
            return redirect()->route('asktoin');
        }

        // ⏱️ SESSION TIMEOUT (15 MENIT)
        $timeout = 15 * 60;

        if (time() - (int)$session->get('last_activity') > $timeout) {
            $session->destroy();
            return redirect()->route('asktoin')->with('flashfail', 'Sesi Anda telah berakhir');
        }

        // update aktivitas
        $session->set('last_activity', time());

        $username = $session->get('username');
        $dauo = model('Dauo');

        $user = $dauo->where('username', $username)->first();

        if (! $user) {
            $session->destroy();
            return redirect()->route('asktoin');
        }


        // cek role
        if ((int)$user['roleId'] === 8) {
            $session->setFlashdata('flashfail', 'Anda Tidak Memiliki Akses Backend');
            $session->destroy();
            return redirect()->route('asktoin');
        }

        $session->set([
            'pelaku' => $user['userId']
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}