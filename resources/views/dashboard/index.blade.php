@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Dashboard')

@section('content')
<div class="container-fluid">
    @if(isset($dashboardErrorMessage) && $dashboardErrorMessage)
    <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ $dashboardErrorMessage }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
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
                        <h5 class="mb-0 fw-semibold">{{ $totalPegawaiAktif }} orang</h5>
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
                                        @if($agenda->sudah_absen)
                                            <button class="btn btn-sm btn-secondary flex-fill" disabled>
                                                <i class="fas fa-check me-1"></i> Sudah Absen
                                            </button>
                                        @elseif($agenda->status_kehadiran_agenda ?? null)
                                            <button class="btn btn-sm btn-outline-secondary flex-fill" disabled title="Status tercatat (bukan dari scan)">
                                                @if($agenda->status_kehadiran_agenda === 'tidak_hadir')
                                                    <i class="fas fa-user-times me-1"></i> Tidak hadir
                                                @elseif($agenda->status_kehadiran_agenda === 'ijin')
                                                    <i class="fas fa-file-signature me-1"></i> Ijin
                                                @elseif($agenda->status_kehadiran_agenda === 'cuti')
                                                    <i class="fas fa-umbrella-beach me-1"></i> Cuti
                                                @elseif($agenda->status_kehadiran_agenda === 'sakit')
                                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                                @else
                                                    <i class="fas fa-clipboard-check me-1"></i> {{ ucfirst(str_replace('_', ' ', $agenda->status_kehadiran_agenda)) }}
                                                @endif
                                            </button>
                                        @elseif($agenda->status_class == 'info' || $agenda->status_class == 'success')
                                            <a href="{{ route('absensi.scan', $agenda->id) }}" class="btn btn-sm btn-success flex-fill">
                                                <i class="fas fa-qrcode me-1"></i> Scan
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($agendaTerundang->count() > 5)
                    <div class="text-center mt-3">
                        @can('absensi_agenda.access')
                        <a href="{{ route('absensi_agenda.index') }}" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i> Lihat Semua Agenda
                        </a>
                        @endcan
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Ulang Tahun Hari Ini -->
    @if(isset($pegawaiUlangTahunHariIni) && $pegawaiUlangTahunHariIni->count() > 0)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card custom-card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-birthday-cake me-2"></i>Ulang Tahun Hari Ini ({{ $pegawaiUlangTahunHariIni->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @foreach($pegawaiUlangTahunHariIni as $p)
                        <span class="badge bg-light text-dark border border-warning px-3 py-2">
                            <i class="fas fa-gift me-1"></i>{{ $p->nama }}
                            @if($p->departemen)
                                <small class="text-muted">({{ $p->departemen }})</small>
                            @endif
                        </span>
                        @endforeach
                    </div>
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

    <!-- Surat Menyurat -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <h5 class="mb-0 text-muted"><i class="fas fa-envelope-open-text me-2"></i>Surat Menyurat</h5>
        </div>
        @foreach($suratMenyuratCards as $card)
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
            <a href="{{ route($card['route']) }}" class="text-decoration-none d-block h-100 dashboard-surat-card-link">
                <div class="card overflow-hidden sales-card {{ $card['gradient'] }} h-100 mb-0">
                    <div class="px-3 pt-3 pb-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="mb-2 fs-12 text-fixed-white text-uppercase">{{ $card['label'] }}</h6>
                                <h4 class="fs-24 fw-bold mb-1 text-fixed-white">{{ number_format($card['count']) }}</h4>
                                <p class="mb-0 fs-12 text-fixed-white op-7">{{ $card['subtitle'] }}</p>
                            </div>
                            <span class="dashboard-surat-card-icon">
                                <i class="fas {{ $card['icon'] }} text-fixed-white"></i>
                            </span>
                        </div>
                        <p class="mb-0 mt-2 fs-11 text-fixed-white op-8">
                            <i class="fas fa-arrow-right me-1"></i>Klik untuk buka menu
                        </p>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <!-- Statistik Surat -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <h5 class="mb-0 text-muted"><i class="fas fa-chart-pie me-2"></i>Statistik Surat</h5>
            <p class="small text-muted mb-0">Klasifikasi: semua surat tercatat. Departemen: surat keluar/memo berdasarkan unit kerja pengirim.</p>
        </div>
        <div class="col-lg-5">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Jumlah per Klasifikasi</h6>
                </div>
                <div class="card-body">
                    @if(count($suratKlasifikasiChart ?? []) > 0)
                        <div id="chart-surat-klasifikasi" style="min-height: 360px;"></div>
                    @else
                        <p class="text-muted text-center mb-0 py-5">Belum ada data surat untuk ditampilkan.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Jumlah per Departemen Pengirim</h6>
                </div>
                <div class="card-body">
                    @if(count($suratDepartemenChart ?? []) > 0)
                        <div id="chart-surat-departemen" style="min-height: 360px;"></div>
                    @else
                        <p class="text-muted text-center mb-0 py-5">Belum ada surat dengan pengirim internal.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-surat-card-link .card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dashboard-surat-card-link:hover .card {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
.dashboard-surat-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    const klasifikasiData = @json($suratKlasifikasiChart ?? []);
    const departemenData = @json($suratDepartemenChart ?? []);
    const chartColors = ['#0162e8', '#10b759', '#f59e0b', '#ef4444', '#8b5cf6', '#0db2de', '#ec4899', '#14b8a6', '#6366f1', '#f97316'];

    if (klasifikasiData.length && document.querySelector('#chart-surat-klasifikasi')) {
        new ApexCharts(document.querySelector('#chart-surat-klasifikasi'), {
            series: klasifikasiData.map(function (item) { return item.total; }),
            chart: { type: 'pie', height: 360 },
            labels: klasifikasiData.map(function (item) { return item.label; }),
            colors: chartColors,
            legend: { position: 'bottom' },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)';
                }
            },
            tooltip: {
                y: { formatter: function (value) { return value + ' surat'; } }
            }
        }).render();
    }

    if (departemenData.length && document.querySelector('#chart-surat-departemen')) {
        new ApexCharts(document.querySelector('#chart-surat-departemen'), {
            series: [{ name: 'Jumlah surat', data: departemenData.map(function (item) { return item.total; }) }],
            chart: { type: 'bar', height: 360, toolbar: { show: false } },
            plotOptions: {
                bar: { borderRadius: 4, columnWidth: '55%', distributed: true }
            },
            colors: chartColors,
            dataLabels: { enabled: true },
            legend: { show: false },
            xaxis: {
                categories: departemenData.map(function (item) { return item.label; }),
                labels: { rotate: -35, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: {
                labels: { formatter: function (v) { return Math.floor(v) === v ? v : ''; } },
                title: { text: 'Jumlah surat' }
            },
            tooltip: {
                y: { formatter: function (value) { return value + ' surat'; } }
            }
        }).render();
    }
});
</script>
@endpush

@endsection