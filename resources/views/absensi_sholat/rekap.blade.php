@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Absensi Sholat')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Rekap Absensi Sholat (Masjid RS)</h5>
                <a href="{{ route('masjid_token.index') }}" class="btn btn-outline-secondary btn-sm">Token QR Masjid</a>
            </div>
            <div class="card-body">
                <form method="get" action="{{ route('rekap_absensi_sholat.index') }}" class="mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label for="bulan" class="form-label">Bulan</label>
                            <select name="bulan" id="bulan" class="form-select form-select-sm" style="width: auto;">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ (int) $bulan === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="tahun" class="form-label">Tahun</label>
                            <select name="tahun" id="tahun" class="form-select form-select-sm" style="width: auto;">
                                @foreach(range((int) date('Y'), (int) date('Y') - 3, -1) as $y)
                                    <option value="{{ $y }}" {{ (int) $tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                        </div>
                    </div>
                </form>

                <p class="text-muted small mb-3">
                    Daftar karyawan yang tercatat absen sholat di masjid RS pada periode {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}.
                </p>

                @if(empty($rekap))
                    <div class="alert alert-info">Belum ada data absensi sholat untuk periode ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Jabatan</th>
                                    <th>Tanggal hadir</th>
                                    <th class="text-center">Subuh</th>
                                    <th class="text-center">Dzuhur</th>
                                    <th class="text-center">Ashar</th>
                                    <th class="text-center">Maghrib</th>
                                    <th class="text-center">Isya</th>
                                    <th class="text-center">Sunnah</th>
                                    <th class="text-center"><strong>Total</strong></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekap as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $row['nik'] }}</td>
                                    <td>{{ $row['nama'] }}</td>
                                    <td>{{ $row['departemen'] }}</td>
                                    <td>{{ $row['jabatan'] }}</td>
                                    <td class="small">
                                        @if(!empty($row['tanggal_list']))
                                            @php
                                                $tglTampil = array_map(function ($d) {
                                                    return \Carbon\Carbon::parse($d)->translatedFormat('d M');
                                                }, $row['tanggal_list']);
                                            @endphp
                                            {{ implode(', ', $tglTampil) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $row['subuh'] ?: '-' }}</td>
                                    <td class="text-center">{{ $row['dzuhur'] ?: '-' }}</td>
                                    <td class="text-center">{{ $row['ashar'] ?: '-' }}</td>
                                    <td class="text-center">{{ $row['maghrib'] ?: '-' }}</td>
                                    <td class="text-center">{{ $row['isya'] ?: '-' }}</td>
                                    <td class="text-center">{{ $row['sunnah'] ?: '-' }}</td>
                                    <td class="text-center"><strong>{{ $row['total'] }}</strong></td>
                                    <td class="text-nowrap">
                                        @if(!empty($row['detail']))
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1" data-bs-toggle="collapse" data-bs-target="#detail-{{ $i }}" aria-expanded="false">Detail</button>
                                        @endif
                                    </td>
                                </tr>
                                @if(!empty($row['detail']))
                                <tr class="collapse" id="detail-{{ $i }}">
                                    <td colspan="14" class="bg-light py-2">
                                        <table class="table table-sm table-bordered mb-0 small">
                                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Jenis sholat</th></tr></thead>
                                            <tbody>
                                                @foreach($row['detail'] as $d)
                                                <tr>
                                                    <td>{{ $d['tanggal'] }}</td>
                                                    <td>{{ $d['jam'] }}</td>
                                                    <td>{{ ucfirst($d['jenis_sholat']) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
