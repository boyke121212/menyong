<?php
$isi   = (!empty($data) && isset($data[0]->isi))   ? $data[0]->isi   : 'jelaskan apa itu doas disini';
$judul = (!empty($data) && isset($data[0]->judul)) ? $data[0]->judul : 'Judul disini';
$pdf   = (!empty($data) && isset($data[0]->pdf))   ? $data[0]->pdf   : '';
?>

<div class="container-fluid doas-page">

    <!-- PAGE TITLE -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <h2 class="page-title">Manajemen DOAS</h2>
            <p class="page-subtitle">Kelola penjelasan dan dokumen resmi DOAS</p>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">

            <div class="box box-primary doas-box">
                <div class="box-header">
                    <h3 class="box-title">Informasi DOAS</h3>
                </div>

                <form action="<?= site_url('simpandoas') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="box-body">

                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul" class="form-control" value="<?= esc($judul) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Penjelasan D.O.A.S</label>
                            <textarea id="konten" name="konten" class="form-control" rows="10"
                                required><?= esc($isi) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Upload Dokumen (PDF)</label>
                            <input type="file" name="dokumen" class="form-control" accept="application/pdf">
                            <small class="text-muted">Upload file PDF (opsional)</small>
                        </div>

                        <?php if (!empty($pdf)) : ?>
                        <div class="pdf-preview">
                            <label>Dokumen PDF Saat Ini</label>
                            <iframe src="<?= site_url('doas/pdf/' . rawurlencode($pdf)) ?>" width="100%"
                                height="520"></iframe>
                        </div>
                        <?php endif; ?>

                    </div>

                    <div class="box-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                        <a href="<?= site_url('/') ?>" class="btn btn-default">
                            Batal
                        </a>
                    </div>
                </form>
            </div>

            <!-- FLASH -->
            <?php if (session()->getFlashdata('flashsuccess')) : ?>
            <div class="toast toast-success">
                <?= esc(session()->getFlashdata('flashsuccess')) ?>
            </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('flasherror')) : ?>
            <div class="toast toast-error">
                <?= esc(session()->getFlashdata('flasherror')) ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>



<script>
tinymce.init({
    selector: '#konten',
    height: 300,
    menubar: false,
    plugins: [],
    toolbar: 'undo redo | bold italic underline',
    branding: false
});
</script>

<style>
/* =========================
   PAGE
   ========================= */
.doas-page {
    padding-top: 20px;
}

.page-title {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 4px;
}

.page-subtitle {
    color: #6c757d;
    margin-bottom: 24px;
}

/* =========================
   BOX
   ========================= */
.doas-box {
    border-radius: 8px;
    border-top: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.doas-box .box-header {
    border-bottom: 1px solid #eee;
    padding: 16px 20px;
}

.doas-box .box-title {
    font-size: 16px;
    font-weight: 600;
}

.doas-box .box-body {
    padding: 22px;
}

.doas-box .box-footer {
    padding: 16px 22px;
    background: #fafafa;
    border-top: 1px solid #eee;
}

/* =========================
   PDF PREVIEW
   ========================= */
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

/* =========================
   TOAST (KEEP)
   ========================= */
.toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    min-width: 300px;
    max-width: 420px;
    padding: 14px 18px;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    opacity: 0;
    z-index: 9999;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
    transition: all 0.35s ease;
}

.toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.toast-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.toast-error {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}
</style>
<script>
const toast = document.querySelector('.toast');
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.toast');

    toasts.forEach(function(toast) {
        setTimeout(function() {
            toast.classList.add('show');
        }, 100);

        setTimeout(function() {
            toast.classList.remove('show');
        }, 4000);
    });
});
</script>