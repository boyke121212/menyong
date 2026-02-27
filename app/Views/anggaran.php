<style>
.wrapper {
    max-width: 600px;
    margin: 40px auto;
}

.card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.card h3 {
    margin-top: 0;
}

.form-row {
    margin-bottom: 15px;
}

button {
    padding: 8px 14px;
    border-radius: 5px;
    border: none;
    background: #2b6cb0;
    color: white;
    cursor: pointer;
}

button:hover {
    background: #1e4e8c;
}

.btn-danger {
    background: #c53030;
}

.btn-danger:hover {
    background: #9b2c2c;
}

.btn-secondary {
    background: #4a5568;
}

.btn-secondary:hover {
    background: #2d3748;
}

.info-table td {
    padding: 6px 10px;
}
</style>

<div class="wrapper">
    <div class="card">

        <h3>Tahun Anggaran</h3>

        <form method="get" class="form-row">
            <input type="number" name="tahun_anggaran" value="<?= $tahun ?>">
            <button type="submit">Cek</button>
        </form>

        <hr>

        <?php
        $bulanList = [
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "Mei",
            6 => "Jun",
            7 => "Jul",
            8 => "Agu",
            9 => "Sep",
            10 => "Okt",
            11 => "Nov",
            12 => "Des"
        ];
        ?>

        <?php if (!$dataAda): ?>

        <form method="post" action="<?= base_url('anggaran/buat') ?>">
            <?= csrf_field() ?>

            <input type="hidden" name="tahun_anggaran" value="<?= $tahun ?>">

            <div class="form-row">
                <label>Bulan Awal:</label><br>
                <select name="bulan_awal">
                    <?php foreach ($bulanList as $k => $v): ?>
                    <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Buat Tahun Anggaran</button>
        </form>

        <?php else: ?>

        <?php
            $bulanAkhir = (($bulanAwal + 10) % 12) + 1;
            $tahunAkhir = $tahun + 1;
            ?>

        <h4>Informasi Tahun Anggaran</h4>

        <table class="info-table">
            <tr>
                <td>Tahun</td>
                <td><b><?= $tahun ?></b></td>
            </tr>
            <tr>
                <td>Bulan Mulai</td>
                <td><b><?= $bulanList[$bulanAwal] ?></b></td>
            </tr>
            <tr>
                <td>Periode</td>
                <td>
                    <b><?= $bulanList[$bulanAwal] ?> <?= $tahun ?> - <?= $bulanList[$bulanAkhir] ?>
                        <?= $tahunAkhir ?></b>
                </td>
            </tr>
        </table>

        <br>
        <div style="display:flex; gap:10px; margin-top:15px;">

            <form method="get" action="<?= base_url('anggaran/detail') ?>">
                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                <button type="submit" class="btn-secondary">Atur Anggaran</button>
            </form>

            <form method="post" action="<?= base_url('anggaran/delete') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="tahun_anggaran" value="<?= $tahun ?>">
                <button type="submit" class="btn-danger" onclick="return confirm('Hapus tahun anggaran ini?')">
                    Hapus Tahun
                </button>
            </form>

        </div>



        <?php endif; ?>

    </div>
</div>