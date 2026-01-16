@extends('layouts.pages-layouts')

@section('pageTitle', 'Inventaris')

@section('content')
<div class="col-xl-12">
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Daftar Inventaris</h3>
            <a href="{{ route('inventaris.visualisasi') }}" class="btn btn-info btn-sm">
                <i class="fa fa-chart-bar me-1"></i>Visualisasi Data
            </a>
        </div>
<div class="card-body">
    <!-- Filter Section - Responsive -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-auto">
            <a href="{{ route('inventaris.create') }}" class="btn btn-primary w-100 w-md-auto">
                <i class="fa fa-plus me-1"></i>Tambah Inventaris
            </a>
        </div>
        <div class="col-12 col-md-3">
            <select name="ruang" id="ruang" class="js-example-basic-single form-control w-100">
                <option value="">-- Pilih Nama Ruang --</option>
                @foreach($ruang as $r)
                    <option value="{{ $r->id_ruang }}" {{ request('ruang') == $r->id_ruang ? 'selected' : '' }}>
                        {{ $r->nama_ruang }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4">
            <input type="text" name="search" id="search" class="form-control" placeholder="Cari..." value="{{ request('search') }}">
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" id="filter-button" class="btn btn-secondary w-100 w-md-auto">
                <i class="fa fa-filter me-1"></i>Filter
            </button>
        </div>
    </div>
</div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="inventaris-table">
                    <thead>
                        <tr>
                            <th>No Inventaris</th>
                            <th class="none">Kode Barang</th>
                            <th>Nama Barang</th>
                            <th class="none">Produsen</th>
                            <th class="none">Merk</th>
                            <th>Nama Ruang</th>
                            <th class="none">Asal Barang</th>
                            <th class="none">Tanggal Pengadaan</th>
                            <th>Harga</th>
                            <th>Status Barang</th>
                            <th class="none">Photo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Responsive styles untuk inventaris table */
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        #inventaris-table {
            min-width: 800px;
        }
        
        .card-body {
            padding: 0.75rem;
        }
        
        .btn-group {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .btn-group .btn {
            width: 100%;
            margin: 0;
        }
    }
    
    @media (max-width: 576px) {
        .col-md-auto,
        .col-md-3,
        .col-md-4 {
            margin-bottom: 0.5rem;
        }
        
        #inventaris-table_wrapper .dataTables_length,
        #inventaris-table_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
    }
    
    /* Style untuk child rows di responsive mode */
    table.dataTable.dtr-inline.collapsed > tbody > tr > td.child,
    table.dataTable.dtr-inline.collapsed > tbody > tr > th.child,
    table.dataTable.dtr-inline.collapsed > tbody > tr > td.dataTables_empty {
        cursor: default !important;
    }
    
    table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > td:first-child:before,
    table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > th:first-child:before {
        top: 50%;
        transform: translateY(-50%);
    }
</style>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2 dengan responsive
        $('.js-example-basic-single').select2({
            width: '100%',
            dropdownAutoWidth: true
        });

        // Inisialisasi DataTables dengan responsive
        var table = $('#inventaris-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: {
                details: {
                    type: 'column',
                    target: 'tr'
                }
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: "Memproses...",
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            ajax: {
                url: '/inventaris',
                data: function(d) {
                    d.ruang = $('#ruang').val();
                    d.search = $('#search').val();
                }
            },
            columns: [
                { 
                    data: 'no_inventaris', 
                    name: 'no_inventaris',
                    className: 'control'
                },
                { 
                    data: 'kode_barang', 
                    name: 'kode_barang',
                    className: 'none'
                },
                { 
                    data: 'nama_barang', 
                    name: 'nama_barang'
                },
                { 
                    data: 'nama_produsen', 
                    name: 'nama_produsen',
                    className: 'none'
                },
                { 
                    data: 'nama_merk', 
                    name: 'nama_merk',
                    className: 'none'
                },
                { 
                    data: 'nama_ruang', 
                    name: 'nama_ruang'
                },
                { 
                    data: 'asal_barang', 
                    name: 'asal_barang',
                    className: 'none'
                },
                { 
                    data: 'tgl_pengadaan', 
                    name: 'tgl_pengadaan',
                    className: 'none'
                },
                { 
                    data: 'harga', 
                    name: 'harga'
                },
                { 
                    data: 'status_barang', 
                    name: 'status_barang'
                },
                { 
                    data: 'photo', 
                    name: 'photo', 
                    orderable: false, 
                    searchable: false,
                    className: 'none'
                },
                { 
                    data: 'action', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false
                }
            ],
            order: [[0, 'desc']],
            drawCallback: function(settings) {
                // Re-initialize Select2 after table redraw if needed
                $('.js-example-basic-single').select2({
                    width: '100%'
                });
            }
        });

        // Event listener untuk filter
        $('#filter-button').click(function() {
            table.draw();
        });
        
        // Filter on Enter key
        $('#search').on('keypress', function(e) {
            if (e.which === 13) {
                table.draw();
            }
        });
    });
</script>
@endsection
