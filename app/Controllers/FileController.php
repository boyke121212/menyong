<?php

namespace App\Controllers;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Controller;
use Dompdf\Dompdf;

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

    // Generate PDF contoh
    public function generatePDF()
    {
        $dompdf = new Dompdf();

        $html = "<h1>Contoh PDF</h1><p>Generated on ".date('Y-m-d H:i:s')."</p>";
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfName = uniqid() . '_document.pdf';
        $output = $dompdf->output();
        file_put_contents(WRITEPATH . 'uploads/pdf/' . $pdfName, $output);

        return $this->response->setJSON(['status' => 'success', 'file' => $pdfName]);
    }
}
