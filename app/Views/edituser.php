<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit User</h3>
                </div>

                <form action="<?= site_url('updateuser') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- USER ID -->
                    <input type="hidden" name="userId" value="<?= esc($datauser['userId']) ?>">

                    <div class="box-body">

                        <!-- Username (readonly) -->


                        <!-- Nama -->
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= esc($datauser['name']) ?>"
                                required>
                        </div>

                        <!-- NIP -->
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?= esc($datauser['nip']) ?>"
                                readonly>
                        </div>

                        <!-- Pangkat -->
                        <div class="form-group">
                            <label>Pangkat</label>
                            <input type="text" name="pangkat" class="form-control"
                                value="<?= esc($datauser['pangkat']) ?>" required>
                        </div>

                        <!-- Jabatan -->
                        <div class="form-group">
                            <label>Jabatan</label>
                            <input type="text" name="jabatan" class="form-control"
                                value="<?= esc($datauser['jabatan']) ?>" required>
                        </div>

                        <!-- Subdit -->
                        <div class="form-group">
                            <label>Subdit</label>
                            <select name="subdit" class="form-control" required>
                                <option value="">-- Pilih Subdit --</option>

                                <option value="Staff Pimpinan"
                                    <?= ($datauser['subdit'] === 'Staff Pimpinan') ? 'selected' : '' ?>>
                                    Staff Pimpinan
                                </option>

                                <option value="Subdit 1" <?= ($datauser['subdit'] === 'Subdit 1') ? 'selected' : '' ?>>
                                    Subdit 1
                                </option>

                                <option value="Subdit 2" <?= ($datauser['subdit'] === 'Subdit 2') ? 'selected' : '' ?>>
                                    Subdit 2
                                </option>

                                <option value="Subdit 3" <?= ($datauser['subdit'] === 'Subdit 3') ? 'selected' : '' ?>>
                                    Subdit 3
                                </option>

                                <option value="Subdit 4" <?= ($datauser['subdit'] === 'Subdit 4') ? 'selected' : '' ?>>
                                    Subdit 4
                                </option>

                                <option value="Subdit 5" <?= ($datauser['subdit'] === 'Subdit 5') ? 'selected' : '' ?>>
                                    Subdit 5
                                </option>
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
<?php if (session()->getFlashdata('flasherror')): ?>
<div class="alert alert-danger">
    <?= session()->getFlashdata('flasherror') ?>
</div>
<?php endif; ?>

<script>
function previewFoto(input) {
    const preview = document.getElementById('fotoPreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
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