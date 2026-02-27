<?php
$row = (!empty($data)) ? $data[0] : null;

$latitude   = $row ? $row->latitude   : '0.000000';
$longitude  = $row ? $row->longitude  : '0.000000';
$jam        = $row ? $row->jam         : '07:00';
$pulang     = $row ? $row->pulang     : '17:00';
// FIELD BARU
$batas_awal = $row && !empty($row->batasmulai)
    ? $row->batasmulai
    : '04:15';

$batas_akhir = $row && !empty($row->batasakhir)
    ? $row->batasakhir
    : '13:00';
?>

<div class="container-fluid doas-page">

    <!-- HEADER -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <h2 class="page-title">Lokasi & Jam Kantor</h2>
            <p class="page-subtitle">
                Konfigurasi lokasi pusat dan jam masuk kantor
            </p>
        </div>
    </div>

    <!-- FORM -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box doas-box">

                <div class="box-header">
                    <h3 class="box-title">Pengaturan Utama</h3>
                </div>

                <form action="<?= site_url('simpankantor') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="box-body">

                        <!-- JAM MASUK -->
                        <div class="form-group">
                            <label>Jam Masuk Kantor</label>
                            <input type="time" name="jam" class="form-control" value="<?= esc($jam) ?>" required>
                            <small class="help-block">
                                Digunakan untuk menentukan status HADIR / TERLAMBAT
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Jam Pulang Kantor</label>
                            <input type="time" name="pulang" class="form-control" value="<?= esc($pulang) ?>" required>
                            <small class="help-block">
                                Digunakan untuk menentukan status OVERIME / PULANG LEBIH AWAL
                            </small>
                        </div>
                        <!-- BATAS ABSEN -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Batas Awal Absen</label>
                                    <input type="time" name="batas_awal_absen" class="form-control"
                                        value="<?= esc($batas_awal) ?>" required>
                                    <small class="help-block">
                                        Absen hanya bisa dilakukan mulai jam ini
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Batas Akhir Absen</label>
                                    <input type="time" name="batas_akhir_absen" class="form-control"
                                        value="<?= esc($batas_akhir) ?>" required>
                                    <small class="help-block">
                                        Absen tidak bisa dilakukan setelah jam ini
                                    </small>
                                </div>
                            </div>
                        </div>



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

            <!-- FLASH MESSAGE -->
            <?php if (session()->getFlashdata('flashsuccess')) : ?>
                <div class="toast toast-success show">
                    <?= esc(session()->getFlashdata('flashsuccess')) ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('flasherror')) : ?>
                <div class="toast toast-error show">
                    <?= esc(session()->getFlashdata('flasherror')) ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<style>
    .section-title {
        margin-top: 10px;
        font-size: 15px;
        font-weight: 600;
        color: #333;
    }

    .help-block {
        font-size: 12px;
        color: #777;
        margin-top: 6px;
    }
</style>