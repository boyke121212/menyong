<?php
helper('form');
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Tambah User</h3>
                </div>

                <form action="<?= site_url('simpanuser') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="box-body">

                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= old('name') ?>">
                        </div>

                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" class="form-control" required value="<?= old('nip') ?>">
                        </div>

                        <div class="form-group">
                            <label>Pangkat</label>
                            <input type="text" name="pangkat" class="form-control" required
                                value="<?= old('pangkat') ?>">
                        </div>

                        <div class="form-group">
                            <label>Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" required
                                value="<?= old('jabatan') ?>">
                        </div>

                        <div class="form-group">
                            <label>Subdit</label>
                            <select name="subdit" class="form-control" required>
                                <option value="">-- Pilih Subdit --</option>
                                <option value="Staff Pimpinan" <?= set_select('subdit', 'Staff Pimpinan') ?>>Staff
                                    Pimpinan</option>
                                <option value="Subdit 1" <?= set_select('subdit', 'Subdit 1') ?>>Subdit 1</option>
                                <option value="Subdit 2" <?= set_select('subdit', 'Subdit 2') ?>>Subdit 2</option>
                                <option value="Subdit 3" <?= set_select('subdit', 'Subdit 3') ?>>Subdit 3</option>
                                <option value="Subdit 4" <?= set_select('subdit', 'Subdit 4') ?>>Subdit 4</option>
                                <option value="Subdit 5" <?= set_select('subdit', 'Subdit 5') ?>>Subdit 5</option>
                            </select>
                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= site_url('usnmanajemen') ?>" class="btn btn-default">Kembali</a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<style>
select.form-control {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='%23666' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
    padding-right: 40px;
    cursor: pointer;
}
</style>




<script>
function previewFoto(input) {
    const preview = document.getElementById('fotoPreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<style>
select.form-control {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    background-image: url("data:image/svg+xml;utf8,<svg fill='%23666' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;

    padding-right: 40px;
    cursor: pointer;
}
</style>