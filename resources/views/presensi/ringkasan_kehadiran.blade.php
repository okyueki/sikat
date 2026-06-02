@extends('layouts.pages-layouts')

@section('pageTitle', 'Ringkasan Kehadiran Pegawai')

@section('content')
<div class="container-fluid page-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">
                        Ringkasan Kehadiran Semua Pegawai
                    </h4>
                    <a href="{{ route('budayakerja.rekap_pegawai') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap Budaya Kerja
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('presensi.ringkasan_kehadiran') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Tanggal mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                   value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Tanggal sampai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                   value="{{ $endDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="departemen" class="form-label">Departemen</label>
                            <select name="departemen" id="departemen" class="form-select">
                                <option value="">Semua Departemen</option>
                                @foreach($departemenList ?? [] as $d)
                                    <option value="{{ $d }}" {{ ($departemen ?? '') == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-1"></i> Tampilkan
                            </button>
                            <a href="{{ route('presensi.ringkasan_kehadiran') }}" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $periodeText = \Carbon\Carbon::parse($startDate)->format('d-m-Y') . ' s.d. ' . \Carbon\Carbon::parse($endDate)->format('d-m-Y');
    @endphp

    <!-- Statistik ringkas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Pegawai</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiTotal }}</h3>
                    <small class="text-white-50">Periode {{ $periodeText }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Hadir</h6>
                    <h3 class="mb-0">{{ $totalHadir }}</h3>
                    <small class="text-white-50">Hari kehadiran</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Tepat Waktu</h6>
                    <h3 class="mb-0">{{ $totalTepatWaktu }}</h3>
                    <small class="text-white-50">Kali kehadiran</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Terlambat</h6>
                    <h3 class="mb-0">{{ $totalTerlambat }}</h3>
                    <small class="text-white-50">Keterlambatan</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Kehadiran Semua Pegawai -->
    <div class="row">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Detail Kehadiran Per Pegawai</h5>
                    <small class="text-muted">Data presensi {{ $periodeText }}</small>
                </div>
                <div class="card-body">
                    @if($listPresensi->isEmpty())
                        <p class="text-muted mb-0 text-center py-4">Tidak ada data presensi untuk periode ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped mb-0">
                                <thead class="table-info">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama</th>
                                        <th>Departemen</th>
                                        <th>Tanggal</th>
                                        <th>Jadwal</th>
                                        <th>Jam Datang</th>
                                        <th>Jam Pulang</th>
                                        <th>Status Recalc</th>
                                        <th>Status DB</th>
                                        <th>Telat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listPresensi as $idx => $p)
                                    @php
                                        $isPagi = $p['shift'] && (strpos(strtolower($p['shift']), 'pagi') !== false);
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td><strong>{{ $p['nama'] }}</strong></td>
                                        <td><small>{{ $p['departemen'] }}</small></td>
                                        <td>{{ $p['tanggal'] }}</td>
                                        <td>
                                            @if($p['shift'])
                                                <span class="badge bg-{{ $isPagi ? 'info' : 'secondary' }}">
                                                    {{ $p['shift'] }}
                                                </span>
                                                @if($p['jam_masuk_std'])
                                                    <br><small class="text-muted">{{ $p['jam_masuk_std'] }} - {{ $p['jam_pulang_std'] ?? '-' }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $p['jam_datang'] }}</td>
                                        <td>{{ $p['jam_pulang'] }}</td>
                                        <td>
                                            @if($p['status'] === 'Tepat Waktu')
                                                <span class="badge bg-success">{{ $p['status'] }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ $p['status'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary" title="Status dari database">{{ $p['status_original'] }}</span>
                                        </td>
                                        <td>
                                            @if($p['keterlambatan'] !== '00:00:00')
                                                <span class="text-danger fw-bold">{{ $p['keterlambatan'] }}</span>
                                            @else
                                                {{ $p['keterlambatan'] }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection