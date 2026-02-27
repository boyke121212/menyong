<?php

namespace App\Services;

use App\Models\Dauo;

class LoginAuditService
{
    protected Dauo $dauo;

    public function __construct()
    {
        $this->dauo = new Dauo();
    }

    /**
     * Simpan riwayat login user
     */
    public function logLogin(array $loginInfo): bool
    {
        // validasi minimal (aman & ringan)
        if (!isset($loginInfo['userId'])) {
            return false;
        }

        // insert ke tabel tbl_last_login
        $hasil = $this->dauo->input_data($loginInfo, 'tbl_last_login');

        return $hasil === 'sukses' || $hasil === true;
    }
}
