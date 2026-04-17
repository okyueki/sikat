@extends('layouts.pages-layouts')

@section('pageTitle', 'Inventaris')

@section('content')
<div class="col-xl-12">
    <div class="card custom-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">Daftar Inventaris</h3>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="Mode tampilan">
                    <button type="button" class="btn btn-outline-secondary active" id="view-mode-table" title="Tampilan tabel">
                        <i class="fa fa-list me-1"></i>Tabel
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="view-mode-grid" title="Tampilan galeri foto">
                        <i class="fa fa-th-large me-1"></i>Galeri
                    </button>
                </div>
                <div id="grid-per-page-wrap" class="d-none">
                    <select id="grid-per-page" class="form-control form-control-sm" style="min-width: 7rem;" title="Jumlah kartu per halaman">
                        <option value="12" selected>12 / hal</option>
                        <option value="24">24 / hal</option>
                        <option value="36">36 / hal</option>
                        <option value="48">48 / hal</option>
                    </select>
                </div>
                <a href="{{ route('inventaris.visualisasi') }}" class="btn btn-info btn-sm">
                    <i class="fa fa-chart-bar me-1"></i>Visualisasi Data
                </a>
            </div>
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

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div id="wrap-inventaris-table">
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

            <div id="wrap-inventaris-grid" class="d-none">
                <div id="grid-loading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Memuat…</span></div>
                    <p class="text-muted small mt-2 mb-0">Memuat galeri…</p>
                </div>
                <div id="grid-empty" class="alert alert-light border text-center d-none mb-0">
                    <i class="fa fa-inbox fa-2x text-muted mb-2 d-block"></i>
                    Tidak ada inventaris untuk filter ini.
                </div>
                <div id="inventaris-grid-cards" class="row"></div>
                <nav id="grid-pagination" class="mt-3 d-none" aria-label="Paginasi galeri"></nav>
            </div>
        </div>
    </div>
</div>

