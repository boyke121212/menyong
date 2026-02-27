<style>
.wrapper {
    max-width: 95%;
    margin: 20px auto;
}

table input {
    width: 95px;
    border: 1px solid #ccc;
    padding: 3px;
}

.rupiah {
    text-align: right;
    font-family: monospace;
    width: 250px;
}

.table-scroll {
    overflow-x: auto;
    width: 100%;
    -webkit-overflow-scrolling: touch;
}


.card-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 6px;
    background: #fafafa;
}
</style>

<div class="wrapper">

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

    $warnaSubdit = [
        "Subdit 1" => "#153246",
        "Subdit 2" => "#391e04",
        "Subdit 3" => "#633f07",
        "Subdit 4" => "#530922",
        "Subdit 5" => "#410c49",
        "Subdit Staff Pimpinan" => "#07020e"
    ];

    $subditList = array_keys($warnaSubdit);

    $dataMap = $dataMap ?? [];
    $grafikSubdit = $grafikSubdit ?? [];
    $summarySubdit = $summarySubdit ?? [];

    /* urutan bulan berdasarkan bulan_awal */
    $bulanUrut = [];
    for ($i = 0; $i < 12; $i++) {
        $idx = (($bulanAwal + $i - 1) % 12) + 1;
        $bulanUrut[$idx] = $bulanList[$idx];
    }

    $bulanAkhir = (($bulanAwal + 10) % 12) + 1;
    $tahunAkhir = $tahun + 1;

    ?>

    <h2>Detail Anggaran Tahun <?= $tahun ?></h2>

    <p>
        Periode:
        <b><?= $bulanList[$bulanAwal] ?> <?= $tahun ?> -
            <?= $bulanList[$bulanAkhir] ?> <?= $tahunAkhir ?></b>
    </p>

    <hr>

    <h3>Ringkasan per Subdit</h3>

    <div class="card-summary">

        <?php foreach ($subditList as $subdit):

            $row = $summarySubdit[$subdit] ?? [
                'diajukan' => 0,
                'terserap' => 0,
                'persen' => 0
            ];
        ?>

        <div class="card">
            <h4><?= $subdit ?></h4>

            Total Diajukan<br>
            <b>Rp <?= number_format($row['diajukan'], 0, ',', '.') ?></b><br><br>

            Total Terserap<br>
            <b>Rp <?= number_format($row['terserap'], 0, ',', '.') ?></b><br><br>

            Persentase Serapan<br>
            <b><?= $row['persen'] ?> %</b>

        </div>

        <?php endforeach; ?>

    </div>

    <hr>

    <h4>Input Data Serapan</h4>

    <div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:15px;">
        <?php foreach ($warnaSubdit as $nama => $warna): ?>
        <div style="display:flex;align-items:center;gap:5px;">
            <div style="width:15px;height:15px;background:<?= $warna ?>"></div>
            <?= $nama ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php
    $flashSalah = session()->getFlashdata('flashsalah');
    $validationErrors = session()->getFlashdata('validationErrors');
    ?>

    <?php if ($flashSalah): ?>
    <div style="background:#fee2e2;padding:10px;border:1px solid #ef4444;margin-bottom:10px;">

        <b><?= esc($flashSalah) ?></b>

        <?php if (!empty($validationErrors)): ?>
        <ul style="margin-top:5px;">
            <?php foreach ($validationErrors as $err): ?>
            <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

    </div>

    <?php
        // Hapus supaya footer tidak membaca lagi
        session()->remove('flashsalah');
        session()->remove('validationErrors');
        ?>

    <?php endif; ?>



    <form method="post" action="<?= base_url('anggaran/save') ?>">
        <?= csrf_field() ?>

        <input type="hidden" name="tahun_anggaran" value="<?= $tahun ?>">
        <div class="table-scroll">
            <table border="1" cellpadding="5">


                <tr>
                    <th>Keterangan</th>

                    <?php foreach ($subditList as $subdit): ?>
                    <th style="background:<?= $warnaSubdit[$subdit] ?>;color:white;">
                        <?= $subdit ?>
                    </th>
                    <?php endforeach; ?>
                </tr>


                <tr>
                    <td style="   border: 2px solid #ccc;background:white;width:150px"><b>Pengajuan</b></td>

                    <?php foreach ($subditList as $subdit):

                        $rowSummary = $summarySubdit[$subdit] ?? ['diajukan' => 0];

                        $oldPengajuan = old('pengajuan[' . $subdit . ']');
                        $pengajuanVal = $oldPengajuan !== null ? $oldPengajuan : ($rowSummary['diajukan'] ?: '');

                        $errorSubdit = $validationErrors[$subdit] ?? null;
                    ?>

                    <td style="background:<?= $warnaSubdit[$subdit] ?>;">
                        <input type="text" class="rupiah" name="pengajuan[<?= $subdit ?>]"
                            value="<?= $pengajuanVal ? number_format($pengajuanVal, 0, ',', '.') : '' ?>" style="background:white;color:<?= $errorSubdit ? 'red' : 'black' ?>;
