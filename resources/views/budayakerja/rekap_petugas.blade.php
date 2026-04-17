@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Pelaksanaan Tugas Penilai')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['total_petugas_terjadwal'] }}</h3>
                        <small>Petugas Terjadwal</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['total_jadwal'] }}</h3>
                        <small>Total Jadwal Tugas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['total_menilai'] }}</h3>
                        <small>Total Aktivitas Menilai</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ $summary['total_pegawai_dinilai'] }}</h3>
                        <small>Pegawai Dinilai (Petugas Terpilih)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Periode</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('budayakerja.rekap_petugas') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="petugas" class="form-label">Fokus Detail Petugas</label>
                        <select name="petugas" id="petugas" class="form-select">
                            <option value="">Pilih petugas</option>
                            @foreach($rekapPetugas as $row)
                                <option value="{{ $row['nik'] }}" {{ $selectedPetugas === $row['nik'] ? 'selected' : '' }}>
                                    {{ $row['nama'] }} ({{ $row['nik'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Rekap Petugas Penilai</h5>
                <small class="text-muted">Klik angka kolom "Sudah Menilai" untuk lihat detail</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Petugas</th>
                                <th>Departemen</th>
                                <th class="text-center">Terjadwal</th>
                                <th class="text-center">Sudah Menilai</th>
                                <th class="text-center">Pegawai Dinilai</th>
                                <th class="text-center">Realisasi</th>
                                <th class="text-center">Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rekapPetugas as $idx => $row)
                                @php
                                    $isSelected = $selectedPetugas === $row['nik'];
                                    $badgeClass = $row['persentase_realisasi'] >= 100
                                        ? 'bg-success'
                                        : ($row['persentase_realisasi'] >= 60 ? 'bg-warning text-dark' : 'bg-danger');
                                @endphp
                                <tr class="{{ $isSelected ? 'table-primary' : '' }}">
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <strong>{{ $row['nama'] }}</strong><br>
                                        <small class="text-muted">{{ $row['nik'] }}</small>
                                    </td>
                                    <td>{{ $row['departemen'] }}</td>
                                    <td class="text-center">{{ $row['total_jadwal'] }}</td>
                                    <td class="text-center">
                                        <a class="btn btn-sm {{ $isSelected ? 'btn-primary' : 'btn-outline-primary' }}"
                                           href="{{ route('budayakerja.rekap_petugas', ['start_date' => $startDate, 'end_date' => $endDate, 'petugas' => $row['nik']]) }}#detail-pegawai-dinilai">
                                            {{ $row['total_menilai'] }} kali
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $row['pegawai_unik'] }}</td>
                                    <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $row['persentase_realisasi'] }}%</span></td>
                                    <td class="text-center">{{ $row['selisih'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data jadwal pada rentang tanggal ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" id="detail-pegawai-dinilai">
            <div class="card-header">
                <h5 class="card-title mb-0">Detail Pegawai yang Dinilai</h5>
                <small class="text-muted">
                    Menampilkan detail penilaian oleh petugas:
                    <strong>{{ $selectedPetugas ?: '-' }}</strong>
                </small>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-2">Nama Pegawai (unik)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>NIK</th>
                                    <th>Nama Pegawai</th>
                                    <th>Departemen</th>
                                    <th class="text-center">Jumlah Dinilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detailPegawaiDinilai as $idx => $p)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $p['nik_pegawai'] }}</td>
                                        <td>{{ $p['nama_pegawai'] }}</td>
                                        <td>{{ $p['departemen'] }}</td>
                                        <td class="text-center">{{ $p['jumlah_dinilai'] }} kali</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada nama pegawai yang dinilai pada filter ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <h6 class="mb-2">Aktivitas Penilaian (detail per baris)</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>NIK Pegawai</th>
                                <th>Nama Pegawai</th>
                                <th>Departemen</th>
                                <th>Shift</th>
                                <th class="text-center">Total Nilai</th>
                                <th class="text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detailPenilaian as $idx => $item)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $item->tanggal }}</td>
                                    <td>{{ $item->jam }}</td>
                                    <td>{{ $item->nik_pegawai }}</td>
                                    <td>{{ $item->nama_pegawai }}</td>
                                    <td>{{ $item->departemen }}</td>
                                    <td>{{ $item->shift }}</td>
                                    <td class="text-center">{{ $item->total_nilai }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('budayakerja.show', $item->id) }}" class="btn btn-sm btn-outline-info">Lihat</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Belum ada aktivitas penilaian untuk petugas/rentang tanggal ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
