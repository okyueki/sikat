@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@php
    $defaultStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    $defaultEnd = \Carbon\Carbon::now()->format('Y-m-d');
@endphp

@push('head')
<style>
    .rekap-libur-filter-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.5rem;
        background: var(--custom-white, #fff);
    }
    #rekapLiburTable_wrapper .dataTables_length select {
        min-width: 4.5rem;
    }
    .rekap-total-card {
        border: 1px dashed rgba(13, 110, 253, 0.35);
        border-radius: 0.5rem;
        background: rgba(13, 110, 253, 0.04);
        padding: 0.75rem 1rem;
    }
</style>
@endpush

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card custom-card shadow-sm">
                    <div class="card-header border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="card-title mb-0">{{ $title }}</div>
                            <small class="text-muted">Format ringkas: nama | tanggal mulai | tanggal akhir | jenis | status.</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="rekap-libur-filter-card p-3 mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3 col-sm-6">
                                    <label for="start_date" class="form-label fw-medium mb-1"><i class="far fa-calendar-alt me-1 text-primary"></i>Mulai tanggal</label>
                                    <input type="date" id="start_date" class="form-control" value="{{ $defaultStart }}" autocomplete="off">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="end_date" class="form-label fw-medium mb-1"><i class="far fa-calendar-check me-1 text-primary"></i>Sampai tanggal</label>
                                    <input type="date" id="end_date" class="form-control" value="{{ $defaultEnd }}" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" id="filter" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Terapkan filter
                                        </button>
                                        <button type="button" id="preset-month" class="btn btn-outline-secondary btn-sm">
                                            Bulan ini
                                        </button>
                                        <button type="button" id="preset-year" class="btn btn-outline-secondary btn-sm">
                                            Tahun ini
                                        </button>
                                        <button type="button" id="filter-reset" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-undo me-1"></i>Reset tanggal
                                        </button>
                                        <button type="button" id="export-csv" class="btn btn-success btn-sm">
                                            <i class="fas fa-file-csv me-1"></i>Export CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rekap-total-card mb-3 d-flex align-items-center justify-content-between">
                            <div class="text-muted">Total hari (hasil filter):</div>
                            <div><span id="totalHariFiltered" class="badge bg-primary fs-6">0 hari</span></div>
                        </div>

                        <div class="table-responsive rounded border">
                            <table id="rekapLiburTable" class="table table-hover table-striped align-middle mb-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-nowrap">Nama pegawai</th>
                                        <th class="text-nowrap">Tanggal mulai</th>
                                        <th class="text-nowrap">Tanggal akhir</th>
                                        <th>Jenis</th>
                                        <th>Status</th>
                                        <th class="text-end">Jumlah hari</th>
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
    var defaultStart = @json($defaultStart);
    var defaultEnd = @json($defaultEnd);

    $("#start_date,#end_date").flatpickr({
        dateFormat: "Y-m-d",
        allowInput: true
    });

    function applyPreset(start, end) {
        var s = $('#start_date')[0]._flatpickr;
        var e = $('#end_date')[0]._flatpickr;
        if (s) { s.setDate(start, false); } else { $('#start_date').val(start); }
        if (e) { e.setDate(end, false); } else { $('#end_date').val(end); }
    }

    $('#preset-month').on('click', function() {
        applyPreset(defaultStart, defaultEnd);
        table.ajax.reload();
    });

    $('#preset-year').on('click', function() {
        var y = new Date().getFullYear();
        applyPreset(y + '-01-01', y + '-12-31');
        table.ajax.reload();
    });

    $('#filter-reset').on('click', function() {
        applyPreset(defaultStart, defaultEnd);
        table.ajax.reload();
    });

    let table = $('#rekapLiburTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        ajax: {
            url: @json(route('pengajuan_libur.rekap-libur')),
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        columns: [
            { data: 'nama', name: 'nama', className: 'fw-medium' },
            { data: 'tanggal_awal', name: 'tanggal_awal', className: 'text-nowrap' },
            { data: 'tanggal_akhir', name: 'tanggal_akhir', className: 'text-nowrap' },
            { data: 'jenis', name: 'jenis_pengajuan_libur' },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            {
                data: 'jumlah_hari',
                name: 'jumlah_hari',
                className: 'text-end',
                render: function(data) {
                    var n = parseInt(data, 10) || 0;
                    return n + ' hari';
                }
            },
            { data: 'detail', name: 'detail', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        drawCallback: function(settings) {
            var json = settings.json || {};
            var total = parseInt(json.total_hari, 10) || 0;
            $('#totalHariFiltered').text(total + ' hari');
        }
    });

    $('#filter').on('click', function() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        if (startDate === '' || endDate === '') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Rentang tanggal', text: 'Pilih tanggal mulai dan tanggal akhir terlebih dahulu.' });
            } else {
                alert('Silakan pilih rentang tanggal terlebih dahulu.');
            }
            return;
        }
        if (startDate > endDate) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Tanggal tidak valid', text: 'Tanggal mulai tidak boleh setelah tanggal akhir.' });
            } else {
                alert('Tanggal mulai tidak boleh setelah tanggal akhir.');
            }
            return;
        }
        table.ajax.reload();
    });

    $('#start_date, #end_date').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#filter').trigger('click');
        }
    });
    
    $('#export-csv').on('click', function() {
        var params = new URLSearchParams({
            export: 'csv',
            start_date: $('#start_date').val() || '',
            end_date: $('#end_date').val() || ''
        });
        window.location.href = @json(route('pengajuan_libur.rekap-libur')) + '?' + params.toString();
    });
});
</script>
@endsection
