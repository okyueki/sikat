@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Semua Pegawai – Budaya Kerja')

@section('content')
<div class="container-fluid page-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">
                        Rekap Terpadu Semua Pegawai
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('budayakerja.rekap_pegawai_export', array_filter(['start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen ?? null])) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('budayakerja.index') }}" class="btn btn-secondary btn-sm">Kembali ke daftar harian</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('budayakerja.rekap_pegawai') }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('budayakerja.rekap_pegawai') }}" class="btn btn-outline-secondary">
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
                    <h6 class="text-white-50 mb-2">Total Pegawai Aktif</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiTotal }}</h3>
                    <small class="text-white-50">Periode {{ $periodeText }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Pegawai Aktif</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiTertib }}</h3>
                    <small class="text-white-50">Score ≥ 70</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Warning</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiWarning }}</h3>
                    <small class="opacity-75">Score 50-69</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Tidak Aktif</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiTidakTertib }}</h3>
                    <small class="text-white-50">Score &lt; 50</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel semua pegawai -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Pegawai dan Status Kehadiran</h5>
                    <small class="text-muted">Urut berdasarkan nama | Bobot: Presensi 30% | Sholat 25% | Budaya 25% | Agenda 20%</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle">
                            <thead>
                                <tr class="text-center">
                                    <th style="width: 50px;">No</th>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th class="bg-dark text-white">Overall<br>Score</th>
                                    <th class="bg-primary text-white">Status</th>
                                    <th class="bg-info text-white">Presensi</th>
                                    <th class="bg-info text-white">Sholat</th>
                                    <th class="bg-info text-white">Budaya</th>
                                    <th class="bg-info text-white">Agenda</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dataWithScores as $index => $row)
                                @php
                                    $status = $row['status_keterlibatan'] ?? 'tidak_aktif';
                                    $statusBadgeClass = match($status) {
                                        'aktif' => 'success',
                                        'warning' => 'warning text-dark',
                                        'tidak_aktif' => 'danger',
                                        default => 'secondary'
                                    };
                                    $statusLabel = match($status) {
                                        'aktif' => 'Aktif',
                                        'warning' => 'Warning',
                                        'tidak_aktif' => 'Tidak Aktif',
                                        default => 'N/A'
                                    };
                                    $breakdown = $row['score_breakdown'] ?? $row['breakdown'] ?? [];
                                @endphp
                                <tr class="{{ $status === 'tidak_aktif' ? 'table-danger' : ($status === 'warning' ? 'table-warning' : '') }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td><small>{{ $row['nik'] }}</small></td>
                                    <td>
                                        <a href="{{ route('budayakerja.rekap_pegawai_detail', array_filter(['nik' => $row['nik'], 'start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen ?? null])) }}" class="text-primary fw-bold text-decoration-none">
                                            {{ $row['nama'] }}
                                        </a>
                                    </td>
                                    <td><small>{{ $row['departemen'] ?? '-' }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-dark fs-6">{{ number_format($row['overall_score'] ?? 0, 1) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <small>{{ $breakdown['presensi']['score'] ?? 0 }}/100</small><br>
                                        <span class="badge bg-{{ ($breakdown['presensi']['score'] ?? 0) >= 70 ? 'success' : (($breakdown['presensi']['score'] ?? 0) >= 50 ? 'warning text-dark' : 'danger') }} micro">
                                            {{ number_format(($breakdown['presensi']['weighted'] ?? 0), 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small>{{ $breakdown['sholat']['score'] ?? 0 }}/100</small><br>
                                        <span class="badge bg-{{ ($breakdown['sholat']['score'] ?? 0) >= 70 ? 'success' : (($breakdown['sholat']['score'] ?? 0) >= 50 ? 'warning text-dark' : 'danger') }} micro">
                                            {{ number_format(($breakdown['sholat']['weighted'] ?? 0), 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small>{{ $breakdown['budaya']['score'] ?? 0 }}/100</small><br>
                                        <span class="badge bg-{{ ($breakdown['budaya']['score'] ?? 0) >= 70 ? 'success' : (($breakdown['budaya']['score'] ?? 0) >= 50 ? 'warning text-dark' : 'danger') }} micro">
                                            {{ number_format(($breakdown['budaya']['weighted'] ?? 0), 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small>{{ $breakdown['agenda']['score'] ?? 0 }}/100</small><br>
                                        <span class="badge bg-{{ ($breakdown['agenda']['score'] ?? 0) >= 70 ? 'success' : (($breakdown['agenda']['score'] ?? 0) >= 50 ? 'warning text-dark' : 'danger') }} micro">
                                            {{ number_format(($breakdown['agenda']['weighted'] ?? 0), 1) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('budayakerja.rekap_pegawai_detail', array_filter(['nik' => $row['nik'], 'start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen ?? null])) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
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
</div>
@endsection