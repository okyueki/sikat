@extends('layouts.pages-layouts')

@section('pageTitle', 'Merk Inventaris')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Merk Inventaris</h4>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalForm">
                        <i class="fas fa-plus"></i> Tambah Data
                    </button>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="merkTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Merk</th>
                                    <th>Nama Merk</th>
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
                <h5 class="modal-title" id="modalFormLabel">Tambah Data Merk Inventaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="merkForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="kode_merk_old" id="kode_merk_old">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="kode_merk">Kode Merk</label>
                        <input type="text" class="form-control" id="kode_merk" name="kode_merk" required>
                        <div class="invalid-feedback">Kode Merk harus diisi</div>
                    </div>
                    <div class="form-group">
                        <label for="nama_merk">Nama Merk</label>
                        <input type="text" class="form-control" id="nama_merk" name="nama_merk" required>
                        <div class="invalid-feedback">Nama Merk harus diisi</div>
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
    var table = $('#merkTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('merk-inventaris.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'kode_merk', name: 'kode_merk'},
            {data: 'nama_merk', name: 'nama_merk'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    // Reset form when modal is closed
    $('#modalForm').on('hidden.bs.modal', function () {
        $('#merkForm')[0].reset();
        $('#formMethod').val('POST');
        $('.invalid-feedback').hide();
        $('input').removeClass('is-invalid');
    });

    // Show create form
    $('.btn-primary').click(function() {
        $('#modalFormLabel').text('Tambah Data Merk Inventaris');
        $('#formMethod').val('POST');
    });

    // Show edit form
    $(document).on('click', '.edit', function() {
        var id = $(this).data('id');
        $.get("{{ route('merk-inventaris.index') }}/" + id + "/edit", function(data) {
            $('#modalFormLabel').text('Edit Data Merk Inventaris');
            $('#formMethod').val('PUT');
            $('#kode_merk_old').val(data.kode_merk);
            $('#kode_merk').val(data.kode_merk);
            $('#nama_merk').val(data.nama_merk);
            $('#modalForm').modal('show');
        });
    });

    // Handle form submission
    $('#merkForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = "{{ route('merk-inventaris.store') }}";
        var method = 'POST';
        
        if ($('#formMethod').val() === 'PUT') {
            url = "{{ route('merk-inventaris.index') }}/" + $('#kode_merk_old').val();
            method = 'POST';
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
            url: "{{ route('merk-inventaris.index') }}/" + deleteId,
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
