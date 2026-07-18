@extends('layouts.pages-layouts')

@section('pageTitle', 'Enroll Pegawai — InsightFace')

@section('content')
<div class="container-fluid page-body insightface-ui">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <a href="{{ route('insightface.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <span class="badge text-bg-light border px-3 py-2">Pencarian pegawai aktif</span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="row g-4 align-items-center">
                <div class="col-xl-8">
                    <span class="badge text-bg-light border mb-3">Enroll Baru</span>
                    <h3 class="mb-2">Cari pegawai untuk pendaftaran wajah</h3>
                    <p class="text-muted mb-0 insightface-lead">
                        Gunakan halaman ini untuk menemukan pegawai aktif berdasarkan nama atau NIK, lalu lanjutkan ke halaman detail untuk enroll atau re-enroll wajah.
                    </p>
                </div>
                <div class="col-xl-4">
                    <div class="insightface-meta">
                        <div><span>Minimal pencarian</span><strong>2 karakter</strong></div>
                        <div><span>Status pegawai</span><strong>AKTIF</strong></div>
                        <div><span>Kata kunci</span><strong>{{ $q !== '' ? $q : 'Belum diisi' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                <div>
                    <h5 class="card-title mb-1">Pencarian pegawai</h5>
                    <p class="text-muted mb-0 small">Masukkan nama atau NIK untuk melihat pegawai aktif yang bisa didaftarkan ke InsightFace.</p>
                </div>
                @if(strlen($q) >= 2)
                    <span class="badge text-bg-light border">{{ number_format($results->count()) }} hasil</span>
                @endif
            </div>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('insightface.enroll_search') }}" class="insightface-toolbar row g-3 mb-4">
                <div class="col-md-9">
                    <label class="form-label small text-muted mb-1">Nama atau NIK</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Ketik nama atau NIK (min. 2 karakter)" autofocus>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Cari Pegawai</button>
                </div>
            </form>

            @if(strlen($q) >= 2)
                <div class="table-responsive">
                    <table class="table insightface-table align-middle">
                        <thead>
                            <tr>
                                <th>Pegawai</th>
                                <th>Departemen</th>
                                <th>Status enroll</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $pegawai)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $pegawai->nama }}</div>
                                        <div class="small text-muted">NIK {{ $pegawai->nik }}</div>
                                    </td>
                                    <td>{{ $pegawai->departemen ?? '-' }}</td>
                                    <td>
                                        @if($enrolledIds->has($pegawai->id))
                                            <span class="badge bg-success-subtle text-success">Sudah enroll</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Belum enroll</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('insightface.show', $pegawai->id) }}" class="btn btn-sm {{ $enrolledIds->has($pegawai->id) ? 'btn-outline-primary' : 'btn-primary' }}">
                                            {{ $enrolledIds->has($pegawai->id) ? 'Lihat / Re-enroll' : 'Enroll Sekarang' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-muted mb-1">Pegawai tidak ditemukan.</div>
                                        <small class="text-muted">Periksa ejaan nama atau coba cari dengan NIK.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif($q !== '')
                <div class="insightface-empty-state text-center py-5">
                    <div class="fw-semibold mb-1">Ketik minimal 2 karakter.</div>
                    <p class="text-muted mb-0">Masukkan nama atau NIK yang lebih lengkap agar pencarian bisa dijalankan.</p>
                </div>
            @else
                <div class="insightface-empty-state text-center py-5">
                    <div class="fw-semibold mb-1">Siap mencari pegawai.</div>
                    <p class="text-muted mb-0">Mulai dengan mengetik nama atau NIK pegawai aktif, lalu lanjutkan ke halaman detail untuk proses enroll.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .insightface-ui .card {
        border-radius: 18px;
    }
    .insightface-lead {
        max-width: 760px;
        line-height: 1.65;
    }
    .insightface-meta {
        display: grid;
        gap: 8px;
        padding: 14px 16px;
        border-radius: 14px;
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid #e9edf5;
    }
    .insightface-meta div {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 0.9rem;
    }
    .insightface-meta span {
        color: #6c757d;
    }
    .insightface-meta strong {
        color: #212529;
        text-align: right;
        word-break: break-word;
    }
    .insightface-toolbar {
        padding: 16px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #edf1f5;
    }
    .insightface-table thead th {
        border-bottom: 1px solid #edf1f5;
        color: #6c757d;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
    }
    .insightface-table tbody td {
        padding-top: 1rem;
        padding-bottom: 1rem;
        border-color: #f1f3f5;
    }
    .insightface-empty-state {
        border: 1px dashed #dbe3ef;
        border-radius: 16px;
        background: #fafcff;
    }
    @media (max-width: 767.98px) {
        .insightface-meta div {
            flex-direction: column;
        }
    }
</style>
@endpush
