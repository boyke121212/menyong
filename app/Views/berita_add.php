<?php
$judul = old('judul', '');
$isi = old('isi', '');
$tanggalRaw = old('tanggal', date('Y-m-d'));
$timestamp = strtotime($tanggalRaw);
$tanggal = $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Tambah Berita</h3>
                </div>

                <form action="<?= site_url('berita/save') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

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
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label>Upload PDF (opsional)</label>
                            <input type="file" name="pdf" class="form-control" accept="application/pdf">
                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= site_url('berita') ?>" class="btn btn-default">Kembali</a>
                    </div>
                </form>
            </div>

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
</script>
