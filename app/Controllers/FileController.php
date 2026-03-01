<?php

namespace App\Controllers;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Controller;

class FileController extends BaseController
{
    // Upload foto user
    public function uploadPhoto()
    {
        $file = $this->request->getFile('photo');

        if (!$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File invalid']);
        }

        // batasi 10MB
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File terlalu besar']);
        }

        $newName = uniqid() . '_' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/photos', $newName);

        return $this->response->setJSON(['status' => 'success', 'file' => $newName]);
    }

}
