<!DOCTYPE html>
<html>

<head>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

    <h2>Laporan Absensi</h2>

    <div style="margin-bottom:10px">

        Subdit:
        <select id="subdit">
            <option value="">Semua</option>
            <?php foreach ($subditList as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
        </select>

        Dari:
        <input type="date" id="dari">

        Sampai:
        <input type="date" id="sampai">

        Search:
        <input type="text" id="customSearch" placeholder="Nama / NIP / dll">

        Tipe:
        <select id="keterangan">
            <option value="">Semua</option>
            <option>HADIR</option>
            <option>TERLAMBAT</option>
            <option>TK</option>
            <option>LD</option>
            <option>CUTI</option>
            <option>DIK</option>
            <option>BKO</option>
            <option>DINAS</option>
            <option>SAKIT</option>
            <option>IZIN</option>
        </select>

        <button id="btnFilter">Apply Filter</button>
        <button id="btnExport">Export Excel</button>

    </div>


    <table id="tabelAbsensi" class="display" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIP</th>
                <th>Subdit</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Jabatan</th>
                <th>Pangkat</th>
                <th>Tipe Izin</th>
                <th>Nama Pimpinan</th>
                <th>Pangkat Pimpinan</th>
                <th>Jabatan Pimpinan</th>
            </tr>
        </thead>
    </table>
    <canvas id="chartAbsensi" height="120" style="margin-top: 30px;"></canvas>

    <!-- FORM EXPORT POST -->
    <form id="formExport" method="post" action="<?= base_url('absensi/export') ?>" target="_blank">
        <?= csrf_field() ?>
        <input type="hidden" name="subdit" id="exp_subdit">
        <input type="hidden" name="dari" id="exp_dari">
        <input type="hidden" name="sampai" id="exp_sampai">
        <input type="hidden" name="keterangan" id="exp_keterangan">
    </form>


    <script>
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';
    var table = $('#tabelAbsensi').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ordering: false, // <-- semua kolom
        ajax: {
            url: "<?= base_url('absensi/ajaxList') ?>",
            type: "POST",
            data: function(d) {
                d.subdit = $('#subdit').val();
                d.dari = $('#dari').val();
                d.sampai = $('#sampai').val();
                d.keterangan = $('#keterangan').val();
            }
        },
        columns: [{
                data: "no"
            },
            {
                data: "nama"
            },
            {
                data: "nip"
            },
            {
                data: "subdit"
            },
            {
                data: "tanggal"
            },
            {
                data: "keterangan"
            },
            {
                data: "masuk"
            },
            {
                data: "pulang"
            },
            {
                data: "jabatan"
            },
            {
                data: "pangkat"
            },
            {
                data: "tipeizin",
                render: tampilanizin
            },
            {
                data: "namapimpinan",
                render: tampilPimpinan
            },
            {
                data: "pangkatpimpinan",
                render: tampilPimpinan
            },
            {
                data: "jabatanpimpinan",
                render: tampilPimpinan
            }

        ]
    });

    function tampilPimpinan(data) {
        if (data && data.trim() !== "") {
            return data;
        }
        return '<span style="color:#ffc107; font-weight:600;">-Khusus Izin pimpinan-</span>';
    }

    function tampilanizin(data) {
        if (data && data.trim() !== "") {
            return data;
        }
        return '<span style="color:#820d04; font-weight:600;">-Khusus Absen Izin-</span>';
    }
    loadGrafik();

    // pindahkan search datatable ke custom input
    $('#customSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // FILTER hanya reload kalau user klik
    $("#btnFilter").click(function() {
        table.ajax.reload(null, true);
        loadGrafik();
    });

    // EXPORT POST + CSRF
    $("#btnExport").click(function() {

        $('#exp_subdit').val($('#subdit').val());
        $('#exp_dari').val($('#dari').val());
        $('#exp_sampai').val($('#sampai').val());
        $('#exp_keterangan').val($('#keterangan').val());

        $("#formExport").submit();
    });

    var chart;

    function loadGrafik() {

        $.post("<?= base_url('absensi/grafik') ?>", {
            subdit: $('#subdit').val(),
            dari: $('#dari').val(),
            sampai: $('#sampai').val(),
            keterangan: $('#keterangan').val()
        }, function(res) {

            let labels = [];
            let values = [];

            res.forEach(function(r) {
                labels.push(r.keterangan);
                values.push(r.total);
            });

            if (chart) chart.destroy();

            chart = new Chart(document.getElementById('chartAbsensi'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Absensi',
                        data: values
                    }]
                }
            });

        });
    }
    </script>

</body>

</html>