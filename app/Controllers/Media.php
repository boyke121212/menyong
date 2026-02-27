<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Media extends Controller
{
    public function berita($filename)
    {
        $path = WRITEPATH . 'uploads/berita/' . $filename;

        if (!is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        $mime = mime_content_type($path);

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setBody(file_get_contents($path));
    }

    public function absensi()
    {
        $filename = $this->request->getGet('file');

        if (!$filename) {
            return $this->response->setStatusCode(404);
        }

        $path = WRITEPATH . 'uploads/absensi/' . $filename;

        if (!is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setHeader('Content-Type', mime_content_type($path))
            ->setBody(file_get_contents($path));
    }



    public function pdf($filename)
    {
        $path = WRITEPATH . 'uploads/pdf/' . $filename;

        if (!file_exists($path)) {
            return $this->response->setStatusCode(404, 'File tidak ditemukan');
        }

        return $this->response->download($path, null);
    }
}
