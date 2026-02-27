<?php
$item = $item ?? [];
$id = $item['id'] ?? '';
$judul = old('judul', $item['judul'] ?? '');
$isi = old('isi', $item['isi'] ?? '');
$tanggal = old('tanggal', $item['tanggal'] ?? '');
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
                            <textarea name="isi" class="form-control" rows="6" required><?= esc($isi) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= esc($tanggal) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Upload Foto (opsional)</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin ganti foto.</small>
                            <?php if (!empty($foto)) : ?>
                            <div style="margin-top:10px;">
                                <img src="<?= site_url('tampilberita/' . rawurlencode($foto)) ?>" alt="Foto berita"
                                    style="max-width:260px;height:auto;border:1px solid #ddd;padding:4px;">
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Upload PDF (opsional)</label>
                            <input type="file" name="pdf" class="form-control" accept="application/pdf">
                            <small class="text-muted">Kosongkan jika tidak ingin ganti PDF.</small>
                        </div>

                        <?php if (!empty($pdf)) : ?>
                        <div class="pdf-preview">
                            <label>Dokumen PDF Saat Ini</label>
                            <iframe src="<?= site_url('berita/pdf/' . rawurlencode($pdf)) ?>" width="100%"
                                height="520"></iframe>
                        </div>
                        <?php endif; ?>
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
