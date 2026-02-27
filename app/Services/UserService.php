<?php

namespace App\Services;

use App\Models\Deden;
use App\Models\Dauo;
use Config\Services;

class UserService
{
    protected Deden $userModel;

    public function __construct()
    {
        $this->userModel = new Deden();
        $this->dauo = new Dauo();
    }

    public function getDataUser($userId)
    {
        return $this->userModel->getdatauser($userId); // boleh null
    }
}
