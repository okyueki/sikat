@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <!-- Page Header -->
        <div class="col-12">
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                <div>
                    <h4 class="mb-0">Assalamualaikum, @if(Auth::check())
                        {{ Auth::user()->name }}
                    @endif</h4>
                    <p class="mb-0 text-muted">{{ $presensiMessage }}</p>
                </div>
                <div class="main-dashboard-header-right d-flex gap-3">
                    <div>
                        <label class="fs-13 text-muted">TOTAL PEGAWAI RS</label>
                        <h5 class="mb-0 fw-semibold">{{ $jumlahPegawai->sum() }} orang</h5>
                    </div>
                    <div>
                        <label class="fs-13 text-muted">JUMLAH DOKTER</label>
                        <h5 class="mb-0 fw-semibold">{{ $jumlahDokter }} orang</h5>
                    </div>
                    <div>
                        <label class="fs-13 text-muted">PASIEN HARI INI</label>
                        <h5 class="mb-0 fw-semibold">{{ $jumlahPasienHariIni }} orang</h5>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
    </div>

    <!-- Agenda Terundang Shortcut -->
    @if(isset($agendaTerundang) && $agendaTerundang->count() > 0)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Agenda Saya ({{ $agendaTerundang->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($agendaTerundang as $agenda)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border h-100 {{ $agenda->status_class == 'success' ? 'border-success' : ($agenda->status_class == 'info' ? 'border-info' : 'border-secondary') }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0 text-truncate" style="max-width: 70%;" title="{{ $agenda->judul }}">
                                            {{ $agenda->judul }}
                                        </h6>
                                        <span class="badge bg-{{ $agenda->status_class }} ms-2">{{ $agenda->status_label }}</span>
                                    </div>
                                    <p class="card-text mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>{{ $agenda->waktu_info }}<br>
                                            @if($agenda->tempat)
                                                <i class="fas fa-map-marker-alt me-1"></i>{{ $agenda->tempat }}<br>
                                            @endif
                                            @if($agenda->pimpinan)
                                                <i class="fas fa-user-tie me-1"></i>{{ $agenda->pimpinan->nama }}
                                            @endif
                                        </small>
                                    </p>
                                    <div class="d-flex gap-2 mt-2">
                                        <a href="{{ route('acara_show', $agenda->id) }}" class="btn btn-sm btn-info flex-fill">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </a>
                                        @if(!$agenda->sudah_absen && ($agenda->status_class == 'info' || $agenda->status_class == 'success'))
                                            <a href="{{ route('absensi.scan', $agenda->id) }}" class="btn btn-sm btn-success flex-fill">
                                                <i class="fas fa-qrcode me-1"></i> Scan
                                            </a>
                                        @elseif($agenda->sudah_absen)
                                            <button class="btn btn-sm btn-secondary flex-fill" disabled>
                                                <i class="fas fa-check me-1"></i> Sudah Absen
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($agendaTerundang->count() > 5)
                    <div class="text-center mt-3">
                        <a href="{{ route('absensi_agenda.index') }}" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i> Lihat Semua Agenda
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Presensi Table -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-table">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th>Shift</th>
                                <th>Jam Datang</th>
                                <th>Status</th>
                                <th>Keterlambatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($presensiUser as $presensi)
                            <tr>
                                <td>{{ $presensi->shift ?? '-' }}</td>
                                <td>{{ $presensi->jam_datang ? \Carbon\Carbon::parse($presensi->jam_datang)->format('H:i') : '-' }}</td>
                                <td>{{ $presensi->status ?? '-' }}</td>
                                <td>{{ $presensi->keterlambatan ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data presensi</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card overflow-hidden sales-card bg-primary-gradient">
                    <div class="px-3 pt-3 pb-2 pt-0">
                        <div>
                            <h6 class="mb-3 fs-12 text-fixed-white">Cuti Tahunan</h6>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div>
                                    <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ 12 - $totalHari }} Hari</h4>
                                    <p class="mb-0 fs-12 text-fixed-white op-7">Sisa Cuti Tahunan</p>
                                </div>
                                <span class="float-end my-auto ms-auto">
                                    <i class="fas fa-user-friends text-fixed-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-3">
         <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                <div class="card overflow-hidden sales-card bg-warning-gradient">
                    <div class="px-3 pt-3 pb-2 pt-0">
                        <div>
                            <h6 class="mb-3 fs-12 text-fixed-white">JUMLAH PASIEN RAWAT JALAN HARI INI</h6>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahPasienHariIni }} orang</h4>
                            <p class="mb-0 fs-12 text-fixed-white op-7">Pasien Diperiksa</p>
                        </div>
                        <span class="ms-auto">
                            @if($pertumbuhanPasien > 0)
                                <i class="fas fa-arrow-circle-up text-fixed-white"></i>
                                <span class="text-fixed-white op-7"> +{{ $pertumbuhanPasien }} dari kemarin</span>
                            @elseif($pertumbuhanPasien < 0)
                                <i class="fas fa-arrow-circle-down text-fixed-white"></i>
                                <span class="text-fixed-white op-7"> {{ $pertumbuhanPasien }} dari kemarin</span>
                            @else
                                <i class="fas fa-minus-circle text-fixed-white"></i>
                                <span class="text-fixed-white op-7"> Sama dengan kemarin</span>
                            @endif
                        </span>
                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                <div class="card overflow-hidden sales-card bg-primary-gradient">
                    <div class="px-3 pt-3 pb-2 pt-0">
                        <div>
                            <h6 class="mb-3 fs-12 text-fixed-white">TOTAL PEGAWAI AKTIF</h6>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div>
                                    <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahPegawai->sum() }} orang</h4>
                                    <p class="mb-0 fs-12 text-fixed-white op-7">Pegawai aktif saat ini</p>
                                </div>
                                <span class="float-end my-auto ms-auto">
                                    <i class="fas fa-user-friends text-fixed-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                <div class="card overflow-hidden sales-card bg-danger-gradient">
                    <div class="px-3 pt-3 pb-2 pt-0">
                        <div>
                            <h6 class="mb-3 fs-12 text-fixed-white">JUMLAH PASIEN RAWAT INAP</h6>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div>
                                    <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahPasienRawatInap }} orang</h4>
                                    <p class="mb-0 fs-12 text-fixed-white op-7">Pasien rawat inap dengan lama < 6 hari</p>
                                </div>
                                <span class="float-end my-auto ms-auto">
                                    <i class="fas fa-procedures text-fixed-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                <div class="card overflow-hidden sales-card bg-success-gradient">
                    <div class="px-3 pt-3 pb-2 pt-0">
                        <div>
                            <h6 class="mb-3 fs-12 text-fixed-white">JUMLAH PASIEN IGD HARI INI</h6>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div>
                                    <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahPasienIGD }} orang</h4>
                                    <p class="mb-0 fs-12 text-fixed-white op-7">Pasien terdaftar di IGD</p>
                                </div>
                                <span class="float-end my-auto ms-auto">
                                    <i class="fas fa-hospital-alt text-fixed-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ranking Tim Paling Rajin & Paling Sering Terlambat -->
    <div class="row g-3 mb-3">
        <!-- Tim Paling Rajin -->
        <div class="col-xl-6 col-md-12">
            <div class="card custom-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Top 10 Tim Paling Rajin (Bulan Ini)
                    </h5>
                    <small class="text-white-50">Ranking berdasarkan: (Persentase Kehadiran × Durasi Kerja) - Keterlambatan | Tidak pernah terlambat</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="50">Rank</th>
                                    <th>Nama Pegawai</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Wajib</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">% Hadir</th>
                                    <th class="text-center">Durasi Kerja</th>
                                    <th class="text-center">Terlambat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timPalingRajin as $index => $tim)
                                <tr>
                                    <td>
                                        @if($index == 0)
                                            <span class="badge bg-warning text-dark">🥇</span>
                                        @elseif($index == 1)
                                            <span class="badge bg-secondary">🥈</span>
                                        @elseif($index == 2)
                                            <span class="badge bg-danger">🥉</span>
                                        @else
                                            <span class="badge bg-light text-dark">#{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $tim['nama'] }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $tim['departemen'] ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $tim['wajib_masuk'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $tim['kehadiran'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $tim['persen_kehadiran'] >= 100 ? 'success' : ($tim['persen_kehadiran'] >= 80 ? 'warning' : 'danger') }}">
                                            {{ $tim['persen_kehadiran'] }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-primary">
                                            <i class="fas fa-clock me-1"></i>{{ $tim['total_durasi_formatted'] }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>0
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Belum ada data ranking
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tim Paling Sering Terlambat -->
        <div class="col-xl-6 col-md-12">
            <div class="card custom-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Top 10 Tim Paling Sering Terlambat (Bulan Ini)
                    </h5>
                    <small class="text-white-50">Ranking berdasarkan: Total Durasi Keterlambatan Tertinggi</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="50">Rank</th>
                                    <th>Nama Pegawai</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Wajib</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Terlambat</th>
                                    <th class="text-center">Total Telat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timPalingSeringTerlambat as $index => $tim)
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">#{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $tim['nama'] }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $tim['departemen'] ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $tim['wajib_masuk'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $tim['kehadiran'] < $tim['wajib_masuk'] ? 'warning' : 'success' }}">
                                            {{ $tim['kehadiran'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $tim['jumlah_terlambat'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-danger fw-bold">
                                            <i class="fas fa-clock me-1"></i>{{ $tim['total_keterlambatan_formatted'] }}
                                        </small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Belum ada data ranking
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Pegawai -->
    <div class="row g-3">
          <div class="col-xl-4 col-md-12 col-lg-6">
        <div class="card">
            <div class="card-header pb-1">
                <h3 class="card-title mb-2">Terlambat Pegawai Hari Ini</h3>
                <p class="fs-12 mb-0 text-muted">Daftar pegawai yang terlambat berdasarkan keterlambatan terbesar</p>
            </div>
            <div class="product-timeline card-body pt-2 mt-1">
                <ul class="timeline-1 mb-0">
                    @foreach($topTerlambat as $pegawai)
                    <li class="mt-0">
                        <i class="fe fe-user bg-primary-gradient text-fixed-white product-icon"></i>
                        <span class="fw-medium mb-4 fs-14">{{ $pegawai->pegawai->nama }}</span>
                        <a href="javascript:void(0);" class="float-end fs-11 text-muted">
                            {{ $pegawai->jam_datang->diffForHumans() }}  <!-- Menggunakan diffForHumans() dengan Carbon -->
                        </a>
                        <p class="mb-0 text-muted fs-12">
                            {{ $pegawai->status }} - Terlambat: {{ $pegawai->keterlambatan }} - Shift: {{ $pegawai->shift }}
                        </p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    
            <div class="col-xl-4 col-md-12 col-lg-6">
        <div class="card">
            <div class="card-header pb-1">
                <h3 class="card-title mb-2">Top 10 Pegawai Rajin (30 Hari Terakhir)</h3>
                <p class="fs-12 mb-0 text-muted">Pegawai yang paling sering mengisi pemeriksaan rawat jalan</p>
            </div>
            <div class="product-timeline card-body pt-2 mt-1">
                <ul class="timeline-1 mb-0">
                    @foreach($topPegawaiRajin as $pegawai)
                    <li class="mt-0">
                        <i class="fe fe-user bg-primary-gradient text-fixed-white product-icon"></i>
                        <span class="fw-medium mb-4 fs-14">{{ $pegawai->nama_pegawai }}</span>
                        <p class="mb-0 text-muted fs-12">Jumlah Entri: {{ $pegawai->jumlah_entri }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-12 col-lg-6">
        <div class="card">
            <div class="card-header pb-1">
                <h3 class="card-title mb-2">Pegawai Ulang Tahun Dalam 10 Hari Kedepan</h3>
                <p class="fs-12 mb-0 text-muted">Daftar pegawai yang akan berulang tahun dalam waktu dekat</p>
            </div>
            <div class="product-timeline card-body pt-2 mt-1">
                <ul class="timeline-1 mb-0">
                    @if($pegawaiUlangTahun->isEmpty())
                        <li class="mt-0">
                            <i class="fe fe-calendar bg-danger-gradient text-fixed-white product-icon"></i>
                            <span class="fw-medium mb-4 fs-14">Tidak ada pegawai yang berulang tahun dalam waktu dekat</span>
                        </li>
                    @else
                        @foreach($pegawaiUlangTahun as $pegawai)
                        <li class="mt-0">
                            <i class="bi bi-emoji-smile-fill bg-danger-gradient text-fixed-white product-icon"></i>
                            <span class="fw-medium mb-4 fs-14">{{ $pegawai->nama }}</span>
                            <a href="javascript:void(0);" class="float-end fs-11 text-muted">{{ $pegawai->status }}</a>
                            <p class="mb-0 text-muted fs-12">Tanggal Lahir: {{ \Carbon\Carbon::parse($pegawai->tgl_lahir)->format('d F') }}</p>
                        </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>
    </div>

    <!-- Statistik Grafik -->
    <div class="row g-3">
       <!-- Jumlah Pegawai per Departemen -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Jumlah Pegawai per Unit / Departemen</div>
            </div>
            <div class="card-body">
                <h4>Total Pegawai: {{ $jumlahPegawai->sum() }} orang</h4> <!-- Display total pegawai -->
                <div id="chart-departemen" style="min-height: 365px;"></div> <!-- Tempat untuk grafik -->
            </div>
        </div>
    </div>

    <!-- Jumlah Pegawai per Bidang -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Distribusi Pegawai per Bidang</div>
            </div>
            <div class="card-body">
                <h4>Total Pegawai: {{ $jumlahPerBidang->sum() }} orang</h4> <!-- Display total pegawai per bidang -->
                <div id="chart-bidang" style="min-height: 365px;"></div> <!-- Tempat untuk grafik -->
            </div>
        </div>
    </div>
    </div>
</div>

   

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan ApexCharts sudah dimuat
    if (typeof ApexCharts === 'undefined') {
        console.warn('ApexCharts library not loaded');
        return;
    }

    // Data for Treemap Chart for Departemen
    var optionsTreemapDepartemen = {
        series: [{
            data: {!! json_encode($departemen) !!}
        }],
        chart: {
            type: 'treemap',
            height: 350
        },
        title: {
            text: 'Jumlah Pegawai per Departemen'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                try {
                    if (opts && opts.w && opts.w.config && opts.w.config.series && opts.w.config.series[0] && opts.w.config.series[0].data && opts.dataPointIndex !== undefined) {
                        var departmentName = opts.w.config.series[0].data[opts.dataPointIndex].x;
                        var label = opts.w.config.series[0].data[opts.dataPointIndex].label;
                        // Gunakan '\n' untuk memecah baris
                        return departmentName + "\n" + (label ? label.replace("(", "\n(") : '');
                    }
                    return '';
                } catch (e) {
                    console.warn('Error in formatter:', e);
                    return '';
                }
            },
            style: {
                fontSize: '12px',
                colors: ['#000'],
                fontWeight: 'bold'
            }
        }
    };

    // Render Chart for Departemen
    var chartDepartemenEl = document.querySelector("#chart-departemen");
    if (chartDepartemenEl) {
        try {
            var chartTreemapDepartemen = new ApexCharts(chartDepartemenEl, optionsTreemapDepartemen);
            chartTreemapDepartemen.render();
        } catch (e) {
            console.error('Error rendering departemen chart:', e);
        }
    }

    // Data for Treemap Chart for Bidang
    var optionsTreemapBidang = {
        series: [{
            data: {!! json_encode($bidang) !!}
        }],
        chart: {
            type: 'treemap',
            height: 350
        },
        title: {
            text: 'Distribusi Pegawai per Bidang'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                try {
                    if (opts && opts.w && opts.w.config && opts.w.config.series && opts.w.config.series[0] && opts.w.config.series[0].data && opts.dataPointIndex !== undefined) {
                        var bidangName = opts.w.config.series[0].data[opts.dataPointIndex].x;
                        var label = opts.w.config.series[0].data[opts.dataPointIndex].label;
                        // Gunakan '\n' untuk memecah baris
                        return bidangName + "\n" + (label ? label.replace("(", "\n(") : '');
                    }
                    return '';
                } catch (e) {
                    console.warn('Error in formatter:', e);
                    return '';
                }
            },
            style: {
                fontSize: '12px',
                colors: ['#000'],
                fontWeight: 'bold'
            }
        }
    };

    // Render Chart for Bidang
    var chartBidangEl = document.querySelector("#chart-bidang");
    if (chartBidangEl) {
        try {
            var chartTreemapBidang = new ApexCharts(chartBidangEl, optionsTreemapBidang);
            chartTreemapBidang.render();
        } catch (e) {
            console.error('Error rendering bidang chart:', e);
        }
    }
});
</script>

@endsection