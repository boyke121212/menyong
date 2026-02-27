<div class="container-fluid mt-4">

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center g-2">

                <!-- Range Tanggal -->
                <div class="col-md-4">
                    <input type="date" id="start_date" class="form-control form-control-sm">
                </div>

                <div class="col-md-4">
                    <input type="date" id="end_date" class="form-control form-control-sm">
                </div>

                <div class="col-md-4 text-end">
                    <button id="btnExport" class="btn btn-sm btn-success">
                        Export to Excel
                    </button>
                </div>

            </div>
        </div>

        <div class="card-body">

            <table id="sessionTable" class="table table-bordered table-hover table-sm w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Session Data</th>
                        <th>Machine IP</th>
                        <th>User Agent</th>
                        <th>Platform</th>
                        <th>Created</th>
                    </tr>
                </thead>
            </table>

        </div>
    </div>

</div>

<script>
$(document).ready(function() {

    let csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    let table = $('#sessionTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "<?= site_url('login_log/data') ?>",
            type: "POST",
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
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
                data: 'sessionData',
                render: function(data) {
                    return `<pre class="mb-0">${data}</pre>`;
                }
            },
            {
                data: 'machineIp'
            },
            {
                data: 'userAgent'
            },
            {
                data: 'platform'
            },
            {
                data: 'createdDtm'
            }
        ],
        order: [
            [6, 'desc']
        ]
    });

    // 🔎 Reload saat range berubah
    $('#start_date, #end_date').on('change', function() {
        table.ajax.reload();
    });

    // 📤 Export Excel
    $('#btnExport').on('click', function() {
        let s = $('#start_date').val();
        let e = $('#end_date').val();

        window.location.href =
            "<?= site_url('login_log/export') ?>?start=" + s + "&end=" + e;
    });

});
</script>
<style>
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(0.8);
}
</style>