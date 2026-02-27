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
$summarySubdit = $summarySubdit ?? [];
$grafikSubdit = $grafikSubdit ?? [];
$userTerakhir = $userTerakhir ?? [];

$bulanUrut = [];
if (!empty($bulanAwal)) {
    for ($i = 0; $i < 12; $i++) {
        $idx = ((($bulanAwal + $i) - 1) % 12) + 1;
        $bulanUrut[$idx] = $bulanList[$idx];
    }
}
?>

<div class="dashboard-wrap">
    <div class="hero">
        <img src="<?= base_url('lukisan/logodit.webp') ?>" alt="Bareskrim Polri" class="center-logo" width="220"
            height="220" fetchpriority="high" decoding="async">
        <div class="center-text">
            <p>BARESKRIM POLRI</p>
            <p>Dittipidter Online Attendance System</p>
            <p>D.O.A.S</p>
        </div>
    </div>

    <?php if (!empty($tahunAktif) && !empty($bulanAwal)): ?>
    <?php
        $bulanAkhir = (($bulanAwal + 10) % 12) + 1;
        $tahunAkhir = $tahunAktif + 1;
        ?>
    <div class="anggaran-box">
        <h3>Ringkasan Anggaran Tahun Berjalan</h3>
        <p>
            Tahun Anggaran: <b><?= esc($tahunAktif) ?></b><br>
            Periode: <b><?= $bulanList[$bulanAwal] ?> <?= esc($tahunAktif) ?> - <?= $bulanList[$bulanAkhir] ?>
                <?= esc($tahunAkhir) ?></b>
        </p>

        <div class="card-summary">
            <?php foreach ($subditList as $subdit):
                    $row = $summarySubdit[$subdit] ?? ['diajukan' => 0, 'terserap' => 0, 'persen' => 0];
                ?>
            <div class="card">
                <h4><?= esc($subdit) ?></h4>
                Total Diajukan<br>
                <b>Rp <?= number_format($row['diajukan'], 0, ',', '.') ?></b><br><br>
                Total Terserap<br>
                <b>Rp <?= number_format($row['terserap'], 0, ',', '.') ?></b><br><br>
                Persentase Serapan<br>
                <b><?= esc($row['persen']) ?> %</b>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="table-scroll">
            <table border="1" cellpadding="5" class="anggaran-table">
                <tr>
                    <th>Keterangan</th>
                    <?php foreach ($subditList as $subdit): ?>
                    <th style="background:<?= $warnaSubdit[$subdit] ?>;color:white;"><?= esc($subdit) ?></th>
                    <?php endforeach; ?>
                </tr>

                <tr>
                    <td><b>Pengajuan</b></td>
                    <?php foreach ($subditList as $subdit):
                            $rowSummary = $summarySubdit[$subdit] ?? ['diajukan' => 0];
                        ?>
                    <td>Rp <?= number_format((int) $rowSummary['diajukan'], 0, ',', '.') ?></td>
                    <?php endforeach; ?>
                </tr>

                <?php foreach ($bulanUrut as $bulanAngka => $bulanNama): ?>
                <tr>
                    <td><b><?= 'Serapan ' . $bulanNama ?></b></td>
                    <?php foreach ($subditList as $subdit):
                                $terserapVal = $dataMap[$subdit][$bulanAngka]['anggaran_terserap'] ?? 0;
                            ?>
                    <td>Rp <?= number_format((int) $terserapVal, 0, ',', '.') ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h4>Grafik Persentase Serapan per Subdit (%)</h4>
        <canvas id="grafik"></canvas>
    </div>
    <?php else: ?>
    <div class="anggaran-box">
        <h3>Ringkasan Anggaran Tahun Berjalan</h3>
        <p>Data tahun anggaran yang berlaku belum tersedia.</p>
    </div>
    <?php endif; ?>

    <div class="anggaran-box user-box">
        <h3>5 User Terakhir Ditambahkan</h3>
        <?php if (!empty($userTerakhir)): ?>
        <div class="table-scroll">
            <table border="1" cellpadding="5" class="anggaran-table">
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Subdit</th>
                    <th>Dibuat</th>
                </tr>
                <?php foreach ($userTerakhir as $u): ?>
                <tr>
                    <td><?= esc($u['userId'] ?? '-') ?></td>
                    <td><?= esc($u['username'] ?? '-') ?></td>
                    <td><?= esc($u['name'] ?? '-') ?></td>
                    <td><?= esc($u['roleId'] ?? '-') ?></td>
                    <td><?= esc($u['subdit'] ?? '-') ?></td>
                    <td><?= esc($u['createdDtm'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <p>Data user belum tersedia.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.dashboard-wrap {
    max-width: 96%;
    margin: 20px auto;
}

.hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 26px;
}

.center-logo {
    max-width: 220px;
    width: 100%;
    height: auto;
    margin-bottom: 14px;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .15));
}

.center-text {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #0b2545;
}

.anggaran-box {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.user-box {
    margin-top: 18px;
}

.card-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    border: 1px solid #ddd;
    padding: 14px;
    border-radius: 6px;
    background: #fafafa;
}

.table-scroll {
    overflow-x: auto;
    width: 100%;
    -webkit-overflow-scrolling: touch;
    margin-top: 10px;
}

.anggaran-table td {
    min-width: 150px;
}

@media (max-width: 1024px) {
    .card-summary {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .card-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if (!empty($tahunAktif) && !empty($bulanAwal)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dataGrafik = <?= json_encode($grafikSubdit) ?>;
const warnaSubdit = <?= json_encode($warnaSubdit) ?>;
const bulanUrut = <?= json_encode($bulanUrut) ?>;
const labels = <?= json_encode(array_values($bulanUrut)) ?>;
const datasets = [];

for (const subdit in dataGrafik) {
    let dataBulan = [];
    for (const bulanAngka in bulanUrut) {
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
<?php endif; ?>
