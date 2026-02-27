<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;

class Lukisan extends Controller
{
    public function show($filename)
    {
        // 1️⃣ CEK LOGIN (WAJIB)
        if (! session()->get('isLoggedIn')) {
            return redirect()->route('asktoin');
        }

        // 2️⃣ CEGAT PATH TRAVERSAL
        $filename = basename($filename);

        // 3️⃣ PATH FIX
        $path = WRITEPATH . 'uploads/lukisan/' . $filename;

        // 4️⃣ CEK FILE ADA
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        // 5️⃣ VALIDASI MIME
        $mime = mime_content_type($path);
        $allowed = ['image/webp', 'image/jpeg', 'image/png'];

        if (! in_array($mime, $allowed, true)) {
            return $this->response->setStatusCode(403);
        }

        // 6️⃣ KIRIM FILE (NO CACHE)
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody(file_get_contents($path));
    }
}