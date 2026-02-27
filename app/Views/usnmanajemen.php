<div style="padding:20px;">

    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h3>Data User</h3>
        <div class="header-user">



            <div class="header-actions">
                <?php if (in_array((int) ($role ?? 0), [1, 2, 3], true)): ?>
                <button class="btn-add" onclick="tambahuser()">
                    + Tambah User
                </button>
                <?php endif; ?>

                <button type="button" class="btn-import" onclick="pilihFileImport()">
                    Import Excel
                </button>
            </div>

        </div>

    </div>

    <table id="userTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="checkPage">
                </th>
                <th>No</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>NIP</th>
                <th>Subdit</th>
                <th>Pangkat</th>
                <th>Status</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
    <div class="bulk-right">
        <button id="btnHapusTerpilih" class="btn-hapus dataTables_info" onclick="hapusTerpilih()" style="display:none;">
            Hapus Terpilih
        </button>
    </div>


    <form id="hapusForm" action="<?= site_url('hapususer') ?>" method="post" style="display:none;">
        <?= csrf_field() ?>
        <input type="hidden" name="userId" id="hapusUserId">
    </form>
    <form id="logoutForm" action="<?= site_url('logoutuser') ?>" method="post" style="display:none;">
        <?= csrf_field() ?>
        <input type="hidden" name="userId" id="logoutId">
        <input type="hidden" name="alasan" id="alasan">
    </form>
    <form id="hapusBanyakForm" action="<?= site_url('hapususerbanyak') ?>" method="post" style="display:none;">

        <?= csrf_field() ?>

        <!-- daftar userId yang dicentang (halaman ini saja) -->
        <input type="hidden" name="userIds" id="hapusUserIds">

    </form>

    <form id="importExcelForm" action="<?= site_url('usn/import-excel') ?>" method="post"
        enctype="multipart/form-data" style="display:none;">
        <?= csrf_field() ?>
        <input type="file" name="excel_file" id="excelFileInput" accept=".xlsx,.xls,.csv">
    </form>

</div>

<!-- MODAL -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">Alasan Logout</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="logoutUserId">

                <div class="form-group">
                    <label>Alasan</label>
                    <textarea id="logoutReason" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" onclick="dismis();">
                    Batal
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmLogout()">
                    Logout
                </button>
            </div>

        </div>
    </div>
</div>


<script>
function openLogoutModal(userId) {
    document.getElementById('logoutUserId').value = userId;
    document.getElementById('logoutReason').value = '';

    $('#logoutModal').modal('show');
}

function confirmLogout() {
    const userId = document.getElementById('logoutUserId').value;
    const reason = document.getElementById('logoutReason').value.trim();

    if (reason === '') {
        alert('Alasan wajib diisi');
        return;
    }

    $('#logoutModal').modal('hide');

    logout(userId, reason);
}
</script>

<?php if (session()->getFlashdata('flashsuccess')) : ?>
<div id="toast-success" class="toast toast-success">
    <?= esc(session()->getFlashdata('flashsuccess')) ?>
