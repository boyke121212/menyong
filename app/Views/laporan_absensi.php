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

    .btn-map {
        background: #0ea5a4;
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        border: 0;
        cursor: pointer;
        font-weight: 600;
    }

    #attendanceMap {
        width: 100%;
        height: 420px;
        border-radius: 8px;
    }

    .map-info {
        margin-bottom: 10px;
        color: #dc2626;
        font-size: 13px;
    }

    .map-pin-label {
        background: #fff;
        border: 1px solid #bbb;
        border-radius: 4px;
        padding: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #222;
    }

    .map-pin-title {
        margin-bottom: 4px;
    }

    .map-pin-photo {
        width: 110px;
        height: 110px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
        display: block;
        cursor: pointer;
    }

    #mapModal .modal-dialog {
        width: 95%;
        max-width: 1400px;
    }

    #previewModal .modal-dialog {
        width: 90%;
        max-width: 1200px;
    }

    #previewImage {
        max-width: 100%;
        max-height: none;
        object-fit: contain;
        transform-origin: center center;
        transition: transform .12s ease;
    }

    .preview-toolbar {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 10px;
    }

    .preview-zoom-btn {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #111827;
        border-radius: 6px;
        min-width: 40px;
        height: 34px;
        font-weight: 700;
        cursor: pointer;
    }

    .preview-canvas {
        width: 100%;
        height: 75vh;
        overflow: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
    }

    .map-pin-empty {
        font-size: 11px;
        color: #6b7280;
    }

    .attendance-pin {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25);
    }

    .attendance-pin.masuk {
        background: #16a34a;
    }

    .attendance-pin.pulang {
        background: #dc2626;
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

    <div class="modal fade" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Lokasi Absensi</h4>
                    <button type="button" class="close" onclick="closeMapModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="mapInfo" class="map-info" style="display:none;"></div>
                    <div id="attendanceMap"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="previewTitle">Preview Foto</h4>
                    <button type="button" class="close" onclick="closePreviewModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="preview-toolbar">
                        <button type="button" class="preview-zoom-btn" onclick="zoomOutPreview()">-</button>
                        <button type="button" class="preview-zoom-btn" onclick="resetPreviewZoom()">Reset</button>
                        <button type="button" class="preview-zoom-btn" onclick="zoomInPreview()">+</button>
                    </div>
                    <div id="previewCanvas" class="preview-canvas">
                        <img id="previewImage" src="" alt="Preview">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

    <script>
    const fieldLabels = <?= json_encode($fieldLabels) ?>;
    const defaultFields = <?= json_encode($defaultFields) ?>;
    let table = null;
    let chart = null;
    let attendanceMapInstance = null;
    let attendanceMapLayer = null;
    let previewScale = 1;

    function toCoord(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function distanceMeters(lat1, lon1, lat2, lon2) {
        const toRad = (deg) => deg * Math.PI / 180;
        const R = 6371000;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function offsetByMeters(lat, lon, metersNorth, metersEast) {
        const dLat = metersNorth / 111320;
        const dLon = metersEast / (111320 * Math.cos(lat * Math.PI / 180));
        return [lat + dLat, lon + dLon];
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function photoTooltipHtml(label, filename) {
        const safeLabel = escapeHtml(label);
        const safeFile = String(filename ?? '').trim();
        if (!safeFile) {
            return `<div class="map-pin-title">${safeLabel}</div><div class="map-pin-empty">Foto tidak tersedia</div>`;
        }

        const url = "<?= base_url('tampilfoto') ?>/" + encodeURIComponent(safeFile);
        return `<div class="map-pin-title">${safeLabel}</div><img class="map-pin-photo" src="${url}" alt="${safeLabel}" onclick="openImagePreview('${url.replace(/'/g, "\\'")}','${safeLabel.replace(/'/g, "\\'")}')" onerror="this.outerHTML='<div class=&quot;map-pin-empty&quot;>Foto tidak ditemukan</div>'">`;
    }

    function closeMapModal() {
        $('#mapModal').modal('hide');
    }

    function openImagePreview(url, label) {
        const img = document.getElementById('previewImage');
        const title = document.getElementById('previewTitle');
        img.src = url;
        title.textContent = `Preview Foto ${label}`;
        resetPreviewZoom();
        $('#previewModal').modal('show');
    }

    function closePreviewModal() {
        $('#previewModal').modal('hide');
    }

    function applyPreviewZoom() {
        const img = document.getElementById('previewImage');
        img.style.transform = `scale(${previewScale})`;
    }

    function zoomInPreview() {
        previewScale = Math.min(8, previewScale + 0.2);
        applyPreviewZoom();
    }

    function zoomOutPreview() {
        previewScale = Math.max(0.2, previewScale - 0.2);
        applyPreviewZoom();
    }

    function resetPreviewZoom() {
        previewScale = 1;
        applyPreviewZoom();
    }

    function openMapModal(latMasukRaw, lonMasukRaw, latPulangRaw, lonPulangRaw, fotoMasukRaw, fotoPulangRaw) {
        const latMasuk = toCoord(latMasukRaw);
        const lonMasuk = toCoord(lonMasukRaw);
        const latPulang = toCoord(latPulangRaw);
        const lonPulang = toCoord(lonPulangRaw);
        const fotoMasuk = String(fotoMasukRaw ?? '');
        const fotoPulang = String(fotoPulangRaw ?? '');
        const infoEl = document.getElementById('mapInfo');

        if (latMasuk === null || lonMasuk === null) {
            infoEl.style.display = 'block';
            infoEl.textContent = 'Koordinat masuk tidak tersedia.';
            document.getElementById('attendanceMap').innerHTML = '';
            $('#mapModal').modal('show');
            return;
        }

        infoEl.style.display = 'none';
        infoEl.textContent = '';
        $('#mapModal').modal('show');

        setTimeout(() => {
            if (typeof L === 'undefined') {
                infoEl.style.display = 'block';
                infoEl.textContent = 'Library map belum termuat.';
                return;
            }

            if (!attendanceMapInstance) {
                attendanceMapInstance = L.map('attendanceMap');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(attendanceMapInstance);
            }

            if (attendanceMapLayer) {
                attendanceMapLayer.clearLayers();
            } else {
                attendanceMapLayer = L.layerGroup().addTo(attendanceMapInstance);
            }

            const points = [];
            let masukPoint = [latMasuk, lonMasuk];
            let pulangPoint = null;
            let isNearPoint = false;
            let isExactSamePoint = false;

            const masukIcon = L.divIcon({
                className: '',
                html: '<div class="attendance-pin masuk"></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });
            const pulangIcon = L.divIcon({
                className: '',
                html: '<div class="attendance-pin pulang"></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });

            if (latPulang !== null && lonPulang !== null) {
                const dist = distanceMeters(latMasuk, lonMasuk, latPulang, lonPulang);
                if (dist < 15) {
                    isNearPoint = true;
                    isExactSamePoint = dist < 0.5;
                    // Jika terlalu dekat, geser visual marker agar tidak bertumpuk.
                    const offsetMeter = isExactSamePoint ? 12 : 8;
                    masukPoint = offsetByMeters(latMasuk, lonMasuk, offsetMeter, -offsetMeter);
                    pulangPoint = offsetByMeters(latPulang, lonPulang, -offsetMeter, offsetMeter);
                } else {
                    pulangPoint = [latPulang, lonPulang];
                }
            }

            const masukMarker = L.marker(masukPoint, {
                icon: masukIcon
            }).addTo(attendanceMapLayer);
            masukMarker.bindTooltip(photoTooltipHtml('Masuk', fotoMasuk), {
                permanent: true,
                direction: 'top',
                offset: [0, -8],
                className: 'map-pin-label',
                interactive: true
            });
            points.push(masukPoint);

            if (pulangPoint) {
                const pulangMarker = L.marker(pulangPoint, {
                    icon: pulangIcon
                }).addTo(attendanceMapLayer);
                pulangMarker.bindTooltip(photoTooltipHtml('Pulang', fotoPulang), {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -8],
                    className: 'map-pin-label',
                    interactive: true
                });
                points.push(pulangPoint);
            }

            if (points.length === 1) {
                attendanceMapInstance.setView(points[0], 16);
            } else {
                attendanceMapInstance.fitBounds(points, {
                    padding: [80, 80],
                    maxZoom: 19
                });
                const minZoomForNear = isExactSamePoint ? 19 : (isNearPoint ? 18 : 0);
                if (minZoomForNear > 0 && attendanceMapInstance.getZoom() < minZoomForNear) {
                    attendanceMapInstance.setZoom(minZoomForNear);
                }
            }
            attendanceMapInstance.invalidateSize();
        }, 250);
    }

    $('#mapModal').on('hidden.bs.modal', function() {
        if (attendanceMapLayer) {
            attendanceMapLayer.clearLayers();
        }
    });

    $('#previewModal').on('hidden.bs.modal', function() {
        document.getElementById('previewImage').src = '';
        document.getElementById('previewTitle').textContent = 'Preview Foto';
        resetPreviewZoom();
    });


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

        columns.push({
            data: null,
            title: 'Aksi',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const latMasuk = row.latitude ?? '';
                const lonMasuk = row.longitude ?? '';
                const latPulang = row.latpulang ?? '';
                const lonPulang = row.lonpulang ?? '';
                const fotoMasuk = row.foto2 ?? '';
                const fotoPulang = row.fotopulang2 ?? '';
                const esc = (v) => String(v ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                return `<button class="btn-map" onclick="openMapModal('${esc(latMasuk)}','${esc(lonMasuk)}','${esc(latPulang)}','${esc(lonPulang)}','${esc(fotoMasuk)}','${esc(fotoPulang)}')">Tampilkan Map</button>`;
            }
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
