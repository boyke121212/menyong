<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="date" id="startDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-4">
                <input type="date" id="endDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 text-end">
                <button id="btnExport" class="btn btn-sm btn-success">
                    Export to Excel
                </button>
            </div>
        </div>
    </div>

    <div class="card-body log-table-wrap">
        <table id="logoutTable" class="table table-bordered table-hover table-sm w-100">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Alasan</th>
                    <th>Oleh</th>
                    <th>Waktu</th>
                    <th>IP</th>
                    <th>Platform</th>
                    <th>Device</th>
                    <th>User Yang Dilogout</th>
                    <th>Yang Melakukan Logout</th>
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

    let table = $('#logoutTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "<?= site_url('logout_log/data') ?>",
            type: "POST",
            data: function(d) {
                d.startDate = $('#startDate').val();
                d.endDate = $('#endDate').val();
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
                data: 'userId'
            },
            {
                data: 'alasan',
                render: function(data) {
                    return '<span class="log-wrap-cell">' + escHtml(data || '-') + '</span>';
                }
            },
            {
                data: 'oleh'
            },
            {
                data: 'timestamp'
            },
            {
                data: 'machineIp'
            },
            {
                data: 'platform'
            },
            {
                data: 'userAgent',
                render: function(data) {
                    return '<span class="log-wrap-cell">' + escHtml(data || '-') + '</span>';
                }
            },
            {
                data: 'terlogout',
                render: function(data) {
                    return data ? ('<pre class="log-pre mb-0">' + escHtml(data) + '</pre>') : '-';
                }
            },
            {
                data: 'pelogout',
                render: function(data) {
                    return data ? ('<pre class="log-pre mb-0">' + escHtml(data) + '</pre>') : '-';
                }
            }
        ],
        order: [
            [4, 'desc']
        ]
    });

    // 🔎 AUTO reload saat tanggal berubah (SAMA PERSIS)
    $('#startDate, #endDate').on('change', function() {
        table.ajax.reload();
    });

    // 📤 Export
    $('#btnExport').on('click', function() {
        let s = $('#startDate').val();
        let e = $('#endDate').val();
        window.location.href =
            "<?= site_url('logout_log/export') ?>?startDate=" + s + "&endDate=" + e;
    });

});
</script>
<style>
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(0.8);
}

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
