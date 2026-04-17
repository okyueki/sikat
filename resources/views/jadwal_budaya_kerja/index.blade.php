@extends('layouts.pages-layouts')

@section('pageTitle', 'Daftar Jadwal Budaya Kerja')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['total'] }}</h3>
                        <small>Total Jadwal</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['total_bulan_ini'] }}</h3>
                        <small>Bulan Ini ({{ \Carbon\Carbon::create()->month($currentMonth)->translatedFormat('F') }})</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['pagi'] }} / {{ $summary['sore'] }}</h3>
                        <small>Pagi / Sore</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['terkirim'] }}</h3>
                        <small>WA Terkirim Bulan Ini</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Daftar Jadwal Budaya Kerja</h3>
                        <div>
                            <a href="{{ route('jadwalbudayakerja.kalender') }}" class="btn btn-info btn-sm me-2">
                                <i class="fa fa-calendar"></i> Kalender
                            </a>
                            <a href="{{ route('jadwalbudayakerja.generator') }}" class="btn btn-primary btn-sm me-2">
                                <i class="fa fa-magic"></i> Generator Bulanan
                            </a>
                            <button id="btn-export" class="btn btn-success btn-sm">
                                <i class="fa fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Tampilkan pesan sukses jika ada -->
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-3 mb-2">
                                <select id="filter-bulan" class="form-control">
                                    <option value="">Semua Bulan</option>
                                    @for($i=1; $i<=12; $i++)
                                        <option value="{{ $i }}" {{ $i == $currentMonth ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select id="filter-tahun" class="form-control">
                                    <option value="">Semua Tahun</option>
                                    @for($i=2024; $i<=2027; $i++)
                                        <option value="{{ $i }}" {{ $i == $currentYear ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select id="filter-shift" class="form-control">
                                    <option value="">Semua Shift</option>
                                    <option value="Pagi">Pagi</option>
                                    <option value="Sore">Sore</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button id="btn-filter" class="btn btn-primary">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
                                <button id="btn-reset" class="btn btn-secondary">
                                    <i class="fa fa-refresh"></i> Reset
                                </button>
                            </div>
                        </div>

                        <a href="{{ route('jadwalbudayakerja.create') }}" class="btn btn-primary mb-3">
                            <i class="fa fa-plus"></i> Tambah Jadwal
                        </a>
                        <button id="btn-bulk-delete" class="btn btn-danger mb-3 ms-2" disabled>
                            <i class="fa fa-trash"></i> Hapus Terpilih (<span id="selected-count">0</span>)
                        </button>

                        <table class="table table-bordered table-striped" id="jadwal-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="select-all" class="form-check-input" title="Tandai semua baris di halaman ini">
                                    </th>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Tanggal Bertugas</th>
                                    <th>Shift</th>
                                    <th>Hari</th>
                                    <th>No. Hp</th>
                                    <th>Status WA</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        let table = $('#jadwal-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('jadwalbudayakerja.index') }}",
                data: function(d) {
                    d.bulan = $('#filter-bulan').val();
                    d.tahun = $('#filter-tahun').val();
                    d.shift = $('#filter-shift').val();
                }
            },
            columns: [
                { data: 'select', name: 'select', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nama', name: 'nama' },
                { data: 'tanggal_bertugas', name: 'tanggal_bertugas' },
                { data: 'shift', name: 'shift' },
                { data: 'hari', name: 'hari' },
                { data: 'no_telp', name: 'no_telp' },
                { data: 'whatsapp_status', name: 'whatsapp_status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                zeroRecords: "Tidak ditemukan data yang sesuai",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });

        let selectedIds = new Set();

        function updateSelectedUi() {
            const count = selectedIds.size;
            $('#selected-count').text(count);
            $('#btn-bulk-delete').prop('disabled', count === 0);
        }

        function syncRowCheckboxes() {
            let checkedOnPage = 0;
            const $rows = $('.row-select');

            $rows.each(function() {
                const id = parseInt($(this).val(), 10);
                const checked = selectedIds.has(id);
                $(this).prop('checked', checked);
                if (checked) checkedOnPage++;
            });

            const totalOnPage = $rows.length;
            const allChecked = totalOnPage > 0 && checkedOnPage === totalOnPage;
            $('#select-all').prop('checked', allChecked);
            $('#select-all').prop('indeterminate', checkedOnPage > 0 && checkedOnPage < totalOnPage);
        }

        table.on('draw', function() {
            syncRowCheckboxes();
            updateSelectedUi();
        });

        $(document).on('change', '.row-select', function() {
            const id = parseInt($(this).val(), 10);
            if ($(this).is(':checked')) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            syncRowCheckboxes();
            updateSelectedUi();
        });

        $('#select-all').on('change', function() {
            const checked = $(this).is(':checked');
            $('.row-select').each(function() {
                const id = parseInt($(this).val(), 10);
                if (checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            });
            syncRowCheckboxes();
            updateSelectedUi();
        });

        // Filter button click
        $('#btn-filter').on('click', function() {
            $('#select-all').prop('checked', false).prop('indeterminate', false);
            table.ajax.reload();
        });

        // Reset button click
        $('#btn-reset').on('click', function() {
            $('#filter-bulan').val('{{ $currentMonth }}');
            $('#filter-tahun').val('{{ $currentYear }}');
            $('#filter-shift').val('');
            selectedIds.clear();
            updateSelectedUi();
            $('#select-all').prop('checked', false).prop('indeterminate', false);
            table.ajax.reload();
        });

        // Export button handler
        $('#btn-export').on('click', function() {
            let bulan = $('#filter-bulan').val();
            let tahun = $('#filter-tahun').val();
            let shift = $('#filter-shift').val();
            
            let exportUrl = "{{ route('jadwalbudayakerja.index') }}?export=1";
            if (bulan) exportUrl += '&bulan=' + bulan;
            if (tahun) exportUrl += '&tahun=' + tahun;
            if (shift) exportUrl += '&shift=' + shift;
            
            window.open(exportUrl, '_blank');
        });

        $('#btn-bulk-delete').on('click', function() {
            if (selectedIds.size === 0) {
                return;
            }

            Swal.fire({
                title: "Hapus data terpilih?",
                text: `Total ${selectedIds.size} data akan dihapus permanen.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, hapus semua!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route('jadwalbudayakerja.bulk-destroy') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        ids: Array.from(selectedIds)
                    },
                    success: function(response) {
                        Swal.fire("Berhasil!", response.message, "success");
                        selectedIds.clear();
                        updateSelectedUi();
                        $('#select-all').prop('checked', false).prop('indeterminate', false);
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || "Terjadi kesalahan saat menghapus data.";
                        Swal.fire("Error!", message, "error");
                    }
                });
            });
        });

        // SweetAlert2 untuk Konfirmasi Hapus
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            let deleteUrl = $(this).attr('href');

            Swal.fire({
                title: "Apakah Anda yakin?",
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            Swal.fire("Deleted!", response.message, "success");
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire("Error!", "Terjadi kesalahan saat menghapus.", "error");
                        }
                    });
                }
            });
        });

    });
</script>
@endsection


