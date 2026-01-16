@extends('layouts.pages-layouts')

@section('pageTitle', 'Kategori Inventaris')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Kategori Inventaris</h4>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalForm">
                        <i class="fas fa-plus"></i> Tambah Data
                    </button>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="kategoriTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Kategori</th>
                                    <th>Nama Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFormLabel">Tambah Data Kategori Inventaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="kategoriForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="id_kategori_old" id="id_kategori_old">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="id_kategori">ID Kategori</label>
                        <input type="text" class="form-control" id="id_kategori" name="id_kategori" required>
                        <div class="invalid-feedback">ID Kategori harus diisi</div>
                    </div>
                    <div class="form-group">
                        <label for="nama_kategori">Nama Kategori</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                        <div class="invalid-feedback">Nama Kategori harus diisi</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#kategoriTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('kategori-inventaris.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'id_kategori', name: 'id_kategori'},
            {data: 'nama_kategori', name: 'nama_kategori'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    // Reset form when modal is closed
    $('#modalForm').on('hidden.bs.modal', function () {
        $('#kategoriForm')[0].reset();
        $('#formMethod').val('POST');
        $('.invalid-feedback').hide();
        $('input').removeClass('is-invalid');
    });

    // Show create form
    $('.btn-primary').click(function() {
        $('#modalFormLabel').text('Tambah Data Kategori Inventaris');
        $('#formMethod').val('POST');
    });

    // Show edit form
    $(document).on('click', '.edit', function() {
        var id = $(this).data('id');
        $.get("{{ route('kategori-inventaris.index') }}/" + id + "/edit", function(data) {
            $('#modalFormLabel').text('Edit Data Kategori Inventaris');
            $('#formMethod').val('PUT');
            $('#id_kategori_old').val(data.id_kategori);
            $('#id_kategori').val(data.id_kategori);
            $('#nama_kategori').val(data.nama_kategori);
            $('#modalForm').modal('show');
        });
    });

    // Handle form submission
    $('#kategoriForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = "{{ route('kategori-inventaris.store') }}";
        var method = 'POST';
        
        if ($('#formMethod').val() === 'PUT') {
            url = "{{ route('kategori-inventaris.index') }}/" + $('#id_kategori_old').val();
            method = 'POST'; // Laravel's way of handling PUT/PATCH via POST with _method field
        }

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(response) {
                $('#modalForm').modal('hide');
                table.ajax.reload();
                alert(response.success);
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                $('.invalid-feedback').hide();
                $('input').removeClass('is-invalid');
                
                $.each(errors, function(key, value) {
                    $('#' + key).addClass('is-invalid').next('.invalid-feedback').text(value[0]).show();
                });
            }
        });
    });

    // Handle delete button click
    var deleteId;
    $(document).on('click', '.delete', function() {
        deleteId = $(this).data('id');
        $('#deleteModal').modal('show');
    });

    // Confirm delete
    $('#confirmDelete').click(function() {
        $.ajax({
            url: "{{ route('kategori-inventaris.index') }}/" + deleteId,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE'
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                table.ajax.reload();
                alert(response.success);
            }
        });
    });
});
</script>
@endpush
