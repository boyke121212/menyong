<?php
$u = $profileUser ?? [];
$roleMap = [
    1 => 'Super Admin',
    2 => 'Admin Utama',
    3 => 'Admin User',
    4 => 'Admin Berita',
    5 => 'Admin Anggaran',
    6 => 'Admin Kantor',
    7 => 'Admin Laporan',
    8 => 'User',
];
$roleLabel = $roleMap[(int) ($u['roleId'] ?? 0)] ?? 'Unknown';
?>

<div class="container-fluid mt-3">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('profile/update') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= esc($u['username'] ?? '') ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" class="form-control" value="<?= esc($u['nip'] ?? '') ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?= esc($roleLabel) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= esc(old('name', $u['name'] ?? '')) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pangkat</label>
                            <input type="text" name="pangkat" class="form-control"
                                value="<?= esc(old('pangkat', $u['pangkat'] ?? '')) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control"
                                value="<?= esc(old('jabatan', $u['jabatan'] ?? '')) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subdit</label>
                            <select name="subdit" class="form-control" required>
                                <?php $subditValue = old('subdit', $u['subdit'] ?? ''); ?>
                                <option value="">-- Pilih Subdit --</option>
                                <option value="Staff Pimpinan" <?= $subditValue === 'Staff Pimpinan' ? 'selected' : '' ?>>Staff Pimpinan</option>
                                <option value="Subdit 1" <?= $subditValue === 'Subdit 1' ? 'selected' : '' ?>>Subdit 1</option>
                                <option value="Subdit 2" <?= $subditValue === 'Subdit 2' ? 'selected' : '' ?>>Subdit 2</option>
                                <option value="Subdit 3" <?= $subditValue === 'Subdit 3' ? 'selected' : '' ?>>Subdit 3</option>
                                <option value="Subdit 4" <?= $subditValue === 'Subdit 4' ? 'selected' : '' ?>>Subdit 4</option>
                                <option value="Subdit 5" <?= $subditValue === 'Subdit 5' ? 'selected' : '' ?>>Subdit 5</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Ubah Password</h5>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('profile/password') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-warning">Ubah Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('flashsuccess')): ?>
    <div class="alert alert-success mt-3 mb-0"><?= esc(session()->getFlashdata('flashsuccess')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('flasherror')): ?>
    <div class="alert alert-danger mt-3 mb-0"><?= esc(session()->getFlashdata('flasherror')) ?></div>
    <?php endif; ?>
</div>
