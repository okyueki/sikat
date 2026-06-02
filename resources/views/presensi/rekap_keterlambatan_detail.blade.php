@extends('layouts.pages-layouts')

@section('pageTitle', 'Detail Keterlambatan - ' . ($pegawai->nama ?? '-'))

@section('content')
<div class="container-fluid page-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-1">{{ $pegawai->nama }}</h4>
                        <small class="text-muted">NIK: {{ $pegawai->nik }} | Dept: {{ $pegawai->departemen }}</small>
                    </div>
                    <a href="{{ $backUrl }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('presensi.rekap_keterlambatan_detail', $pegawai->nik) }}" class="row g-3 align-items-end">
                        <input type="hidden" name="departemen" value="{{ $departemen }}">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Tanggal mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                   value="{{ $startDate }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Tanggal sampai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                   value="{{ $endDate }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-1"></i> Tampilkan
                            </button>
                            <a href="{{ route('presensi.rekap_keterlambatan_detail', $pegawai->nik) }}" class="btn btn-outline-secondary">
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

    <!-- Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Tepat Waktu</h6>
                    <h3 class="mb-0">{{ $jmlTepatWaktu }}</h3>
                    <small class="text-white-50">Kali</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Terlambat</h6>
                    <h3 class="mb-0">{{ $jmlTerlambat }}</h3>
                    <small class="text-white-50">Kali</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Kehadiran</h6>
                    <h3 class="mb-0">{{ $totalKehadiran }}</h3>
                    <small class="text-white-50">Kali</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="text-dark-50 mb-2">Total Cuti</h6>
                    <h3 class="mb-0">{{ $totalCuti }}</h3>
                    <small class="text-dark-50">Hari (dari {{ $jumlahCuti }} pengajuan)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Jadwal Kosong</h6>
                    <h3 class="mb-0">{{ $totalKosong ?? 0 }}</h3>
                    <small class="text-white-50">Hari tanpa jadwal</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Detail -->
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Breakdown Kehadiran</h5>
                    <small class="text-muted">Periode {{ $periodeText }}</small>
                </div>
                <div class="card-body">
                    @if($listPresensi->isEmpty())
                        <p class="text-muted mb-0 text-center py-4">Tidak ada data presensi untuk periode ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped mb-0">
                                <thead class="table-danger">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Jam Std</th>
                                        <th>Jam Datang</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                        <th>Keterlambatan</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listPresensi as $idx => $row)
                                    <tr class="{{ $row['status'] === 'Terlambat' ? 'table-danger' : '' }} {{ ($row['tipe'] ?? '') === 'cuti' ? 'table-warning' : '' }} {{ ($row['tipe'] ?? '') === 'kosong' ? (($row['is_tanggal_merah'] ?? false) ? 'table-danger' : 'table-success') : '' }}">
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            @if(($row['tipe'] ?? '') === 'cuti')
                                                <span class="badge bg-warning text-dark">{{ $row['tanggal'] }}</span>
                                            @elseif(($row['tipe'] ?? '') === 'kosong')
                                                <span class="badge bg-secondary">{{ $row['tanggal'] }}</span>
                                            @else
                                                <span class="text-primary">{{ $row['tanggal'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['shift'])
                                                <span class="badge bg-info">{{ $row['shift'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['jam_masuk_std'])
                                                <small class="text-muted">{{ $row['jam_masuk_std'] }} - {{ $row['jam_pulang_std'] ?? '-' }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $row['jam_datang'] }}</td>
                                        <td>{{ $row['jam_pulang'] }}</td>
                                        <td>
                                            @if(($row['tipe'] ?? '') === 'cuti')
                                                <span class="badge bg-warning text-dark">{{ $row['status'] }}</span>
                                            @elseif(($row['tipe'] ?? '') === 'kosong')
                                                <span class="badge {{ ($row['is_tanggal_merah'] ?? false) ? 'bg-danger' : 'bg-success' }}">{{ $row['status'] }}</span>
                                            @elseif($row['status'] === 'Tepat Waktu')
                                                <span class="badge bg-success">{{ $row['status'] }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ $row['status'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['keterlambatan'] !== '-' && $row['keterlambatan'] !== '00:00:00')
                                                <span class="text-danger fw-bold">{{ $row['keterlambatan'] }}</span>
                                            @else
                                                {{ $row['keterlambatan'] }}
                                            @endif
                                        </td>
                                        <td>
                                            <small class="{{ ($row['tipe'] ?? '') === 'cuti' ? 'text-dark fw-bold' : (($row['tipe'] ?? '') === 'kosong' ? (($row['is_tanggal_merah'] ?? false) ? 'text-danger fw-bold' : 'text-success') : 'text-muted') }}">
                                                @if(($row['tipe'] ?? '') === 'cuti')
                                                    <i class="fas fa-plane mr-1"></i>
                                                    {{ $row['jenis_cuti'] ?? '-' }}
                                                    <br><span class="badge badge-sm {{ $row['status_cuti'] === 'Disetujui' ? 'bg-success' : ($row['status_cuti'] === 'Ditolak' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $row['status_cuti'] }}</span>
                                                    @if(!empty($row['periode_cuti']))
                                                    <br><small class="text-muted"><i class="far fa-calendar mr-1"></i>{{ $row['periode_cuti'] }}</small>
                                                    @endif
                                                @elseif(($row['tipe'] ?? '') === 'kosong')
                                                    @if(($row['is_tanggal_merah'] ?? false))
                                                        <i class="fas fa-sun mr-1"></i>
                                                        Tanggal Merah
                                                    @else
                                                        <i class="fas fa-home mr-1"></i>
                                                        Libur
                                                    @endif
                                                @else
                                                    {{ $row['status_db'] }}
                                                @endif
                                            </small>
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