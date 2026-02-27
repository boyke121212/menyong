<?php

namespace App\Controllers;

class Testdb extends BaseController
{
    public function index()
    {
        $db = db_connect();
        return $db->getDatabase();
    }
}
