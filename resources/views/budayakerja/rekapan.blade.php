@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekapan Budaya Kerja')

@section('content')
<div class="container-fluid">
    <!-- Header dengan Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Rekapan Budaya Kerja Bulanan
                    </h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('budayakerja.rekapan') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="bulan" class="form-label">Bulan</label>
                            <select name="bulan" id="bulan" class="form-control">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $bulan == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null, $i, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tahun" class="form-label">Tahun</label>
                            <select name="tahun" id="tahun" class="form-control">
                                @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                    <option value="{{ $year }}" {{ $tahun == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Overview -->
    @php
        $totalPenilaianBulan = $rekapanPegawai->sum('total_penilaian');
        $totalNilaiBulan = $rekapanPegawai->sum('total_nilai');
        $rataRataUmum = $totalPenilaianBulan > 0 ? round($totalNilaiBulan / $totalPenilaianBulan, 2) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Penilaian</h6>
                    <h3 class="mb-0">{{ $totalPenilaianBulan }}</h3>
                    <small class="text-white-50">Penilaian Bulan Ini</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Rata-rata Nilai</h6>
                    <h3 class="mb-0">{{ $rataRataUmum }}/11</h3>
                    <small class="text-white-50">Dari Total 11 Item</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Pegawai Terbaik</h6>
                    <h3 class="mb-0">{{ $rankingTertinggi->first()['nama'] ?? '-' }}</h3>
                    <small class="opacity-75">Nilai: {{ $rankingTertinggi->first()['rata_rata_nilai'] ?? 0 }}/11</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Perlu Perhatian</h6>
                    <h3 class="mb-0">{{ $rankingTerendah->first()['nama'] ?? '-' }}</h3>
                    <small class="text-white-50">Nilai: {{ $rankingTerendah->first()['rata_rata_nilai'] ?? 0 }}/11</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Ranking Tertinggi dan Terendah -->
    <div class="row g-3 mb-4">
        <!-- Ranking Tertinggi -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Top 20 Pegawai dengan Nilai Tertinggi
                    </h5>
                    <small class="text-white-50">Berdasarkan Akumulasi Total Nilai Bulan {{ \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }} | Hanya pegawai dengan rata-rata perfect 11/11</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">Rank</th>
                                    <th>Nama Pegawai</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-center">Tertinggi</th>
                                    <th class="text-center">Terendah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rankingTertinggi as $index => $pegawai)
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
                                        <strong>{{ $pegawai['nama'] }}</strong>
                                        <br><small class="text-muted">{{ $pegawai['nik'] }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $pegawai['departemen'] ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info" title="Jumlah kali dinilai dalam 1 bulan">{{ $pegawai['total_penilaian'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fw-bold">{{ $pegawai['total_nilai'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $pegawai['rata_rata_nilai'] }}/11</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $pegawai['nilai_tertinggi'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $pegawai['nilai_terendah'] < 8 ? 'danger' : 'warning' }}">{{ $pegawai['nilai_terendah'] }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Belum ada data penilaian
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking Terendah -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>20 Pegawai dengan Nilai Terendah
                    </h5>
                    <small class="text-white-50">Perlu Perhatian Khusus</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">Rank</th>
                                    <th>Nama Pegawai</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Jumlah Penilaian</th>
                                    <th class="text-center">Total Nilai</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-center">Tertinggi</th>
                                    <th class="text-center">Terendah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rankingTerendah as $index => $pegawai)
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">#{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $pegawai['nama'] }}</strong>
                                        <br><small class="text-muted">{{ $pegawai['nik'] }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $pegawai['departemen'] ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info" title="Jumlah kali dinilai dalam 1 bulan">{{ $pegawai['total_penilaian'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger fw-bold">{{ $pegawai['total_nilai'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $pegawai['rata_rata_nilai'] }}/11</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $pegawai['nilai_tertinggi'] >= 10 ? 'success' : 'warning' }}">{{ $pegawai['nilai_tertinggi'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $pegawai['nilai_terendah'] }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Belum ada data penilaian
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

    <!-- Statistik Item dan Departemen -->
    <div class="row g-3 mb-4">
        <!-- Statistik Item yang Paling Sering Tidak Sesuai -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list-ul me-2"></i>Item yang Paling Sering Tidak Sesuai
                    </h5>
                    <small class="text-white-50">Berdasarkan Persentase Ketidaksesuaian</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Item</th>
                                    <th class="text-center">Tidak Sesuai</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistikItem as $index => $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $index < 3 ? 'danger' : 'warning' }}">#{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $item['label'] }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $item['total_tidak_sesuai'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $item['total_penilaian'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $item['persentase_tidak_sesuai'] > 30 ? 'danger' : ($item['persentase_tidak_sesuai'] > 15 ? 'warning' : 'info') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $item['persentase_tidak_sesuai'] }}%"
                                                 aria-valuenow="{{ $item['persentase_tidak_sesuai'] }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $item['persentase_tidak_sesuai'] }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik per Departemen -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>Rata-rata Nilai per Departemen
                    </h5>
                    <small class="text-white-50">Berdasarkan Rata-rata Nilai</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Pegawai</th>
                                    <th class="text-center">Total Penilaian</th>
                                    <th class="text-center">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistikDepartemen as $index => $dept)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $index < 3 ? 'success' : 'info' }}">#{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $dept['departemen'] }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $dept['jumlah_pegawai'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $dept['total_penilaian'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $dept['rata_rata_nilai'] >= 10 ? 'success' : ($dept['rata_rata_nilai'] >= 8 ? 'warning' : 'danger') }}">
                                            {{ $dept['rata_rata_nilai'] }}/11
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Trend 6 Bulan Terakhir -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Trend Rata-rata Nilai 6 Bulan Terakhir
                    </h5>
                </div>
                <div class="card-body">
                    <div id="chart-trend" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pegawai yang Belum Pernah Dinilai -->
    @if($pegawaiBelumDinilai->count() > 0)
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-times me-2"></i>Pegawai yang Belum Pernah Dinilai ({{ $pegawaiBelumDinilai->count() }} pegawai)
                    </h5>
                    <small class="text-white-50">Pegawai aktif yang belum memiliki data penilaian pada bulan ini</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIK</th>
                                    <th>Nama Pegawai</th>
                                    <th>Departemen</th>
                                    <th>Jabatan</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pegawaiBelumDinilai as $index => $pegawai)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $pegawai['nik'] }}</td>
                                    <td><strong>{{ $pegawai['nama'] }}</strong></td>
                                    <td>{{ $pegawai['departemen'] ?? '-' }}</td>
                                    <td>{{ $pegawai['jabatan'] ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Belum Dinilai
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabel Rekapan Semua Pegawai -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Rekapan Semua Pegawai ({{ $rekapanPegawai->count() }} pegawai)
                    </h5>
                    <small class="text-white-50">Bulan {{ \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Jumlah Penilaian</th>
                                    <th class="text-center">Total Nilai</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-center">Tertinggi</th>
                                    <th class="text-center">Terendah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekapanPegawai as $index => $row)
                                <tr class="{{ $row['total_penilaian'] == 0 ? 'table-warning' : '' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['nik'] }}</td>
                                    <td><strong>{{ $row['nama'] }}</strong></td>
                                    <td><small>{{ $row['departemen'] ?? '-' }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row['total_penilaian'] > 0 ? 'info' : 'secondary' }}">{{ $row['total_penilaian'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $row['total_nilai'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row['rata_rata_nilai'] >= 10 ? 'success' : ($row['rata_rata_nilai'] >= 8 ? 'warning' : 'danger') }}">
                                            {{ $row['rata_rata_nilai'] }}/11
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $row['nilai_tertinggi'] }}</td>
                                    <td class="text-center">{{ $row['nilai_terendah'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Item Tidak Sesuai per Pegawai (Top 10 Terendah) -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>Detail Item Tidak Sesuai - Top 10 Pegawai dengan Nilai Terendah
                    </h5>
                    <small class="text-white-50">Analisa item yang paling sering tidak sesuai per pegawai</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle">Rank</th>
                                    <th rowspan="2" class="align-middle">Nama Pegawai</th>
                                    <th rowspan="2" class="align-middle">Departemen</th>
                                    <th rowspan="2" class="text-center align-middle">Rata-rata</th>
                                    <th colspan="11" class="text-center">Item Tidak Sesuai (Persentase)</th>
                                </tr>
                                <tr>
                                    @foreach(['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'] as $item)
                                        <th class="text-center" style="font-size: 0.75rem;">{{ $itemLabels[$item] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rankingTerendah->take(10) as $index => $pegawai)
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">#{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $pegawai['nama'] }}</strong>
                                        <br><small class="text-muted">{{ $pegawai['nik'] }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $pegawai['departemen'] ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $pegawai['rata_rata_nilai'] }}/11</span>
                                    </td>
                                    @foreach(['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'] as $item)
                                        <td class="text-center">
                                            @php
                                                $persentase = $pegawai['persentase_item_tidak_sesuai'][$item] ?? 0;
                                            @endphp
                                            @if($persentase > 0)
                                                <span class="badge bg-{{ $persentase > 50 ? 'danger' : ($persentase > 25 ? 'warning' : 'info') }}" 
                                                      title="{{ $persentase }}% tidak sesuai">
                                                    {{ number_format($persentase, 0) }}%
                                                </span>
                                            @else
                                                <span class="badge bg-success">0%</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="15" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Belum ada data penilaian
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan ApexCharts sudah dimuat
    if (typeof ApexCharts === 'undefined') {
        console.warn('ApexCharts library not loaded');
        return;
    }

    // Data untuk grafik trend
    var trendData = {!! json_encode($trendData) !!};
    
    var optionsTrend = {
        series: [{
            name: 'Rata-rata Nilai',
            data: trendData.map(item => item.rata_rata)
        }],
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: true
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: trendData.map(item => item.bulan)
        },
        yaxis: {
            title: {
                text: 'Rata-rata Nilai'
            },
            min: 0,
            max: 11
        },
        title: {
            text: 'Trend Rata-rata Nilai Budaya Kerja',
            align: 'left'
        },
        markers: {
            size: 5,
            hover: {
                size: 7
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + '/11'
                }
            }
        },
        colors: ['#5e72e4']
    };

    // Render Chart
    var chartTrendEl = document.querySelector("#chart-trend");
    if (chartTrendEl) {
        try {
            var chartTrend = new ApexCharts(chartTrendEl, optionsTrend);
            chartTrend.render();
        } catch (e) {
            console.error('Error rendering trend chart:', e);
        }
    }
});
</script>

@endsection
