<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body {
        background: #f6f7fb;
    }

    .toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .toolbar input[type="text"] {
        min-width: 280px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
    }

    .btn {
        border: 0;
        border-radius: 8px;
        padding: 9px 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-primary {
        background: #1d4ed8;
        color: #fff;
    }

    .btn-ghost {
        background: #e5e7eb;
        color: #111827;
    }

    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px;
    }

    #tabelAbsensi {
        width: 100%;
    }

    #tabelAbsensi th,
    #tabelAbsensi td {
        white-space: nowrap;
    }

    div.dataTables_wrapper {
        width: 100%;
    }

    .dataTables_scrollBody {
        overflow-x: auto !important;
    }

    .dataTables_scrollHeadInner,
    .dataTables_scrollHeadInner table,
    .dataTables_scrollBody table {
        width: max-content !important;
        min-width: 100% !important;
    }

    .sheet-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.35);
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s ease;
        z-index: 20000;
    }

    .sheet-backdrop.show {
        opacity: 1;
        pointer-events: auto;
    }

    .bottom-sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        max-height: 85vh;
        overflow: auto;
        background: #fff;
        border-radius: 16px 16px 0 0;
        box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(102%);
        transition: transform .25s ease;
        z-index: 20001;
        padding: 16px;
    }

    .bottom-sheet.show {
        transform: translateY(0);
    }

    .sheet-handle {
        width: 48px;
        height: 5px;
        border-radius: 999px;
        background: #d1d5db;
        margin: 2px auto 14px;
    }

    .sheet-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .sheet-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 13px;
    }

    .sheet-field input,
    .sheet-field select {
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        padding: 8px 10px;
    }

    #fieldSelector {
        margin-top: 8px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 6px;
    }

    #fieldSelector label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
    }
    </style>
</head>

<body>
    <h2>Laporan Absensi</h2>

    <div class="toolbar">
        <input type="text" id="customSearch" placeholder="Cari semua field">
        <button id="btnOpenFilter" class="btn btn-ghost" type="button">Filter</button>
        <button id="btnExport" class="btn btn-primary" type="button">Export Excel</button>
    </div>

    <div class="table-card">
        <table id="tabelAbsensi" class="display" width="100%">
            <thead></thead>
        </table>
    </div>

    <canvas id="chartAbsensi" height="120" style="margin-top: 30px;"></canvas>

    <div id="sheetBackdrop" class="sheet-backdrop"></div>
    <div id="bottomSheet" class="bottom-sheet">
        <div class="sheet-handle"></div>
        <div class="sheet-grid">
            <label class="sheet-field">
                Subdit
                <select id="subdit">
                    <option value="">Semua</option>
                    <?php foreach ($subditList as $s): ?>
                    <option value="<?= esc($s) ?>"><?= esc($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="sheet-field">
                Dari
                <input type="date" id="dari">
            </label>

            <label class="sheet-field">
                Sampai
                <input type="date" id="sampai">
            </label>

            <label class="sheet-field">
                Keterangan
                <select id="keterangan">
                    <option value="">Semua</option>
                    <?php foreach ($keteranganList as $t): ?>
                    <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div style="margin-bottom:8px;"><b>Field Ditampilkan</b></div>
        <div id="fieldSelector">
            <?php foreach ($fieldLabels as $field => $label): ?>
            <label>
                <input type="checkbox" class="chk-field" value="<?= esc($field) ?>" checked>
                <?= esc($label) ?>
            </label>
            <?php endforeach; ?>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;">
            <button id="btnCloseFilter" class="btn btn-ghost" type="button">Tutup</button>
            <button id="btnApplyFilter" class="btn btn-primary" type="button">Apply Filter</button>
        </div>
    </div>

    <form id="formExport" method="post" action="<?= base_url('absensi/export') ?>" target="_blank">
        <?= csrf_field() ?>
        <input type="hidden" name="subdit" id="exp_subdit">
        <input type="hidden" name="dari" id="exp_dari">
        <input type="hidden" name="sampai" id="exp_sampai">
        <input type="hidden" name="keterangan" id="exp_keterangan">
        <div id="expFields"></div>
    </form>

    <script>
    const fieldLabels = <?= json_encode($fieldLabels) ?>;
    const defaultFields = <?= json_encode($defaultFields) ?>;
    let table = null;
    let chart = null;

    function getSelectedFields() {
        const selected = [];
        $('.chk-field:checked').each(function() {
            selected.push($(this).val());
        });
        return selected.length ? selected : [...defaultFields];
    }

    function buildColumns(selectedFields) {
        const columns = [{
            data: 'no',
            title: 'No'
        }];

        selectedFields.forEach(function(field) {
            columns.push({
                data: field,
                title: fieldLabels[field] || field
            });
        });

        return columns;
    }

    function initTable() {
        const selectedFields = getSelectedFields();

        if (table) {
            table.destroy();
            $('#tabelAbsensi').empty();
            $('#tabelAbsensi').append('<thead></thead>');
        }

        table = $('#tabelAbsensi').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ordering: false,
            scrollX: true,
            scrollCollapse: true,
            autoWidth: false,
            ajax: {
                url: "<?= base_url('absensi/ajaxList') ?>",
                type: "POST",
                data: function(d) {
                    d.subdit = $('#subdit').val();
                    d.dari = $('#dari').val();
                    d.sampai = $('#sampai').val();
                    d.keterangan = $('#keterangan').val();
                    d.selected_fields = selectedFields;
                }
            },
            columns: buildColumns(selectedFields)
        });
    }

    function loadGrafik() {
        $.post("<?= base_url('absensi/grafik') ?>", {
            subdit: $('#subdit').val(),
            dari: $('#dari').val(),
            sampai: $('#sampai').val(),
            keterangan: $('#keterangan').val()
        }, function(res) {
            const labels = [];
            const values = [];

            res.forEach(function(r) {
                labels.push(r.keterangan || '(Kosong)');
                values.push(r.total);
            });

            if (chart) chart.destroy();
            chart = new Chart(document.getElementById('chartAbsensi'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Absensi per Tipe',
                        data: values
                    }]
                }
            });
        });
    }

    function fillExportFields(selectedFields) {
        $('#expFields').empty();
        selectedFields.forEach(function(field) {
            $('#expFields').append(
                $('<input>', {
                    type: 'hidden',
                    name: 'selected_fields[]',
                    value: field
                })
            );
        });
    }

    initTable();
    loadGrafik();

    $('#customSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    function openSheet() {
        $('#sheetBackdrop').addClass('show');
        $('#bottomSheet').addClass('show');
    }

    function closeSheet() {
        $('#sheetBackdrop').removeClass('show');
        $('#bottomSheet').removeClass('show');
    }

    $('#btnOpenFilter').on('click', function() {
        openSheet();
    });

    $('#btnCloseFilter, #sheetBackdrop').on('click', function() {
        closeSheet();
    });

    $('#btnApplyFilter').on('click', function() {
        initTable();
        loadGrafik();
        closeSheet();
    });

    $(document).on('change', '.chk-field', function() {
        // tunggu apply agar konsisten dengan filter lain
    });

    $('#btnExport').on('click', function() {
        const selectedFields = getSelectedFields();
        fillExportFields(selectedFields);

        $('#exp_subdit').val($('#subdit').val());
        $('#exp_dari').val($('#dari').val());
        $('#exp_sampai').val($('#sampai').val());
        $('#exp_keterangan').val($('#keterangan').val());

        $('#formExport').submit();
    });
    </script>
</body>

</html>