</div>
<?php endif; ?>
<script>
$(function() {
    const viewerRole = parseInt("<?= (int)($role ?? 0) ?>", 10);

    function canEditUser(viewerRole, targetRole) {
        if (viewerRole === 1) return true;
        if (viewerRole === 2) return targetRole === 3;
        return false;
    }

    function canDeleteUser(viewerRole, targetRole) {
        if (viewerRole === 1) return true;
        if (viewerRole === 2) return targetRole === 3;
        if (viewerRole === 3) return targetRole === 8;
        return false;
    }

    $('#userTable').DataTable({
        responsive: true,
        scrollX: true,
        processing: true,
        serverSide: true,
        dom: '<"dt-top"l f>rtip',
        ajax: {
            url: "<?= base_url('ajax/user/datatables') ?>",
            type: "POST",
            error: function(xhr) {

                // 401 / 403 → paksa logout
                if (xhr.status === 401 || xhr.status === 403) {
                    window.location.href = "<?= base_url('logout') ?>";
                }
            }
        },
        order: [
            [9, 'asc']
        ], // 👈 kolom roleId (index ke-10)
        columns: [{
                data: "userId",
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const targetRole = parseInt(row.roleId, 10);
                    const canDelete = canDeleteUser(viewerRole, targetRole);

                    return `<input type="checkbox"
                       class="row-check"
                       value="${data}"
                       data-role="${targetRole}"
                       ${canDelete ? '' : 'disabled'}
                       title="${canDelete ? 'Pilih untuk hapus' : 'Anda tidak punya akses hapus user ini'}">`;
                }
            }, {
                data: "no",
                orderable: true
            },
            {
                data: "name"
            },
            {
                data: "jabatan"
            },
            {
                data: "nip"
            },
            {
                data: "subdit"
            },
            {
                data: "pangkat"
            },
            {
                data: "status"
            },
            {
                data: "roleId",
                render: function(data) {
                    const role = parseInt(data, 10);
                    if (role == 1) return "Super Admin";
                    if (role == 2) return "Admin Utama";
                    if (role == 3) return "Admin User";
                    if (role == 4) return "Admin Berita";
                    if (role == 5) return "Admin Anggaran";
                    if (role == 6) return "Admin Kantor";
                    if (role == 7) return "Admin Laporan";
                    if (role == 8) return "User";
                    return "Unknown";
                }
            },
            {
                data: "userId", // ambil ID dari server
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const targetRole = parseInt(row.roleId, 10);
                    const canEdit = canEditUser(viewerRole, targetRole);
                    const canDelete = canDeleteUser(viewerRole, targetRole);

                    const editButton = canEdit ? `
                        <form method="get" action="<?= site_url('user/edit') ?>" style="display:inline;">
                            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                            <input type="hidden" name="userId" value="${data}">
                            <button type="submit" class="btn-edit">Edit</button>
                        </form>
                    ` : '';
                    const deleteButton = canDelete ? `
                        <button class="btn-hapus" onclick="hapusUser(${data})">
                            Hapus
                        </button>
                    ` : '';

                    return `
                    <div class="aksi-btn">
                        ${editButton}
                        ${deleteButton}
                        <button class="btn-warning" onclick="openLogoutModal(${data})">
                            Logout
                        </button>
                    </div>
                `;
                }
            }
        ]
    });


});
</script>

<style>
.header-user {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    gap: 10px;
}

.header-actions {
    display: flex;
    gap: 8px;
}

/* tombol */
.btn-add {
    padding: 7px 15px;
    background: #28a745;
    color: #fff;
    border: none;
}

.btn-import {
    padding: 7px 15px;
    background: #007bff;
    color: #fff;
    border: none;
}

/* =========================
   MOBILE MODE
   ========================= */
