<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="row g-2 align-items-center mb-2">
            <div class="col-md-3">
                <input type="date" id="startDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <input type="date" id="endDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <select id="actionFilter" class="form-control form-control-sm">
                    <option value="">Semua Aksi</option>
                    <option value="ADD_TAHUN_ANGGARAN">ADD_TAHUN_ANGGARAN</option>
                    <option value="DELETE_TAHUN_ANGGARAN">DELETE_TAHUN_ANGGARAN</option>
                    <option value="LOG_DETAIL_ANGGARAN">LOG_DETAIL_ANGGARAN</option>
                    <option value="RESET_DETAIL_ANGGARAN">RESET_DETAIL_ANGGARAN</option>
                </select>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-12 d-flex gap-2 justify-content-end">
                <button id="btnApply" class="btn btn-sm btn-primary">Apply Filter</button>
                <button id="btnReset" class="btn btn-sm btn-secondary">Reset</button>
                <button id="btnExport" class="btn btn-sm btn-success">Export Excel</button>
            </div>
        </div>
    </div>

    <div class="card-body log-table-wrap">
        <table id="logAnggaranTable" class="table table-bordered table-hover table-sm w-100">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Aksi</th>
                    <th>Tahun</th>
                    <th>Pelaku</th>
                    <th>Deskripsi</th>
                    <th>Payload</th>
                    <th>IP</th>
                    <th>User Agent</th>
                    <th>Waktu</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    const escHtml = (v) => $('<div>').text(v ?? '').html();

    let csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    let table = $('#logAnggaranTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "<?= site_url('log_anggaran/data') ?>",
            type: "POST",
            data: function(d) {
                d.startDate = $('#startDate').val();
                d.endDate = $('#endDate').val();
                d.action = $('#actionFilter').val();
                d[csrfName] = csrfHash;
            },
            dataSrc: function(json) {
                csrfHash = json.csrfHash;
                return json.data;
            }
        },
        columns: [{
                data: 'id'
            },
            {
                data: 'action'
            },
            {
                data: 'tahun'
            },
            {
                data: null,
                render: function(data) {
                    return (data.actorUserId || '-') + ' / ' + (data.actorUsername || '-') + ' / ' + (data.actorName || '-');
                }
            },
            {
                data: 'description'
            },
            {
                data: 'payload',
                render: function(data) {
                    return data ? ('<pre class="log-pre mb-0">' + escHtml(data) + '</pre>') : '-';
                }
            },
            {
                data: 'ipAddress'
            },
            {
                data: 'userAgent',
                render: function(data) {
                    return '<span class="log-wrap-cell">' + escHtml(data || '-') + '</span>';
                }
            },
            {
                data: 'createdAt'
            }
        ],
        order: [
            [8, 'desc']
        ]
    });

    $('#btnApply').on('click', function() {
        table.ajax.reload();
    });

    $('#btnReset').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');
        $('#actionFilter').val('');
        table.ajax.reload();
    });

    $('#btnExport').on('click', function() {
        const s = $('#startDate').val();
        const e = $('#endDate').val();
        const a = $('#actionFilter').val();
        window.location.href = "<?= site_url('log_anggaran/export') ?>?startDate=" + encodeURIComponent(s) +
            "&endDate=" + encodeURIComponent(e) +
            "&action=" + encodeURIComponent(a);
    });
});
</script>
<style>
.log-table-wrap {
    overflow-x: auto;
}

.log-table-wrap table {
    width: 100% !important;
}

.log-table-wrap th,
.log-table-wrap td {
    white-space: normal !important;
    word-break: break-word;
    vertical-align: top;
}

.log-pre {
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    margin: 0;
    max-width: 460px;
}

.log-wrap-cell {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
}
</style>
