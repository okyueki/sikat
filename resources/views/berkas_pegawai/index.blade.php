@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title :  $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                @if ($message = Session::get('success'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: '{{ $message }}',
                                confirmButtonText: 'OK'
                            });
                        });
                    </script>
                @endif
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Status Verifikasi</label>
                        <select id="filter-verifikasi" class="form-control">
                            <option value="">Semua</option>
                            <option value="review">Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="uploaded">Uploaded</option>
                        </select>
                    </div>
                </div>

<div class="table-responsive">
                <table class="table table-bordered" id="berkas-pegawai">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Bidang</th>
                            <th>Kategori</th>
                            <th>Progress</th>
                            <th width="280px">Aksi</th>
                        </tr>
                    </thead>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        var table = $('#berkas-pegawai').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/berkas_pegawai',
                data: function (d) {
                    d.verifikasi = $('#filter-verifikasi').val();
                }
            },
            columns: [
                { data: null, name: 'no', searchable: false, orderable: false, render: function (data, type, row, meta) {
                    return meta.row + 1;
                }},
                { data: 'nama', name: 'nama' },
                { data: 'jbtn', name: 'jbtn' },
                { data: 'bidang', name: 'bidang' },
                { data: 'kategori', name: 'kategori', orderable: false, searchable: false },
                { data: 'progress', name: 'progress', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        $('#filter-verifikasi').on('change', function () {
            table.ajax.reload();
        });
    });
     document.addEventListener('DOMContentLoaded', function () {
        $(document).on('click', '.delete-btn', function () {
            var deleteUrl = $(this).data('url');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                            $('#datatable').DataTable().ajax.reload(); // Reload DataTables
                        },
                        error: function () {
                            Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>

@endsection