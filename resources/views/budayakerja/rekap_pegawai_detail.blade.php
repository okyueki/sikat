@extends('layouts.pages-layouts')

@section('pageTitle', 'Breakdown Penilaian – ' . ($pegawai->nama ?? $pegawai->nik))

@section('content')
<div class="container-fluid page-body">
    <div class="row mb-3">
        <div class="col-12">
            @php
                $backUrl = route('budayakerja.rekap_pegawai', array_filter(['start_date' => $startDate, 'end_date' => $endDate, 'departemen' => $departemen ?? null]));
            @endphp
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap Semua Pegawai
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="card-title mb-0">Breakdown Penilaian Budaya Kerja</h4>
                    <small class="text-muted">Periode {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Nama</dt>
                                <dd class="col-sm-8"><strong>{{ $pegawai->nama ?? '-' }}</strong></dd>
                                <dt class="col-sm-4">NIK</dt>
                                <dd class="col-sm-8">{{ $pegawai->nik }}</dd>
                                <dt class="col-sm-4">Departemen</dt>
                                <dd class="col-sm-8">{{ $pegawai->departemen ?? '-' }}</dd>
                                <dt class="col-sm-4">Jabatan</dt>
                                <dd class="col-sm-8">{{ $pegawai->jbtn ?? $pegawai->jabatan ?? '-' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Jumlah penilaian</dt>
                                <dd class="col-sm-7"><span class="badge bg-info">{{ $totalPenilaian }}</span></dd>
                                <dt class="col-sm-5">Total nilai</dt>
                                <dd class="col-sm-7">{{ $totalNilai }}</dd>
                                <dt class="col-sm-5">Total pelanggaran</dt>
                                <dd class="col-sm-7"><span class="badge bg-{{ $totalPelanggaran > 0 ? 'warning' : 'secondary' }}">{{ $totalPelanggaran }}</span></dd>
                                <dt class="col-sm-5">Rata-rata</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-{{ $statusTertib === 'Tertib' ? 'success' : ($statusTertib === 'Warning' ? 'warning' : ($totalPenilaian > 0 ? 'danger' : 'secondary')) }}">
                                        {{ $rataRata }}/11
                                    </span>
                                </dd>
                                <dt class="col-sm-5">Tertinggi / Terendah</dt>
                                <dd class="col-sm-7">{{ $nilaiTertinggi }} / {{ $nilaiTerendah }}</dd>
                                <dt class="col-sm-5">Status</dt>
                                <dd class="col-sm-7">
                                    @if($statusTertib === 'Tertib')
                                        <span class="badge bg-success">Tertib</span>
                                    @elseif($statusTertib === 'Warning')
                                        <span class="badge bg-warning text-dark">Warning</span>
                                    @elseif($statusTertib === 'Tidak tertib')
                                        <span class="badge bg-danger">Tidak tertib</span>
                                    @else
                                        <span class="badge bg-secondary">Belum dinilai</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan kehadiran periode ini --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Kehadiran periode ini</h5>
                    <small class="text-muted">Data presensi {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2">Hadir</span>
                                <strong>{{ $jumlahHadir ?? 0 }} hari</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">Tepat Waktu</span>
                                <strong>{{ $jumlahTepatWaktu ?? 0 }} kali</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-2">Terlambat</span>
                                <strong>{{ $jumlahTerlambat ?? 0 }} kali</strong>
                            </div>
                        </div>
                    </div>
                    @if(isset($listPresensi) && $listPresensi->isNotEmpty())
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Jadwal</th>
                                        <th>Jam Datang</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                        <th>Keterlambatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listPresensi as $idx => $p)
                                    @php
                                        $tanggalKey = \Carbon\Carbon::parse($p->jam_datang)->format('Y-m-d');
                                        $shiftValue = $jadwalShifts[$tanggalKey] ?? null;
                                        $isPagi = $shiftValue && (strpos(strtolower($shiftValue), 'pagi') !== false);
                                    @endphp
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($p->jam_datang)->format('d-m-Y') }}</td>
                                        <td>
                                            @if($shiftValue)
                                                <span class="badge bg-{{ $isPagi ? 'info' : 'secondary' }}">
                                                    {{ $shiftValue }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $p->jam_datang ? \Carbon\Carbon::parse($p->jam_datang)->format('H:i') : '-' }}</td>
                                        <td>{{ $p->jam_pulang ? \Carbon\Carbon::parse($p->jam_pulang)->format('H:i') : '-' }}</td>
                                        <td>
                                            @if(in_array($p->status, ['Tepat Waktu', 'Tepat Waktu & PSW']))
                                                <span class="badge bg-success">{{ $p->status }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ $p->status ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $p->keterlambatan ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0 mt-2">Tidak ada data presensi untuk periode ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail per hari</h5>
                    <small class="text-muted">Setiap baris = satu kali penilaian (tanggal + jam)</small>
                </div>
                <div class="card-body">
                    @if($listPenilaian->isEmpty())
                        <p class="text-muted mb-0">Tidak ada data penilaian untuk pegawai ini pada periode yang dipilih.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Shift</th>
                                        <th>Penilai</th>
                                        <th class="text-center">Total Nilai</th>
                                        <th>Keterangan (item tidak sesuai)</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listPenilaian as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $row->jam ?? '-' }}</td>
                                        <td>{{ $row->shift ?? '-' }}</td>
                                        <td>
                                            @if($row->atasan)
                                                {{ $row->atasan->nama }}
                                                @if($row->petugas)
                                                    <br><small class="text-muted">{{ $row->petugas }}</small>
                                                @endif
                                            @else
                                                {{ $row->petugas ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $row->total_nilai >= 11 ? 'success' : ($row->total_nilai >= 10 ? 'warning' : 'danger') }}">
                                                {{ $row->total_nilai }}/11
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $itemsTidakSesuai = [];
                                                foreach ($items as $item) {
                                                    if (isset($row->$item) && $row->$item == 0) {
                                                        $itemsTidakSesuai[] = $itemLabels[$item] ?? $item;
                                                    }
                                                }
                                            @endphp
                                            @if(count($itemsTidakSesuai) > 0)
                                                <span class="text-danger">{{ implode(', ', $itemsTidakSesuai) }}</span>
                                            @else
                                                <span class="text-success">Semua sesuai</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('budayakerja.show', $row->id) }}" class="btn btn-info btn-sm">Detail</a>
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
