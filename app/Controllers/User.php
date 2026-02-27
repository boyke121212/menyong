<?php

namespace App\Controllers;

use App\Models\Deden;
use CodeIgniter\Controller;
use App\Services\UsnService;
use App\Models\Dauo;

class User extends Controller
{
    protected Dauo $dauo;

    public function __construct()
    {
        $this->userModel = new Deden();
        $this->dauo = new Dauo();
    }
    public function index()
    {
        return view('user/index');
    }

    public function datatables()
    {

        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'unauthorized',
                    'message' => 'Session expired'
                ]);
        }

        $username = $session->get('username');
        $user = $this->dauo->where('username', $username)->first();

        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'unauthorized',
                    'message' => 'User not found'
                ]);
        }

        $roleId = (int) $user['roleId'];
        if ($roleId === 8) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status' => 'forbidden',
                    'message' => 'No backend access'
                ]);
        }


        // ==== DATATABLES NORMAL ====
        $model = new Deden();
        $request = service('request');

        $draw   = $request->getPost('draw');
        $start  = $request->getPost('start');
        $length = $request->getPost('length');
        $searchArr = $request->getPost('search');
        $search = $searchArr['value'] ?? '';
        $orderArr = $request->getPost('order');
        $columns  = $request->getPost('columns');

        $orderColumn = null;
        $orderDir    = null;

        if (!empty($orderArr)) {
            $colIndex    = $orderArr[0]['column'];
            $orderDir    = $orderArr[0]['dir']; // asc / desc
            $orderColumn = $columns[$colIndex]['data'];
        }
        return $this->response->setJSON([
            "draw" => intval($draw),
            "recordsTotal" => $model->countAllData($roleId),
            "recordsFiltered" => $model->countFilteredData($search, $roleId),
            "data" => $model->getDatatables(
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir,
                $roleId
            )
        ]);
    }
}