<style>
    /* Loading indicator untuk DataTables */
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        padding: 15px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        z-index: 1000 !important;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }
    
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

    /* Galeri thumbnail kotak */
    .inventaris-thumb-card {
        border-radius: 0.5rem;
        overflow: hidden;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
    }
    .inventaris-thumb-card:hover {
        box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    .inventaris-thumb-frame {
        position: relative;
        width: 100%;
        padding-top: 100%;
        background: linear-gradient(145deg, #f1f3f5 0%, #e9ecef 100%);
        overflow: hidden;
    }
    .inventaris-thumb-frame img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .inventaris-thumb-frame.is-empty {
        padding-top: 0;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 7.5rem;
    }
    .inventaris-thumb-card .card-body {
        padding: 0.65rem 0.75rem;
    }
    .inventaris-thumb-card .card-title {
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.25;
        margin-bottom: 0.25rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .inventaris-thumb-meta {
        font-size: 0.7rem;
        color: #6c757d;
        line-height: 1.3;
    }
    .inventaris-thumb-actions .btn {
        padding: 0.2rem 0.45rem;
        font-size: 0.75rem;
        margin: 0.15rem 0.25rem 0.15rem 0;
    }
</style>

<script>
    $(document).ready(function() {
        var gridDataUrl = @json(route('inventaris.grid-data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        function inventarisEscapeHtml(text) {
            if (text == null || text === '') return '';
            return $('<div>').text(String(text)).html();
        }

        function renderGridPagination(res) {
            var $nav = $('#grid-pagination');
            $nav.empty();
            if (res.last_page <= 1) {
                $nav.addClass('d-none');
                return;
            }
            $nav.removeClass('d-none');
            var ul = $('<ul>').addClass('pagination pagination-sm justify-content-center flex-wrap mb-0');
            var prevLi = $('<li>').addClass('page-item' + (res.current_page <= 1 ? ' disabled' : ''));
            prevLi.append($('<a>').addClass('page-link').attr('href', '#').text('«').on('click', function(e) {
                e.preventDefault();
                if (res.current_page > 1) loadGrid(res.current_page - 1);
            }));
            ul.append(prevLi);
            var start = Math.max(1, res.current_page - 2);
            var end = Math.min(res.last_page, res.current_page + 2);
            for (var p = start; p <= end; p++) {
                (function(pageNum) {
                    var li = $('<li>').addClass('page-item' + (pageNum === res.current_page ? ' active' : ''));
                    li.append($('<a>').addClass('page-link').attr('href', '#').text(String(pageNum)).on('click', function(e) {
                        e.preventDefault();
                        loadGrid(pageNum);
                    }));
                    ul.append(li);
                })(p);
            }
            var nextLi = $('<li>').addClass('page-item' + (res.current_page >= res.last_page ? ' disabled' : ''));
            nextLi.append($('<a>').addClass('page-link').attr('href', '#').text('»').on('click', function(e) {
                e.preventDefault();
                if (res.current_page < res.last_page) loadGrid(res.current_page + 1);
            }));
            ul.append(nextLi);
            $nav.append($('<div>').addClass('text-center text-muted small mb-2').text(
                'Menampilkan ' + res.data.length + ' dari ' + res.total + ' item — halaman ' + res.current_page + '/' + res.last_page
            ));
            $nav.append(ul);
        }

        function buildGridCard(item) {
            var u = item.urls;
            var esc = inventarisEscapeHtml;
            var srcAttr = item.thumb_url ? String(item.thumb_url).replace(/"/g, '&quot;') : '';
            var thumbHtml;
            if (item.thumb_url) {
                thumbHtml = '<a href="' + u.show + '" class="d-block text-reset" tabindex="-1">' +
                    '<div class="inventaris-thumb-frame">' +
                    '<img src="' + srcAttr + '" alt="" loading="lazy">' +
                    '</div></a>';
            } else {
                thumbHtml = '<a href="' + u.show + '" class="d-block text-reset" tabindex="-1">' +
                    '<div class="inventaris-thumb-frame is-empty">' +
                    '<i class="fa fa-image fa-2x text-muted"></i>' +
                    '</div></a>';
            }
            var actions =
                '<a href="' + u.show + '" class="btn btn-info btn-sm" title="Detail"><i class="fa fa-eye"></i></a> ' +
                '<a href="' + u.barcode + '" target="_blank" rel="noopener" class="btn btn-primary btn-sm" title="QR"><i class="fa fa-qrcode"></i></a> ' +
                '<a href="' + u.edit + '" class="btn btn-warning btn-sm" title="Edit"><i class="fa fa-edit"></i></a> ' +
                '<form action="' + u.destroy + '" method="POST" class="d-inline" onsubmit="return confirm(\'Yakin ingin menghapus?\');">' +
                '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fa fa-trash"></i></button></form>';
            return (
                '<div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-2">' +
                '<div class="card inventaris-thumb-card h-100">' +
                thumbHtml +
                '<div class="card-body">' +
                '<div class="badge ' + item.badge_class + ' mb-1">' + esc(item.status_barang) + '</div>' +
                '<div class="card-title text-dark">' + esc(item.nama_barang) + '</div>' +
                '<div class="inventaris-thumb-meta">' +
                '<div class="font-weight-bold text-primary small">' + esc(item.no_inventaris) + '</div>' +
                '<div><i class="fa fa-map-marker-alt mr-1"></i>' + esc(item.nama_ruang) + '</div>' +
                '</div>' +
                '<div class="inventaris-thumb-actions mt-2 pt-2 border-top d-flex flex-wrap">' + actions + '</div>' +
                '</div></div></div>'
            );
        }

        function loadGrid(page) {
            $('#grid-loading').removeClass('d-none');
            $('#grid-empty').addClass('d-none');
            $('#inventaris-grid-cards').empty();
            $('#grid-pagination').addClass('d-none').empty();
            $.ajax({
                url: gridDataUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    page: page,
                    per_page: $('#grid-per-page').val(),
                    ruang: $('#ruang').val(),
                    search: $('#search').val()
                },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    $('#grid-loading').addClass('d-none');
                    if (!res.data || !res.data.length) {
                        $('#grid-empty').removeClass('d-none');
                        return;
                    }
                    var html = res.data.map(buildGridCard).join('');
                    $('#inventaris-grid-cards').html(html);
                    renderGridPagination(res);
                },
                error: function() {
                    $('#grid-loading').addClass('d-none');
                    alert('Gagal memuat galeri inventaris.');
                }
            });
        }

        function isGridVisible() {
            return !$('#wrap-inventaris-grid').hasClass('d-none');
        }

        function applyFilters() {
            if (isGridVisible()) {
                loadGrid(1);
            } else {
                table.draw();
            }
        }

        function setViewMode(mode) {
            if (mode === 'grid') {
                $('#wrap-inventaris-table').addClass('d-none');
                $('#wrap-inventaris-grid').removeClass('d-none');
                $('#grid-per-page-wrap').removeClass('d-none');
                $('#view-mode-table').removeClass('active');
                $('#view-mode-grid').addClass('active');
                try { localStorage.setItem('inventaris_view_mode', 'grid'); } catch (e) {}
                loadGrid(1);
            } else {
                $('#wrap-inventaris-grid').addClass('d-none');
                $('#wrap-inventaris-table').removeClass('d-none');
                $('#grid-per-page-wrap').addClass('d-none');
                $('#view-mode-grid').removeClass('active');
                $('#view-mode-table').addClass('active');
                try { localStorage.setItem('inventaris_view_mode', 'table'); } catch (e) {}
                try {
                    table.columns.adjust();
                    if (table.responsive) table.responsive.recalc();
                } catch (e) {}
                table.draw(false);
            }
        }

        $('#view-mode-table').on('click', function() { setViewMode('table'); });
        $('#view-mode-grid').on('click', function() { setViewMode('grid'); });
        $('#grid-per-page').on('change', function() {
            if (isGridVisible()) loadGrid(1);
        });

        // Inisialisasi Select2 dengan responsive
        $('.js-example-basic-single').select2({
            width: '100%',
            dropdownAutoWidth: true
        });

        // Inisialisasi DataTables dengan responsive dan optimasi performa
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
                processing: '<div class="spinner-border spinner-border-sm me-2" role="status"><span class="visually-hidden">Loading...</span></div> Memproses data...',
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
                url: @json(route('inventaris.index')),
                type: 'GET',
                data: function(d) {
                    d.ruang = $('#ruang').val();
                    d.search = $('#search').val();
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
                }
            },
            // Optimasi performa
            deferRender: true, // Render hanya baris yang terlihat
            stateSave: false, // Disable state save untuk performa
            orderMulti: false, // Disable multi-column ordering untuk performa
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
            },
            // Optimasi: Cache hasil untuk performa lebih baik
            initComplete: function() {
                console.log('DataTables initialized successfully');
            }
        });

        // Event listener untuk filter dengan debounce untuk performa
        var searchTimeout;
        $('#filter-button').click(function() {
            applyFilters();
        });

        $('#search').on('keyup', function(e) {
            clearTimeout(searchTimeout);
            if (e.which === 13) {
                applyFilters();
            } else {
                searchTimeout = setTimeout(function() {
                    applyFilters();
                }, 500);
            }
        });

        $('#ruang').on('change', function() {
            applyFilters();
        });

        try {
            if (localStorage.getItem('inventaris_view_mode') === 'grid') {
                setViewMode('grid');
            }
        } catch (e) {}
    });
</script>
@endsection