@media (max-width: 768px) {

    .header-user {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .header-actions button {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .header-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .header-actions button {
        width: 100%;
    }
}

/* container atas datatables */
.dt-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

/* rapikan di mobile */
@media (max-width: 768px) {
    .dt-top {
        flex-wrap: nowrap;
    }

    .dataTables_length,
    .dataTables_filter {
        margin: 0;
    }

    .dataTables_filter input {
        width: 120px;
        /* biar gak kepanjangan */
    }
}

/* container tombol aksi */
.aksi-btn {
    display: flex;
    gap: 6px;
}

/* samakan lebar tombol */
.aksi-btn button {
    min-width: 70px;
    /* ← lebar sama */
    padding: 5px 0;
    text-align: center;
    border: none;
    cursor: pointer;
}

/* warna */
.btn-edit {
    background: #ffc107;
    color: #000;
}

.btn-hapus {
    background: #dc3545;
    color: #fff;
}

/* mobile: tombol full lebar & rapi */
@media (max-width: 768px) {
    .aksi-btn {
        flex-direction: column;
    }

    .aksi-btn button {
        width: 100%;
    }
}

/* ===============================
   SCROLLBAR KHUSUS DATATABLES
   =============================== */

/* Chrome, Edge, Safari */
.dataTables_scrollBody::-webkit-scrollbar {
    height: 14px;
    /* horizontal scrollbar */
    width: 14px;
    /* vertical scrollbar */
}

.dataTables_scrollBody::-webkit-scrollbar-track {
    background: #e0e0e0;
    border-radius: 10px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Firefox */
.dataTables_scrollBody {
    scrollbar-width: auto;
    /* thin | auto */
    scrollbar-color: #888 #e0e0;
}

@media (max-width: 768px) {
    .dataTables_scrollBody::-webkit-scrollbar {
        height: 18px;
        /* lebih tebal untuk jempol */
    }
}

.dataTables_scrollBody::-webkit-scrollbar-thumb {
    background: #666;
}

.bulk-right {
    text-align: left;
}
</style>


<script>
function dismis() {
    $('#logoutModal').modal('hide');
}

function logout(id, reason) {
    console.log('Logout ID:', id);
    console.log('Alasan:', reason);
    document.getElementById('logoutId').value = id;
    document.getElementById('alasan').value = reason;
    document.getElementById('logoutForm').submit();
}

function tambahuser() {
    window.location.href = "<?= site_url('tambahuser') ?>"
}

function pilihFileImport() {
    document.getElementById('excelFileInput').click();
}

document.getElementById('excelFileInput').addEventListener('change', function() {
    if (!this.files || !this.files.length) {
        return;
    }
    document.getElementById('importExcelForm').submit();
});

function hapusUser(id) {
    if (confirm('Yakin hapus user ini?')) {
        document.getElementById('hapusUserId').value = id;
        document.getElementById('hapusForm').submit();
    }
}
</script>

<style>
/* =========================
   TOAST
   ========================= */
.toast {
    position: fixed;
    bottom: -100px;
    left: 50%;
    transform: translateX(-50%);
    min-width: 280px;
    max-width: 90%;
    padding: 14px 20px;
    border-radius: 6px;
    color: #fff;
    font-size: 14px;
    text-align: center;
    z-index: 9999;
    opacity: 0;
    transition: all 0.4s ease;
}

/* sukses */
.toast-success {
    background: #28a745;
}

/* tampil */
.toast.show {
    bottom: 30px;
    opacity: 1;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toast = document.getElementById('toast-success');

    if (toast) {
        // tampil
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // hilang otomatis
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }
});
</script>
<script>
const viewerRoleForBulk = parseInt("<?= (int)($role ?? 0) ?>", 10);

function canDeleteByRole(viewerRole, targetRole) {
    if (viewerRole === 1) return true;
    if (viewerRole === 2) return targetRole === 3;
    if (viewerRole === 3) return targetRole === 8;
    return false;
}

$('#checkPage').on('change', function() {
    $('.row-check:not(:disabled)').prop('checked', this.checked);
});

function hapusTerpilih() {
    const ids = [];

    $('.row-check:checked').each(function() {

        // kalau checkbox disabled → SKIP
        if (this.disabled) return;

        // OPTIONAL: extra safety, cek data-role
        const role = parseInt($(this).data('role'), 10);

        if (!canDeleteByRole(viewerRoleForBulk, role)) return;

        ids.push(this.value);
    });


    if (ids.length === 0) {
        alert('Pilih data di halaman ini sesuai akses role Anda');
        return;
    }

    if (!confirm(`Hapus ${ids.length} data di halaman ini?`)) return;

    $('#hapusUserIds').val(ids.join(','));
    $('#hapusBanyakForm').submit();
}
</script>
<script>
function updateBulkButton() {
    const checkedCount = $('.row-check:checked').length;

    if (checkedCount > 0) {
        $('#btnHapusTerpilih').show();
    } else {
        $('#btnHapusTerpilih').hide();
    }
}

// check all halaman ini
$('#checkPage').on('change', function() {
    $('.row-check:not(:disabled)').prop('checked', this.checked);
    updateBulkButton();
});

// checkbox per row
$(document).on('change', '.row-check', function() {
    updateBulkButton();
});

// reset saat ganti halaman / search / sort
$('#userTable').on('draw.dt', function() {
    $('#checkPage').prop('checked', false);
    updateBulkButton();
});
</script>
