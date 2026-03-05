@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Semua Pegawai – Budaya Kerja')

@section('content')
<div class="container-fluid page-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">
                        Rekap Budaya Kerja Semua Pegawai
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
                    <h6 class="text-white-50 mb-2">Pegawai Dinilai</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiDinilai }}</h3>
                    <small class="text-white-50">Memiliki minimal 1 penilaian</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Belum Dinilai</h6>
                    <h3 class="mb-0">{{ $jumlahPegawaiBelumDinilai }}</h3>
                    <small class="opacity-75">Belum ada penilaian pada periode ini</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-2">Tertib / Warning / Tidak tertib</h6>
                    <h5 class="mb-0">Tertib: {{ $jumlahPegawaiTertib }}</h5>
                    <h5 class="mb-0">Warning: {{ $jumlahPegawaiWarning }}</h5>
                    <h5 class="mb-0">Tidak tertib: {{ $jumlahPegawaiTidakTertib }}</h5>
                    <small class="text-white-50">Berdasarkan total pelanggaran (0=Tertib, 1-3=Warning, &gt;3=Tidak tertib)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel semua pegawai -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Pegawai dan Status Penilaian</h5>
                    <small class="text-muted">Urut berdasarkan nama pegawai</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Jabatan</th>
                                    <th class="text-center">Jumlah Penilaian</th>
                                    <th class="text-center">Total Nilai</th>
                                    <th class="text-center">Pelanggaran</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-center">Tertinggi</th>
                                    <th class="text-center">Terendah</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Tepat Waktu</th>
                                    <th class="text-center">Terlambat</th>
                                    <th class="text-center" title="Undangan acara/rapat">Diundang</th>
                                    <th class="text-center" title="Hadir di acara">Hadir</th>
                                    <th class="text-center">Ijin</th>
                                    <th class="text-center">Cuti</th>
                                    <th class="text-center">Tidak Hadir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekapanPegawai as $index => $row)
                                <tr class="{{ $row['total_penilaian'] == 0 ? 'table-warning' : '' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['nik'] }}</td>
                                    <td>
                                        <a href="{{ route('budayakerja.rekap_pegawai_detail', array_filter(['nik' => $row['nik'], 'start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen ?? null])) }}" class="text-primary fw-bold text-decoration-none">
                                            {{ $row['nama'] }}
                                        </a>
                                    </td>
                                    <td><small>{{ $row['departemen'] ?? '-' }}</small></td>
                                    <td><small>{{ $row['jabatan'] ?? '-' }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row['total_penilaian'] > 0 ? 'info' : 'secondary' }}">
                                            {{ $row['total_penilaian'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $row['total_nilai'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ ($row['total_pelanggaran'] ?? 0) > 0 ? 'warning' : 'secondary' }}">{{ $row['total_pelanggaran'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $row['status_tertib'] === 'Tertib' ? 'success' : ($row['status_tertib'] === 'Warning' ? 'warning' : ($row['total_penilaian'] > 0 ? 'danger' : 'secondary')) }}">
                                            {{ $row['rata_rata_nilai'] }}/11
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $row['nilai_tertinggi'] }}</td>
                                    <td class="text-center">{{ $row['nilai_terendah'] }}</td>
                                    <td class="text-center">
                                        @if($row['status_tertib'] === 'Tertib')
                                            <span class="badge bg-success">Tertib</span>
                                        @elseif($row['status_tertib'] === 'Warning')
                                            <span class="badge bg-warning text-dark">Warning</span>
                                        @elseif($row['status_tertib'] === 'Tidak tertib')
                                            <span class="badge bg-danger">Tidak tertib</span>
                                        @else
                                            <span class="badge bg-secondary">Belum dinilai</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $row['jumlah_hadir'] ?? 0 }}</td>
                                    <td class="text-center">
                                        <span class="text-success">{{ $row['jumlah_tepat_waktu'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ ($row['jumlah_terlambat'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['jumlah_terlambat'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary">{{ $row['jumlah_diundang_agenda'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="text-success">{{ $row['jumlah_hadir_agenda'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="text-info">{{ $row['jumlah_ijin_agenda'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="text-primary">{{ $row['jumlah_cuti_agenda'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="{{ ($row['jumlah_tidak_hadir_agenda'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['jumlah_tidak_hadir_agenda'] ?? 0 }}</span></td>
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
