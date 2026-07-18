@extends('layouts.pages-layouts')

@section('pageTitle', 'Detail Wajah — ' . $pegawai->nama)

@section('content')
<div class="container-fluid page-body insightface-ui">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <a href="{{ route('insightface.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <span class="badge text-bg-light border px-3 py-2">Min score {{ $minScore }}%</span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="row g-4 align-items-center">
                <div class="col-xl-8">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-light border">Detail Pegawai</span>
                        <span class="badge {{ $profile ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                            {{ $profile ? 'Sudah enroll' : 'Belum enroll' }}
                        </span>
                        <span class="badge text-bg-light border">{{ $pegawai->stts_aktif ?? '-' }}</span>
                    </div>
                    <h3 class="mb-2">{{ $pegawai->nama }}</h3>
                    <p class="text-muted mb-0 insightface-lead">
                        Kelola status enroll wajah, unggah ulang foto referensi bila perlu, dan pantau
                        histori verifikasi wajah pegawai ini untuk audit operasional.
                    </p>
                </div>
                <div class="col-xl-4">
                    <div class="insightface-meta">
                        <div><span>NIK</span><strong>{{ $pegawai->nik }}</strong></div>
                        <div><span>Departemen</span><strong>{{ $pegawai->departemen ?? '-' }}</strong></div>
                        <div><span>Status enroll</span><strong>{{ $profile ? 'Terdaftar' : 'Belum terdaftar' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="card-title mb-1">Profil Pegawai</h5>
                    <p class="text-muted mb-0 small">Informasi dasar pegawai yang terkait dengan data wajah.</p>
                </div>
                <div class="card-body p-4">
                    <dl class="insightface-profile-list mb-0">
                        <div>
                            <dt>Nama</dt>
                            <dd>{{ $pegawai->nama }}</dd>
                        </div>
                        <div>
                            <dt>NIK</dt>
                            <dd>{{ $pegawai->nik }}</dd>
                        </div>
                        <div>
                            <dt>Departemen</dt>
                            <dd>{{ $pegawai->departemen ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd>{{ $pegawai->stts_aktif ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="card-title mb-1">Status Enroll</h5>
                    <p class="text-muted mb-0 small">Metadata enroll di Laravel dan tindakan yang tersedia.</p>
                </div>
                <div class="card-body p-4">
                    @if($profile)
                        <div class="insightface-status-block success mb-3">
                            <div class="fw-semibold">Wajah pegawai sudah terdaftar</div>
                            <div class="small text-muted mt-1">
                                Terdaftar: {{ $profile->enrolled_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('insightface.destroy', $pegawai->id) }}"
                              onsubmit="return confirm('Hapus data wajah pegawai ini dari InsightFace dan database?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-1"></i> Hapus Enroll
                            </button>
                        </form>
                    @else
                        <div class="insightface-status-block secondary">
                            <div class="fw-semibold">Belum ada data wajah</div>
                            <div class="small text-muted mt-1">Upload foto wajah di bawah untuk mendaftarkan pegawai ke server InsightFace.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="card-title mb-1">{{ $profile ? 'Re-enroll' : 'Enroll' }} Wajah</h5>
                    <p class="text-muted mb-0 small">Unggah foto referensi baru untuk mendaftarkan atau memperbarui wajah.</p>
                </div>
                <div class="card-body p-4">
                    @if(!config('insightface.enabled'))
                        <div class="alert alert-warning small mb-0 border-0">
                            InsightFace dinonaktifkan di <code>.env</code> (<code>INSIGHTFACE_ENABLED=false</code>).
                            Aktifkan dulu untuk melakukan proses enroll.
                        </div>
                    @else
                        <form method="POST" action="{{ route('insightface.enroll', $pegawai->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Foto wajah (JPEG/PNG)</label>
                                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                                <div class="form-text">Foto dikirim ke server InsightFace sebagai referensi wajah pegawai.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-camera me-1"></i> {{ $profile ? 'Perbarui Wajah' : 'Daftarkan Wajah' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <h5 class="card-title mb-1">Riwayat Verifikasi</h5>
                            <p class="text-muted mb-0 small">Log verifikasi wajah untuk pegawai ini, termasuk tipe, hasil, dan skor.</p>
                        </div>
                        <span class="badge text-bg-light border">{{ number_format($logs->total()) }} log</span>
                    </div>
                </div>
                <div class="card-body p-4 pt-3">
                    <div class="table-responsive">
                        <table class="table insightface-table align-middle table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Skor</th>
                                    <th>Shift</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    @php
                                        $badge = match($log->status) {
                                            'match' => 'success',
                                            'mismatch' => 'danger',
                                            'error' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="small text-muted">{{ $log->created_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                        <td><span class="badge text-bg-light border">{{ $log->tipe }}</span></td>
                                        <td><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ ucfirst($log->status) }}</span></td>
                                        <td class="fw-semibold">{{ $log->score !== null ? number_format($log->score, 0).'%' : '-' }}</td>
                                        <td>{{ $log->shift ?? '-' }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-link btn-sm p-0 log-detail-btn text-decoration-none" data-log-id="{{ $log->id }}">
                                                Detail
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted mb-1">Belum ada log verifikasi.</div>
                                            <small class="text-muted">Riwayat verifikasi akan tampil di sini setelah pegawai melalui proses verifikasi wajah.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 px-4 pb-4 pt-0">
                    {{ $logs->links('vendor.pagination.tabler') }}
                </div>
            </div>
        </div>
    </div>
</div>

@include('insightface.partials.log_modal')
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
    .insightface-profile-list {
        display: grid;
        gap: 1rem;
    }
    .insightface-profile-list div {
        padding-bottom: 0.85rem;
        border-bottom: 1px solid #f1f3f5;
    }
    .insightface-profile-list div:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .insightface-profile-list dt {
        margin-bottom: 0.35rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
    }
    .insightface-profile-list dd {
        margin-bottom: 0;
        font-weight: 600;
        color: #212529;
    }
    .insightface-status-block {
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid #edf1f5;
        background: #f8fafc;
    }
    .insightface-status-block.success {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    .insightface-status-block.secondary {
        background: #f8fafc;
        border-color: #e5e7eb;
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
    @media (max-width: 767.98px) {
        .insightface-meta div {
            flex-direction: column;
        }
    }
</style>
@endpush

@push('scripts')
@include('insightface.partials.log_modal_script')
@endpush
