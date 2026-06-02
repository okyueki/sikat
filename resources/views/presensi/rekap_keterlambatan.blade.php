@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Keterlambatan Per Pegawai')

@section('content')
<div class="container-fluid page-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">
                        Rekap Keterlambatan Per Pegawai
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('presensi.rekap_keterlambatan_export', array_filter(['start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen])) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i> Export CSV
                        </a>
                        <a href="{{ route('presensi.ringkasan_kehadiran') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('presensi.rekap_keterlambatan') }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('presensi.rekap_keterlambatan') }}" class="btn btn-outline-secondary">
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
                    <h3 class="mb-0">{{ $totalPegawai }}</h3>
                    <small class="text-white-50">Periode {{ $periodeText }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Tepat Waktu</h6>
                    <h3 class="mb-0">{{ $totalTepatWaktuSemua }}</h3>
                    <small class="text-white-50">Kali kehadiran</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Terlambat</h6>
                    <h3 class="mb-0">{{ $totalTerlambatSemua }}</h3>
                    <small class="text-white-50">Kali kehadiran</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Total Kehadiran</h6>
                    <h3 class="mb-0">{{ $totalKehadiranSemua }}</h3>
                    <small class="text-white-50">Seluruh pegawai</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="text-dark-50 mb-2">Total Cuti</h6>
                    <h3 class="mb-0">{{ $totalCutiSemua }}</h3>
                    <small class="text-dark-50">Hari cuti approved</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Per Pegawai -->
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Detail Keterlambatan Per Pegawai</h5>
                    <small class="text-muted">Data presensi {{ $periodeText }}</small>
                </div>
                <div class="card-body">
                    @if(empty($rekapPerPegawai))
                        <p class="text-muted mb-0 text-center py-4">Tidak ada data untuk periode ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped mb-0">
                                <thead class="table-danger">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Departemen</th>
                                        <th class="text-center">Jml Terlambat</th>
                                        <th class="text-center">Jml Tepat Waktu</th>
                                        <th class="text-center">Jumlah Kehadiran</th>
                                        <th class="text-center">Jml Cuti</th>
                                        <th class="text-center">Hari Libur</th>
                                        <th class="text-center">Durasi Terlambat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekapPerPegawai as $idx => $row)
                                    <tr class="{{ $row['jml_terlambat'] > 0 ? 'table-danger' : '' }}">
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            <a href="{{ route('presensi.rekap_keterlambatan_detail', array_merge(['nik' => $row['nik']], array_filter(['start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen]))) }}"
                                               class="text-primary fw-bold">
                                                {{ $row['nik'] }}
                                            </a>
                                        </td>
                                        <td><strong>{{ $row['nama'] }}</strong></td>
                                        <td><small>{{ $row['departemen'] }}</small></td>
                                        <td class="text-center">
                                            @if($row['jml_terlambat'] > 0)
                                                <span class="badge bg-danger">{{ $row['jml_terlambat'] }}</span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $row['jml_tepat_waktu'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $row['jumlah_kehadiran'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if(($row['jumlah_cuti'] ?? 0) > 0)
                                                <span class="badge bg-warning text-dark">{{ $row['jumlah_cuti'] }}</span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!empty($row['hari_cuti']))
                                                <span class="badge bg-warning text-dark">{{ count($row['hari_cuti']) }} hari</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($row['jml_terlambat'] > 0)
                                                <span class="text-danger fw-bold">{{ $row['durasi_terlambat'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
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