@extends('layouts.pages-layouts')

@section('pageTitle', 'Foto Absensi')

@section('content')
@php
    $queryBase = array_filter([
        'start_date' => $startDate,
        'end_date' => $endDate,
        'departemen' => $departemen ?: null,
        'q' => $search ?: null,
        'has_photo' => $hasPhoto ? '1' : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid page-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-0">Foto Absensi Karyawan</h4>
                        <small class="text-muted">Lihat absensi dari aplikasi (sementara &amp; rekap) beserta fotonya. Hanya tampilan — tanpa verifikasi setuju/tolak.</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Preset tanggal">
                        <a href="{{ route('presensi.foto_absensi', array_merge($queryBase, ['start_date' => $today, 'end_date' => $today, 'sumber' => $sumber])) }}"
                           class="btn btn-outline-primary {{ $startDate === $today && $endDate === $today ? 'active' : '' }}">Hari ini</a>
                        <a href="{{ route('presensi.foto_absensi', array_merge($queryBase, [
                                'start_date' => \Carbon\Carbon::parse($today)->subDay()->toDateString(),
                                'end_date' => \Carbon\Carbon::parse($today)->subDay()->toDateString(),
                                'sumber' => $sumber,
                            ])) }}"
                           class="btn btn-outline-primary">Kemarin</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="fas fa-info-circle me-1"></i>
                        Absensi dari mesin <strong>fingerprint</strong> tidak tercatat di sistem ini, sehingga tidak muncul di halaman ini.
                        Yang tampil hanya absensi yang dilakukan lewat <strong>aplikasi</strong>
                        (sumber: tabel <code>temporary_presensi</code> + <code>rekap_presensi</code>).
                    </div>

                    <form method="GET" action="{{ route('presensi.foto_absensi') }}" class="row g-3 align-items-end" id="fotoAbsensiFilterForm">
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Tanggal mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                   value="{{ $startDate }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">Tanggal sampai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                   value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2">
                            <label for="departemen" class="form-label">Departemen</label>
                            <select name="departemen" id="departemen" class="form-select select2">
                                <option value="">Semua Departemen</option>
                                @foreach($departemenList as $d)
                                    <option value="{{ $d }}" {{ $departemen === $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sumber" class="form-label">Sumber</label>
                            <select name="sumber" id="sumber" class="form-select">
                                <option value="semua" {{ $sumber === 'semua' ? 'selected' : '' }}>Semua</option>
                                <option value="sementara" {{ $sumber === 'sementara' ? 'selected' : '' }}>Sementara saja</option>
                                <option value="rekap" {{ $sumber === 'rekap' ? 'selected' : '' }}>Rekap saja</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="q" class="form-label">Cari nama / NIK</label>
                            <input type="text" name="q" id="q" class="form-control"
                                   value="{{ $search }}" placeholder="Nama atau NIK">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="1" id="has_photo" name="has_photo"
                                    {{ $hasPhoto ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_photo">Hanya ada foto</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                                <a href="{{ route('presensi.foto_absensi') }}" class="btn btn-outline-secondary" title="Reset">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="{{ route('presensi.foto_absensi', array_merge($queryBase, ['sumber' => 'sementara'])) }}"
               class="text-decoration-none text-reset">
                <div class="card border-warning h-100 {{ $sumber === 'sementara' ? 'shadow' : '' }}">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Sementara (belum rekap)</h6>
                        <h3 class="mb-0">{{ $totalTemporary }}</h3>
                        <small class="text-muted">Tabel temporary_presensi</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('presensi.foto_absensi', array_merge($queryBase, ['sumber' => 'rekap'])) }}"
               class="text-decoration-none text-reset">
                <div class="card border-success h-100 {{ $sumber === 'rekap' ? 'shadow' : '' }}">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Rekap (selesai)</h6>
                        <h3 class="mb-0">{{ $totalRekap }}</h3>
                        <small class="text-muted">Tabel rekap_presensi</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('presensi.foto_absensi', array_merge($queryBase, ['sumber' => $sumber === 'semua' ? 'semua' : $sumber, 'has_photo' => '1'])) }}"
               class="text-decoration-none text-reset">
                <div class="card border-info h-100 {{ $hasPhoto ? 'shadow' : '' }}">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Ada foto</h6>
                        <h3 class="mb-0">{{ $totalWithPhoto }}</h3>
                        <small class="text-muted">File ada di public/presensi</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ $hasPhoto ? 'Ditampilkan' : 'Total baris' }}</h6>
                    <h3 class="mb-0">{{ $rows->total() }}</h3>
                    <small class="text-muted">Tanpa foto: {{ $totalWithoutPhoto }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">Daftar Absensi</h5>
                    <small class="text-muted">
                        {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }}
                        s.d.
                        {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}
                        · Sumber: {{ ucfirst($sumber) }}
                        @if($hasPhoto) · Hanya ada foto @endif
                    </small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Foto</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>Departemen</th>
                                    <th>Shift</th>
                                    <th>Jam Datang</th>
                                    <th>Jam Pulang</th>
                                    <th>Status</th>
                                    <th>Sumber</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td>
                                            @if($row['photo_url'])
                                                <button type="button"
                                                    class="btn btn-link p-0 border-0 foto-thumb-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#fotoAbsensiModal"
                                                    data-photo-url="{{ $row['photo_url'] }}"
                                                    data-photo-caption="{{ $row['nama'] }} — {{ $row['jam_datang'] }} ({{ $row['sumber'] }})"
                                                    title="Lihat foto">
                                                    <img src="{{ $row['photo_url'] }}"
                                                         alt="Foto absensi {{ $row['nama'] }}"
                                                         class="rounded"
                                                         style="width: 56px; height: 56px; object-fit: cover;"
                                                         loading="lazy"
                                                         width="56"
                                                         height="56"
                                                         onerror="this.closest('td').innerHTML='<span class=&quot;text-muted small&quot;>Foto rusak</span>';">
                                                </button>
                                            @else
                                                <span class="text-muted small">Tidak ada foto</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $row['nama'] }}</strong>
                                            @if(!empty($row['keterangan']))
                                                <br><small class="text-muted">{{ $row['keterangan'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $row['nik'] }}</td>
                                        <td>{{ $row['departemen'] }}</td>
                                        <td>{{ $row['shift'] }}</td>
                                        <td>{{ $row['jam_datang'] }}</td>
                                        <td>{{ $row['jam_pulang'] }}</td>
                                        <td>
                                            <span class="badge {{ $row['status_class'] }}">{{ $row['status'] }}</span>
                                        </td>
                                        <td>
                                            @if($row['sumber'] === 'Sementara')
                                                <span class="badge bg-warning text-dark">Sementara</span>
                                            @else
                                                <span class="badge bg-success">Rekap</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Tidak ada data absensi aplikasi untuk filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <small class="text-muted">
                            Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}
                            dari {{ $rows->total() }} baris
                        </small>
                        {{ $rows->withQueryString()->links('vendor.pagination.tabler') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal preview foto --}}
