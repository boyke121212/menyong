<?php
$item = $item ?? [];
$id = $item['id'] ?? '';
$judul = old('judul', $item['judul'] ?? '');
$isi = old('isi', $item['isi'] ?? '');
$tanggalInput = old('tanggal');
$tanggalDb = $item['tanggal'] ?? '';
$tanggalRaw = ($tanggalInput !== null && $tanggalInput !== '') ? $tanggalInput : $tanggalDb;
$tanggal = '';

if (!empty($tanggalRaw)) {
    // Sudah format HTML date
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalRaw)) {
        $tanggal = $tanggalRaw;
    }
    // Format datetime SQL: YYYY-MM-DD HH:ii:ss
    elseif (preg_match('/^(\d{4}-\d{2}-\d{2})\s+\d{2}:\d{2}:\d{2}$/', $tanggalRaw, $m)) {
        $tanggal = $m[1];
    }
    // Format Indonesia: DD-MM-YYYY
    elseif (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $tanggalRaw, $m)) {
        $tanggal = $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    // Format Indonesia: DD/MM/YYYY
    elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $tanggalRaw, $m)) {
        $tanggal = $m[3] . '-' . $m[2] . '-' . $m[1];
    } else {
        $timestamp = strtotime($tanggalRaw);
        $tanggal = $timestamp ? date('Y-m-d', $timestamp) : '';
    }
}
$foto = $item['foto'] ?? '';
$pdf = $item['pdf'] ?? '';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Berita</h3>
                </div>

                <form action="<?= site_url('berita/update') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= esc($id) ?>">

                    <div class="box-body">
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul" class="form-control" value="<?= esc($judul) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Isi</label>
                            <textarea id="isi_editor" name="isi" class="form-control" rows="10"
                                required><?= $isi ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                value="<?= esc($tanggal) ?>" onfocus="this.showPicker && this.showPicker()" required>
                        </div>

                        <div class="form-group">
                            <label>Upload Foto (opsional)</label>
                            <input type="file" id="foto_input" name="foto" class="form-control" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin ganti foto.</small>
                            <div id="foto_preview_wrap" style="margin-top:10px;<?= empty($foto) ? 'display:none;' : '' ?>">
                                <img id="foto_preview"
                                    src="<?= !empty($foto) ? site_url('tampilberita/' . rawurlencode($foto)) : '' ?>"
                                    alt="Foto berita" style="max-width:260px;height:auto;border:1px solid #ddd;padding:4px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Upload PDF (opsional)</label>
                            <input type="file" id="pdf_input" name="pdf" class="form-control" accept="application/pdf">
                            <small class="text-muted">Kosongkan jika tidak ingin ganti PDF.</small>
                        </div>

                        <div id="pdf_preview_wrap" class="pdf-preview" style="<?= empty($pdf) ? 'display:none;' : '' ?>">
                            <label>Dokumen PDF Saat Ini</label>
                            <iframe id="pdf_preview"
                                src="<?= !empty($pdf) ? site_url('berita/pdf/' . rawurlencode($pdf)) : '' ?>" width="100%"
                                height="520"></iframe>
                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="<?= site_url('berita') ?>" class="btn btn-default">Kembali</a>
                    </div>
                </form>
            </div>

            <?php if (session()->getFlashdata('flashsuccess')) : ?>
            <div class="alert alert-success">
                <?= esc(session()->getFlashdata('flashsuccess')) ?>
            </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('flasherror')) : ?>
            <div class="alert alert-danger">
                <?= esc(session()->getFlashdata('flasherror')) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
if (typeof tinymce !== 'undefined') {
    tinymce.init({
        selector: '#isi_editor',
        height: 320,
        menubar: false,
        plugins: 'lists link code',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
        branding: false
    });
}

(function() {
    var fotoInput = document.getElementById('foto_input');
    var fotoPreview = document.getElementById('foto_preview');
    var fotoWrap = document.getElementById('foto_preview_wrap');

    var pdfInput = document.getElementById('pdf_input');
    var pdfPreview = document.getElementById('pdf_preview');
    var pdfWrap = document.getElementById('pdf_preview_wrap');
    var pdfObjectUrl = null;

    if (fotoInput) {
        fotoInput.addEventListener('change', function(e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            var url = URL.createObjectURL(file);
            fotoPreview.src = url;
            fotoWrap.style.display = 'block';
        });
    }

    if (pdfInput) {
        pdfInput.addEventListener('change', function(e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            if (pdfObjectUrl) {
                URL.revokeObjectURL(pdfObjectUrl);
            }
            pdfObjectUrl = URL.createObjectURL(file);
            pdfPreview.src = pdfObjectUrl;
            pdfWrap.style.display = 'block';
        });
    }
})();
</script>

<style>
.pdf-preview {
    margin-top: 30px;
    padding-top: 16px;
    border-top: 1px dashed #ddd;
}

.pdf-preview iframe {
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
}
</style>
