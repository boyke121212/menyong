<!-- CSRF -->
<input type="hidden"
id="csrf_token"
name="<?= csrf_token() ?>"
value="<?= csrf_hash() ?>">

<table id="table_berita" class="display" style="width:100%">
<thead>
<tr>
    <th>ID</th>
    <th>Judul</th>
    <th>Isi</th>
    <th>Tanggal</th>
    <th>Foto</th>
    <th>PDF</th>
    <th>Aksi</th>
     <th></th>
</tr>
</thead>
</table>

<script>
$(function(){

function csrfData(){
    return {
        '<?= csrf_token() ?>': $('#csrf_token').val()
    };
}

var table = $('#table_berita').DataTable({
    processing:true,
    serverSide:true,

    ajax:{
        url:"<?= base_url('berita/getData')?>",
        type:"POST",
        data:function(d){
            d.tgl_awal = $('#tgl_awal').val();
            d.tgl_akhir = $('#tgl_akhir').val();
            $.extend(d, csrfData());
        },
        dataSrc:function(json){
            $('#csrf_token').val(json.csrfHash);
            return json.data;
        }
    },

    columnDefs:[
        {targets:[6],orderable:false}
    ],

    initComplete:function(){

        // RANGE DI SHOW ENTRIES
        $("#table_berita_length").append(`
            <span style="margin-left:20px;">
                Dari:
                <input type="date" id="tgl_awal">
                Sampai:
                <input type="date" id="tgl_akhir">
            </span>
        `);

        // TOMBOL DI ATAS SEARCH
        $("#table_berita_filter").prepend(`
            <a href="<?= base_url('berita/add')?>"
               style="
                margin-right:10px;
                padding:6px 12px;
                background:#007bff;
                color:#fff;
                text-decoration:none;
                border-radius:4px;">
               + Tambah Berita
            </a>
        `);

        $(document).on('change','#tgl_awal,#tgl_akhir',function(){
            table.ajax.reload();
        });
    }
});


/* DELETE */
$(document).on('click','.btn-delete',function(){

    if(!confirm('Hapus data?')) return;

    var data={id:$(this).data('id')};

    data[$('#csrf_token').attr('name')] =
        $('#csrf_token').val();

    $.post("<?= base_url('berita/delete')?>",data,function(res){
        $('#csrf_token').val(res.csrfHash);
        table.ajax.reload(null,false);
    });

});

/* edit */
$(document).on('click','.btn-edit',function(){

    if(!confirm('Mau Edit data?')) return;

    var data={id:$(this).data('id')};

    data[$('#csrf_token').attr('name')] =
        $('#csrf_token').val();

    $.post("<?= base_url('berita/edit')?>",data,function(res){
        $('#csrf_token').val(res.csrfHash);
        table.ajax.reload(null,false);
    });

});
});
</script>