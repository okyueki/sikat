@extends('layouts.pages-layouts')

@section('pageTitle', 'Produsen Inventaris')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Produsen Inventaris</h4>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalForm">
                        <i class="fas fa-plus"></i> Tambah Data
                    </button>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="produsenTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Produsen</th>
                                    <th>Nama Produsen</th>
                                    <th>No. Telp</th>
                                    <th>Email</th>
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFormLabel">Tambah Data Produsen Inventaris</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="produsenForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="kode_produsen_old" id="kode_produsen_old">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="kode_produsen">Kode Produsen *</label>
                                <input type="text" class="form-control" id="kode_produsen" name="kode_produsen" required>
                                <div class="invalid-feedback">Kode Produsen harus diisi</div>
                            </div>
                            <div class="form-group">
                                <label for="nama_produsen">Nama Produsen *</label>
                                <input type="text" class="form-control" id="nama_produsen" name="nama_produsen" required>
                                <div class="invalid-feedback">Nama Produsen harus diisi</div>
                            </div>
                            <div class="form-group">
                                <label for="no_telp">No. Telepon</label>
                                <input type="text" class="form-control" id="no_telp" name="no_telp">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                                <div class="invalid-feedback">Format email tidak valid</div>
                            </div>
                            <div class="form-group">
                                <label for="website_produsen">Website</label>
                                <input type="url" class="form-control" id="website_produsen" name="website_produsen">
                                <div class="invalid-feedback">Format URL tidak valid (contoh: https://example.com)</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alamat_produsen">Alamat</label>
                        <textarea class="form-control" id="alamat_produsen" name="alamat_produsen" rows="2"></textarea>
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
    var table = $('#produsenTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('produsen-inventaris.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'kode_produsen', name: 'kode_produsen'},
            {data: 'nama_produsen', name: 'nama_produsen'},
            {data: 'no_telp', name: 'no_telp'},
            {data: 'email', name: 'email'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    // Reset form when modal is closed
    $('#modalForm').on('hidden.bs.modal', function () {
        $('#produsenForm')[0].reset();
        $('#formMethod').val('POST');
        $('.invalid-feedback').hide();
        $('input, textarea').removeClass('is-invalid');
    });

    // Show create form
    $('.btn-primary').click(function() {
        $('#modalFormLabel').text('Tambah Data Produsen Inventaris');
        $('#formMethod').val('POST');
    });

    // Show edit form
    $(document).on('click', '.edit', function() {
        var id = $(this).data('id');
        $.get("{{ route('produsen-inventaris.index') }}/" + id + "/edit", function(data) {
            $('#modalFormLabel').text('Edit Data Produsen Inventaris');
            $('#formMethod').val('PUT');
            $('#kode_produsen_old').val(data.kode_produsen);
            $('#kode_produsen').val(data.kode_produsen);
            $('#nama_produsen').val(data.nama_produsen);
            $('#alamat_produsen').val(data.alamat_produsen);
            $('#no_telp').val(data.no_telp);
            $('#email').val(data.email);
            $('#website_produsen').val(data.website_produsen);
            $('#modalForm').modal('show');
        });
    });

    // Handle form submission
    $('#produsenForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = "{{ route('produsen-inventaris.store') }}";
        var method = 'POST';
        
        if ($('#formMethod').val() === 'PUT') {
            url = "{{ route('produsen-inventaris.index') }}/" + $('#kode_produsen_old').val();
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
                $('input, textarea').removeClass('is-invalid');
                
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
            url: "{{ route('produsen-inventaris.index') }}/" + deleteId,
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