<div class="modal fade" id="fotoAbsensiModal" tabindex="-1" aria-labelledby="fotoAbsensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fotoAbsensiModalLabel">Preview Foto Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fotoAbsensiModalImg" src="" alt="Preview foto absensi" class="img-fluid rounded" style="max-height: 70vh;">
                <p id="fotoAbsensiModalCaption" class="text-muted mt-3 mb-0"></p>
                <p id="fotoAbsensiModalError" class="text-danger mt-2 mb-0 d-none">Gagal memuat foto.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#departemen').select2({
            width: '100%',
            placeholder: 'Semua Departemen',
            allowClear: true
        });
    }

    var modalEl = document.getElementById('fotoAbsensiModal');
    if (!modalEl) return;

    var img = document.getElementById('fotoAbsensiModalImg');
    var cap = document.getElementById('fotoAbsensiModalCaption');
    var err = document.getElementById('fotoAbsensiModalError');

    modalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;
        var url = button.getAttribute('data-photo-url') || '';
        var caption = button.getAttribute('data-photo-caption') || '';
        if (err) err.classList.add('d-none');
        if (img) {
            img.classList.remove('d-none');
            img.onerror = function () {
                img.classList.add('d-none');
                if (err) err.classList.remove('d-none');
            };
            img.src = url;
            img.alt = caption || 'Preview foto absensi';
        }
        if (cap) cap.textContent = caption;
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (img) {
            img.src = '';
            img.onerror = null;
            img.classList.remove('d-none');
        }
        if (err) err.classList.add('d-none');
    });
});
</script>
@endpush
