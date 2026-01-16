@extends('layouts.pages-layouts')

@section('pageTitle', 'Visualisasi Inventaris')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-md-flex d-block align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0">Visualisasi Data Inventaris</h4>
                    <p class="mb-0 text-muted">Analisis dan visualisasi inventaris berdasarkan ruangan, tanggal pengadaan, dan kondisi</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i>Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-filter me-2"></i>Filter Data
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('inventaris.visualisasi') }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Ruang</label>
                                <select name="ruang" id="ruang" class="form-control js-example-basic-single">
                                    <option value="">-- Semua Ruang --</option>
                                    @foreach($ruang as $r)
                                        <option value="{{ $r->id_ruang }}" {{ $idRuang == $r->id_ruang ? 'selected' : '' }}>
                                            {{ $r->nama_ruang }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Awal</label>
                                <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Akhir</label>
                                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fa fa-search me-1"></i>Filter
                                    </button>
                                    <a href="{{ route('inventaris.visualisasi') }}" class="btn btn-secondary">
                                        <i class="fa fa-refresh"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Inventaris</p>
                            <h3 class="mb-0">{{ number_format($statistik['total_inventaris']) }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-primary-gradient text-fixed-white rounded">
                                <i class="fa fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Nilai Inventaris</p>
                            <h3 class="mb-0">
                                @php
                                    if (!function_exists('formatRupiah')) {
                                        $formatPath = app_path('Helpers/FormatHelper.php');
                                        if (file_exists($formatPath)) {
                                            require_once $formatPath;
                                        }
                                    }
                                @endphp
                                {{ function_exists('formatRupiah') ? formatRupiah($statistik['total_nilai']) : 'Rp ' . number_format($statistik['total_nilai'], 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-success-gradient text-fixed-white rounded">
                                <i class="fa fa-money"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Rata-rata Nilai</p>
                            <h3 class="mb-0">
                                {{ function_exists('formatRupiah') ? formatRupiah($statistik['rata_rata_nilai']) : 'Rp ' . number_format($statistik['rata_rata_nilai'], 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-info-gradient text-fixed-white rounded">
                                <i class="fa fa-calculator"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Ruang Terbanyak</p>
                            <h3 class="mb-0" style="font-size: 1.1rem;">
                                {{ $statistik['ruang_terbanyak'] ? $statistik['ruang_terbanyak']->nama_ruang : '-' }}
                            </h3>
                            @if($statistik['ruang_terbanyak'])
                                <small class="text-muted">{{ $statistik['ruang_terbanyak']->jumlah }} inventaris</small>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-warning-gradient text-fixed-white rounded">
                                <i class="fa fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Distribusi per Ruangan & Kondisi -->
    <div class="row g-3 mb-4">
        <!-- Grafik Distribusi per Ruangan (Pie Chart) -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Distribusi Inventaris per Ruangan</h5>
                </div>
                <div class="card-body">
                    <div id="chart-distribusi-ruang" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>

        <!-- Grafik Kondisi/Status Barang (Donut Chart) -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Distribusi Kondisi Barang</h5>
                </div>
                <div class="card-body">
                    <div id="chart-kondisi" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Bar Chart & Trend -->
    <div class="row g-3 mb-4">
        <!-- Grafik Jumlah Inventaris per Ruangan (Bar Chart) -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Jumlah Inventaris per Ruangan</h5>
                </div>
                <div class="card-body">
                    <div id="chart-bar-ruang" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>

        <!-- Top 5 Ruangan -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top 5 Ruangan</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($topRuang->take(5) as $index => $ruangItem)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                                <span>{{ $ruangItem->nama_ruang }}</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $ruangItem->jumlah }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3: Trend Pengadaan & Nilai -->
    <div class="row g-3 mb-4">
        <!-- Trend Pengadaan (Line Chart) -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trend Pengadaan Inventaris (Bulanan)</h5>
                </div>
                <div class="card-body">
                    <div id="chart-trend-pengadaan" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Nilai Inventaris per Ruangan (Bar Chart) -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Nilai Inventaris per Ruangan</h5>
                </div>
                <div class="card-body">
                    <div id="chart-nilai-ruang" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }
        
        #chart-distribusi-ruang,
        #chart-kondisi,
        #chart-bar-ruang,
        #chart-trend-pengadaan,
        #chart-nilai-ruang {
            min-height: 300px !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan ApexCharts sudah dimuat
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts library not loaded');
        return;
    }

    // Data untuk grafik
    const dataPerRuang = @json($dataPerRuang);
    const dataKondisi = @json($dataKondisi);
    const dataTrendPengadaan = @json($dataTrendPengadaan);
    const nilaiPerRuang = @json($nilaiPerRuang);

    // 1. Grafik Distribusi per Ruangan (Pie Chart)
    const optionsDistribusiRuang = {
        series: dataPerRuang.map(item => item.jumlah),
        chart: {
            type: 'pie',
            height: 400
        },
        labels: dataPerRuang.map(item => item.nama_ruang),
        colors: ['#0162e8', '#0db2de', '#00d9ff', '#10b759', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'],
        legend: {
            position: 'bottom',
            horizontalAlign: 'center'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return opts.w.config.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)';
            }
        },
        tooltip: {
            y: {
                formatter: function(value, { seriesIndex, w }) {
                    return value + ' inventaris';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    const chartDistribusiRuang = new ApexCharts(document.querySelector("#chart-distribusi-ruang"), optionsDistribusiRuang);
    chartDistribusiRuang.render();

    // 2. Grafik Kondisi/Status (Donut Chart)
    const statusColors = {
        'Ada': '#10b759',
        'Rusak': '#ef4444',
        'Hilang': '#f59e0b',
        'Perbaikan': '#0db2de',
        'Dipinjam': '#8b5cf6',
        '-': '#6b7280'
    };

    const optionsKondisi = {
        series: dataKondisi.map(item => item.jumlah),
        chart: {
            type: 'donut',
            height: 400
        },
        labels: dataKondisi.map(item => item.status_barang),
        colors: dataKondisi.map(item => statusColors[item.status_barang] || '#6b7280'),
        legend: {
            position: 'bottom',
            horizontalAlign: 'center'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return opts.w.config.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)';
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value + ' inventaris';
                }
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function() {
                                return dataKondisi.reduce((sum, item) => sum + item.jumlah, 0);
                            }
                        }
                    }
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                }
            }
        }]
    };

    const chartKondisi = new ApexCharts(document.querySelector("#chart-kondisi"), optionsKondisi);
    chartKondisi.render();

    // 3. Grafik Bar Chart Jumlah per Ruangan
    const optionsBarRuang = {
        series: [{
            name: 'Jumlah Inventaris',
            data: dataPerRuang.map(item => item.jumlah)
        }],
        chart: {
            type: 'bar',
            height: 400,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: true
        },
        xaxis: {
            categories: dataPerRuang.map(item => item.nama_ruang),
            labels: {
                rotate: -45,
                rotateAlways: true,
                style: {
                    fontSize: '11px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Jumlah Inventaris'
            }
        },
        colors: ['#0162e8'],
        tooltip: {
            y: {
                formatter: function(value) {
                    return value + ' inventaris';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                },
                plotOptions: {
                    bar: {
                        horizontal: true
                    }
                }
            }
        }]
    };

    const chartBarRuang = new ApexCharts(document.querySelector("#chart-bar-ruang"), optionsBarRuang);
    chartBarRuang.render();

    // 4. Trend Pengadaan (Line Chart)
    const trendLabels = dataTrendPengadaan.map(item => {
        const date = new Date(item.bulan + '-01');
        return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short' });
    });

    const optionsTrendPengadaan = {
        series: [{
            name: 'Jumlah Pengadaan',
            data: dataTrendPengadaan.map(item => item.jumlah)
        }],
        chart: {
            type: 'line',
            height: 350,
            zoom: {
                enabled: true
            },
            toolbar: {
                show: true
            }
        },
        dataLabels: {
            enabled: true
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: trendLabels
        },
        yaxis: {
            title: {
                text: 'Jumlah Inventaris'
            }
        },
        colors: ['#10b759'],
        markers: {
            size: 5,
            hover: {
                size: 7
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value + ' inventaris';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                }
            }
        }]
    };

    const chartTrendPengadaan = new ApexCharts(document.querySelector("#chart-trend-pengadaan"), optionsTrendPengadaan);
    chartTrendPengadaan.render();

    // 5. Nilai Inventaris per Ruangan (Bar Chart)
    const optionsNilaiRuang = {
        series: [{
            name: 'Nilai Inventaris',
            data: nilaiPerRuang.map(item => parseFloat(item.total_nilai))
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return 'Rp ' + (val / 1000000).toFixed(1) + 'M';
            },
            offsetY: -20,
            style: {
                fontSize: '11px',
                colors: ['#304758']
            }
        },
        xaxis: {
            categories: nilaiPerRuang.map(item => item.nama_ruang),
            labels: {
                rotate: -45,
                rotateAlways: true,
                style: {
                    fontSize: '11px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Nilai (Rupiah)'
            },
            labels: {
                formatter: function(val) {
                    return 'Rp ' + (val / 1000000).toFixed(1) + 'M';
                }
            }
        },
        colors: ['#f59e0b'],
        tooltip: {
            y: {
                formatter: function(value) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                },
                plotOptions: {
                    bar: {
                        horizontal: true
                    }
                }
            }
        }]
    };

    const chartNilaiRuang = new ApexCharts(document.querySelector("#chart-nilai-ruang"), optionsNilaiRuang);
    chartNilaiRuang.render();

    // Inisialisasi Select2 dengan delay untuk menghindari konflik dengan SimpleBar
    setTimeout(function() {
        // Destroy Select2 jika sudah ada instance sebelumnya
        if ($('#ruang').hasClass('select2-hidden-accessible')) {
            $('#ruang').select2('destroy');
        }
        
        // Inisialisasi Select2
        $('#ruang').select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: '-- Semua Ruang --',
            allowClear: true,
            language: {
                noResults: function() {
                    return "Tidak ada data";
                },
                searching: function() {
                    return "Mencari...";
                }
            }
        });
    }, 100);
});
</script>
@endsection
