@extends('layouts.pages-layouts')

@section('pageTitle', 'Daftar Penilaian Harian')

@section('content')
<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Daftar Penilaian Harian</h4>
                        <div class="card-actions">
                            <a href="{{ route('budayakerja.rekap_pegawai') }}" class="btn btn-secondary me-2">
                                <i class="fas fa-users me-1"></i>Rekap Semua Pegawai
                            </a>
                            <a href="{{ route('budayakerja.rekap_petugas') }}" class="btn btn-warning me-2">
                                <i class="fas fa-user-check me-1"></i>Rekap Petugas Penilai
                            </a>
                            <a href="{{ route('budayakerja.rekapan') }}" class="btn btn-info me-2">
                                <i class="fas fa-chart-bar me-1"></i>Rekapan Bulanan
                            </a>
                            <a href="{{ route('budayakerja.create') }}" class="btn btn-primary">Tambah Penilaian</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Filter Tanggal -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <input type="date" id="start_date" class="form-control" placeholder="Tanggal Mulai">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="end_date" class="form-control" placeholder="Tanggal Sampai">
                            </div>
                            <div class="col-md-3">
                                <button id="filter" class="btn btn-primary">Filter</button>
                                <button id="reset" class="btn btn-secondary">Reset</button>
                            </div>
                            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                <form id="bulkDeleteForm" action="{{ route('budayakerja.bulk-destroy') }}" method="POST" class="d-inline">
                                    @csrf
                                    <span id="selectedCount" class="me-2 text-muted small">0 dipilih</span>
                                    <span id="bulkDeleteIdsContainer"></span>
                                    <button type="button" id="bulkDeleteBtn" class="btn btn-danger" disabled>
                                        <i class="fas fa-trash me-1"></i>Hapus Terpilih
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table id="budayaKerjaTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 36px;">
                                            <input type="checkbox" id="select_all" class="form-check-input" title="Tandai semua baris di halaman ini">
                                        </th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>NIK Pegawai</th>
                                        <th>Nama Pegawai</th>
                                        <th>Departemen</th>
                                        <th>Jabatan</th>
                                        <th>Shift</th>
                                        <th>Total Nilai</th>
                                        <th>Petugas</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
   $(document).ready(function() {
    // Set default dates to today's date (tanggal lokal, BUKAN UTC)
    // toISOString() pakai UTC → sebelum jam 07 WIB tanggal jadi kemarin. Pakai tanggal lokal:
    var d = new Date();
    var today = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    $('#start_date').val(today);
    $('#end_date').val(today);

    // Initialize DataTable
    var table = $('#budayaKerjaTable').DataTable({
        processing: true,  // Enable processing indicator
        serverSide: true,  // Enable server-side processing for large datasets
        ajax: {
            url: '/databudayakerja',  // The route for fetching data
            data: function(d) {
                d.start_date = $('#start_date').val();  // Send start date for filtering
                d.end_date = $('#end_date').val();  // Send end date for filtering
            }
        },
        columns: [
            { data: 'select', name: 'select', orderable: false, searchable: false },
            { data: 'tanggal', name: 'tanggal' },
            { data: 'jam', name: 'jam' },
            { data: 'nik_pegawai', name: 'nik_pegawai' },
            { data: 'nama_pegawai', name: 'nama_pegawai' },
            { data: 'departemen', name: 'departemen' },
            { data: 'jabatan', name: 'jabatan' },
            { data: 'shift', name: 'shift' },
            { data: 'total_nilai', name: 'total_nilai' },
            { data: 'petugas', name: 'petugas' },
            { data: 'keterangan', name: 'keterangan', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    var selectedIds = new Set();
    var $selectedCount = $('#selectedCount');
    var $bulkDeleteBtn = $('#bulkDeleteBtn');
    var $selectAll = $('#select_all');

    function renderBulkDeleteInputs() {
        var html = '';
        selectedIds.forEach(function(id) {
            html += '<input type="hidden" name="ids[]" value="' + id + '">';
        });
        $('#bulkDeleteIdsContainer').html(html);
    }

    function updateBulkUiState() {
        var selectedTotal = selectedIds.size;
        $selectedCount.text(selectedTotal + ' dipilih');
        $bulkDeleteBtn.prop('disabled', selectedTotal === 0);
        renderBulkDeleteInputs();
    }

    function syncVisibleCheckboxesFromSelection() {
        var visible = $('.row-select');
        var checkedCount = 0;

        visible.each(function() {
            var id = parseInt($(this).val(), 10);
            var isChecked = selectedIds.has(id);
            $(this).prop('checked', isChecked);
            if (isChecked) checkedCount++;
        });

        var totalVisible = visible.length;
        var allChecked = totalVisible > 0 && checkedCount === totalVisible;
        $selectAll.prop('checked', allChecked);
        $selectAll.prop('indeterminate', checkedCount > 0 && checkedCount < totalVisible);
    }

    $('#budayaKerjaTable').on('change', '.row-select', function() {
        var id = parseInt($(this).val(), 10);
        if ($(this).is(':checked')) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        updateBulkUiState();
        syncVisibleCheckboxesFromSelection();
    });

    $selectAll.on('change', function() {
        var checked = $(this).is(':checked');
        $('.row-select').each(function() {
            var id = parseInt($(this).val(), 10);
            if (checked) {
                selectedIds.add(id);
                $(this).prop('checked', true);
            } else {
                selectedIds.delete(id);
                $(this).prop('checked', false);
            }
        });
        updateBulkUiState();
        syncVisibleCheckboxesFromSelection();
    });

    table.on('draw', function() {
        syncVisibleCheckboxesFromSelection();
        updateBulkUiState();
    });

    // Filter Button: Reload table with the filter applied
    $('#filter').click(function() {
        $selectAll.prop('checked', false).prop('indeterminate', false);
        table.ajax.reload();
    });

    // Reset Button: Reset the date fields and reload table without filters
    $('#reset').click(function() {
        $('#start_date').val('');
        $('#end_date').val('');
        selectedIds.clear();
        updateBulkUiState();
        $selectAll.prop('checked', false).prop('indeterminate', false);
        table.ajax.reload();
    });

    $('#bulkDeleteBtn').click(function() {
        if (selectedIds.size === 0) return;
        var confirmed = confirm('Hapus ' + selectedIds.size + ' data yang dipilih?');
        if (!confirmed) return;
        $('#bulkDeleteForm').trigger('submit');
    });
});
</script>

@endsection