border:<?= $errorSubdit ? '3px solid red' : '1px solid #ccc' ?>;">
                    </td>

                    <?php endforeach; ?>
                </tr>


                <?php foreach ($bulanUrut as $bulanAngka => $bulanNama): ?>

                <tr>
                    <td style="background:#fff;border: 2px solid #ccc;"><b><?= 'Serapan ' . $bulanNama ?></b></td>

                    <?php foreach ($subditList as $subdit):

                            $terserapVal = $dataMap[$subdit][$bulanAngka]['anggaran_terserap'] ?? 0;
                        ?>

                    <td style="background:<?= $warnaSubdit[$subdit] ?>;">
                        <input type="hidden" name="bulan[]" value="<?= $bulanAngka ?>">
                        <input type="hidden" name="subdit[]" value="<?= $subdit ?>">

                        <input type="text" class="rupiah" name="terserap[]"
                            value="<?= $terserapVal ? number_format($terserapVal, 0, ',', '.') : '' ?>"
                            style="background:white;color:black">
                    </td>

                    <?php endforeach; ?>
                </tr>

                <?php endforeach; ?>

            </table>
        </div>

        <br>

        <div style="display:flex;gap:10px;align-items:center;">
            <button type="submit">Simpan Perubahan</button>
    </form>


    <form method="post" action="<?= base_url('anggaran/resetDetail') ?>"
        onsubmit="return confirm('Semua data detail tahun ini akan dihapus. Lanjutkan?')">

        <?= csrf_field() ?>
        <input type="hidden" name="tahun_anggaran" value="<?= $tahun ?>">

        <button type="submit" style="background:#b91c1c;color:white;">
            Reset Detail
        </button>

    </form>
    <form method="post" action="<?= base_url('anggaran/exportDetail') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="tahun_anggaran" value="<?= $tahun ?>">
        <button type="submit" style="background:#065f46;color:white;">
            Export Excel
        </button>
    </form>
</div>


<hr>

<h3>Grafik Persentase Serapan per Subdit (%)</h3>

<canvas id="grafik"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const dataGrafik = <?= json_encode($grafikSubdit) ?>;
const warnaSubdit = <?= json_encode($warnaSubdit) ?>;
const labels = <?= json_encode(array_values($bulanUrut)) ?>;

const datasets = [];

for (const subdit in dataGrafik) {

    let dataBulan = [];

    for (const bulanAngka in <?= json_encode($bulanUrut) ?>) {
        dataBulan.push(dataGrafik[subdit][bulanAngka] ?? 0);
    }

    datasets.push({
        label: subdit,
        data: dataBulan,
        borderColor: warnaSubdit[subdit],
        backgroundColor: warnaSubdit[subdit],
        fill: false
    });
}

new Chart(document.getElementById('grafik'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: datasets
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: (v) => v + "%"
                }
            }
        }
    }
});
</script>

</div>
<script>
function formatRupiah(angka) {
    angka = angka.replace(/[^0-9]/g, '');
    if (!angka) return '';
    return new Intl.NumberFormat('id-ID').format(angka);
}

document.querySelectorAll('.rupiah').forEach(function(input) {

    // saat fokus → hilangkan titik
    input.addEventListener('focus', function() {
        this.value = this.value.replace(/\./g, '');
    });

    // saat ketik → format otomatis
    input.addEventListener('keyup', function() {
        let posisi = this.selectionStart;
        this.value = formatRupiah(this.value);
        this.setSelectionRange(this.value.length, this.value.length);
    });

    // sebelum submit → ubah jadi angka murni
    input.form.addEventListener('submit', function() {
        document.querySelectorAll('.rupiah').forEach(function(i) {
            i.value = i.value.replace(/\./g, '');
        });
    });

});
</script>