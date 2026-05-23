@extends('layouts.pages-layouts')

@section('pageTitle', 'Generator Jadwal Budaya Kerja')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-12 mb-3">
                <a href="{{ route('jadwalbudayakerja.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Kembali ke Daftar Jadwal
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Master Peserta Generator</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Pilih siapa saja yang ikut pool generate. Simpan master sekarang bersifat tambah/aktifkan (tidak reset total).
                        </p>
                        <form method="POST" action="{{ route('jadwalbudayakerja.generator.master') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">Petugas</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-check-all-petugas">Pilih Semua</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-uncheck-all-petugas">Reset</button>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-sm mb-2" id="search-petugas" placeholder="Cari nama / NIP petugas...">
                                    <div class="border rounded p-2" id="petugas-list" style="max-height: 360px; overflow-y: auto;">
                                        @forelse($petugas as $p)
                                            <div class="form-check mb-1 participant-item" data-name="{{ strtolower($p->nama . ' ' . $p->nip) }}">
                                                <input
                                                    class="form-check-input petugas-checkbox"
                                                    type="checkbox"
                                                    name="petugas_ids[]"
                                                    value="{{ $p->nip }}"
                                                    id="petugas_{{ $p->nip }}"
                                                    {{ in_array($p->nip, $selectedPetugas, true) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="petugas_{{ $p->nip }}">
                                                    {{ $p->nama }} <small class="text-muted">({{ $p->nip }})</small>
                                                </label>
                                            </div>
                                        @empty
                                            <small class="text-muted">Tidak ada data petugas aktif.</small>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">Dokter</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-check-all-dokter">Pilih Semua</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-uncheck-all-dokter">Reset</button>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-sm mb-2" id="search-dokter" placeholder="Cari nama / kode dokter...">
                                    <div class="border rounded p-2" id="dokter-list" style="max-height: 360px; overflow-y: auto;">
                                        @forelse($dokter as $d)
                                            <div class="form-check mb-1 participant-item" data-name="{{ strtolower($d->nm_dokter . ' ' . $d->kd_dokter) }}">
                                                <input
                                                    class="form-check-input dokter-checkbox"
                                                    type="checkbox"
                                                    name="dokter_ids[]"
                                                    value="{{ $d->kd_dokter }}"
                                                    id="dokter_{{ $d->kd_dokter }}"
                                                    {{ in_array($d->kd_dokter, $selectedDokter, true) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="dokter_{{ $d->kd_dokter }}">
                                                    {{ $d->nm_dokter }} <small class="text-muted">({{ $d->kd_dokter }})</small>
                                                </label>
                                            </div>
                                        @empty
                                            <small class="text-muted">Tidak ada data dokter aktif.</small>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Simpan Master Peserta
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Generate Jadwal Bulanan</h3>
                    </div>
                    <div class="card-body">
                        <ul class="small text-muted mb-3">
                            <li>Senin-Jumat: 2 Pagi + 2 Sore</li>
                            <li>Sabtu: 2 Pagi</li>
                            <li>Minggu: Libur</li>
                            <li>Mode generate: replace data bulan target</li>
                        </ul>

                        <form method="POST" action="{{ route('jadwalbudayakerja.generator.run') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Bulan</label>
                                <select class="form-control" name="bulan" required>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ (int) old('bulan', now()->month) === $i ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tahun</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    name="tahun"
                                    min="2024"
                                    max="2100"
                                    value="{{ old('tahun', now()->year) }}"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-magic"></i> Generate Jadwal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Master Peserta Aktif (Tersimpan)</h3>
                    </div>
                    <div class="card-body">
                        @if($masterParticipants->isEmpty())
                            <div class="text-muted">Belum ada master peserta aktif.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 80px;">No</th>
                                            <th style="width: 120px;">Tipe</th>
                                            <th style="width: 180px;">ID</th>
                                            <th>Nama</th>
                                            <th>Departemen</th>
                                            <th style="width: 160px;">No. HP</th>
                                            <th style="width: 120px;">Urutan</th>
                                            <th style="width: 110px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($masterParticipants as $idx => $row)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $row['tipe'] }}</td>
                                                <td>{{ $row['id'] }}</td>
                                                <td>{{ $row['nama'] }}</td>
                                                <td>{{ $row['departemen'] ?? '-' }}</td>
                                                <td>{{ $row['no_telp'] ?? '-' }}</td>
                                                <td>{{ $row['urutan'] ?? '-' }}</td>
                                                <td>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('jadwalbudayakerja.generator.master.delete', $row['member_id']) }}"
                                                        onsubmit="return confirm('Hapus peserta ini dari master generator?')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Hapus
                                                        </button>
                                                    </form>
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Rekap Distribusi Jadwal (Audit Keadilan)</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('jadwalbudayakerja.generator') }}" class="row g-2 align-items-end mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Bulan</label>
                                <select class="form-control" name="rekap_bulan">
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ (int) $rekapBulan === $i ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tahun</label>
                                <input type="number" class="form-control" name="rekap_tahun" min="2024" max="2100" value="{{ $rekapTahun }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Tampilkan</button>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <span class="badge bg-info me-2">Min: {{ $minTotal }}</span>
                                <span class="badge bg-warning text-dark me-2">Max: {{ $maxTotal }}</span>
                                <span class="badge bg-secondary">Selisih: {{ $selisihKeadilan }}</span>
                            </div>
                        </form>

                        @if($rekapRows->isEmpty())
                            <div class="text-muted">Belum ada data jadwal di bulan/tahun ini.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 80px;">No</th>
                                            <th style="width: 120px;">Tipe</th>
                                            <th style="width: 160px;">ID</th>
                                            <th>Nama</th>
                                            <th>Departemen</th>
                                            <th style="width: 150px;">No. HP</th>
                                            <th style="width: 100px;">Total</th>
                                            <th style="width: 100px;">Pagi</th>
                                            <th style="width: 100px;">Sore</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rekapRows as $idx => $row)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $row['tipe'] }}</td>
                                                <td>{{ $row['nik'] }}</td>
                                                <td>{{ $row['nama'] }}</td>
                                                <td>{{ $row['departemen'] }}</td>
                                                <td>{{ $row['no_telp'] }}</td>
                                                <td>{{ $row['total_jadwal'] }}</td>
                                                <td>{{ $row['total_pagi'] }}</td>
                                                <td>{{ $row['total_sore'] }}</td>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function bindFilter(inputId, listId) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            if (!input || !list) return;

            input.addEventListener('input', function () {
                const keyword = input.value.toLowerCase().trim();
                list.querySelectorAll('.participant-item').forEach(function (item) {
                    const haystack = item.getAttribute('data-name') || '';
                    item.style.display = haystack.includes(keyword) ? '' : 'none';
                });
            });
        }

        function toggleAll(selector, checked) {
            document.querySelectorAll(selector).forEach(function (checkbox) {
                checkbox.checked = checked;
            });
        }

        bindFilter('search-petugas', 'petugas-list');
        bindFilter('search-dokter', 'dokter-list');

        const btnCheckAllPetugas = document.getElementById('btn-check-all-petugas');
        const btnUncheckAllPetugas = document.getElementById('btn-uncheck-all-petugas');
        const btnCheckAllDokter = document.getElementById('btn-check-all-dokter');
        const btnUncheckAllDokter = document.getElementById('btn-uncheck-all-dokter');

        if (btnCheckAllPetugas) btnCheckAllPetugas.addEventListener('click', function () {
            toggleAll('.petugas-checkbox', true);
        });
        if (btnUncheckAllPetugas) btnUncheckAllPetugas.addEventListener('click', function () {
            toggleAll('.petugas-checkbox', false);
        });
        if (btnCheckAllDokter) btnCheckAllDokter.addEventListener('click', function () {
            toggleAll('.dokter-checkbox', true);
        });
        if (btnUncheckAllDokter) btnUncheckAllDokter.addEventListener('click', function () {
            toggleAll('.dokter-checkbox', false);
        });
    });
</script>
@endsection

