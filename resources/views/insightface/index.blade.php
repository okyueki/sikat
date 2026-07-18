@extends('layouts.pages-layouts')

@section('pageTitle', 'InsightFace — Enroll Wajah')

@php
    $serviceBadge = $serviceStatus['enabled'] ? 'success' : 'secondary';
    $serverBadge = $serviceStatus['ping'] ? 'success' : 'danger';
@endphp

@section('content')
<div class="container-fluid page-body insightface-ui">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="insightface-hero card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="row g-4 align-items-center">
                <div class="col-xl-8">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-light border">Admin InsightFace</span>
                        <span class="badge bg-{{ $serviceBadge }}-subtle text-{{ $serviceBadge }} border border-{{ $serviceBadge }}-subtle">
                            {{ $serviceStatus['enabled'] ? 'Verifikasi aktif di env' : 'Verifikasi dimatikan di env' }}
                        </span>
                        @if($serviceStatus['enabled'])
                            <span class="badge bg-{{ $serverBadge }}-subtle text-{{ $serverBadge }} border border-{{ $serverBadge }}-subtle">
                                Server {{ $serviceStatus['ping'] ? 'online' : 'offline' }}
                            </span>
                        @endif
                    </div>
                    <h3 class="mb-2">Manajemen enroll wajah dan monitoring verifikasi pegawai</h3>
                    <p class="text-muted mb-0 insightface-lead">
                        Kelola pegawai yang sudah terdaftar di InsightFace, pantau hasil verifikasi terbaru,
                        dan lakukan re-enroll dengan tampilan yang lebih ringkas untuk kebutuhan operasional harian.
                    </p>
                </div>
                <div class="col-xl-4">
                    <div class="d-grid gap-2 d-sm-flex justify-content-xl-end flex-wrap">
                        <a href="{{ route('insightface.logs') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i> Semua Log
                        </a>
                        <a href="{{ route('insightface.enroll_search') }}" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Enroll Pegawai
                        </a>
                    </div>
                    <div class="insightface-meta mt-3">
                        <div><span>Base URL</span><strong>{{ $config['base_url'] }}</strong></div>
                        <div><span>Min score</span><strong>{{ $config['min_score'] }}%</strong></div>
                        <div><span>Mode</span><strong>{{ strtoupper($config['verify_mode']) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card insightface-stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="insightface-stat-label">Layanan InsightFace</span>
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">{{ $serviceStatus['enabled'] ? 'Siap dipakai' : 'Nonaktif' }}</h4>
                            <p class="text-muted mb-0 small">Status env dan konektivitas server verifikasi.</p>
                        </div>
                        <div class="insightface-stat-icon text-{{ $serviceBadge }}">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge bg-{{ $serviceBadge }}-subtle text-{{ $serviceBadge }}">{{ $serviceStatus['enabled'] ? 'Env aktif' : 'Env nonaktif' }}</span>
                        @if($serviceStatus['enabled'])
                            <span class="badge bg-{{ $serverBadge }}-subtle text-{{ $serverBadge }}">Ping {{ $serviceStatus['ping'] ? 'ok' : 'gagal' }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card insightface-stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="insightface-stat-label">Sudah enroll</span>
                    <div class="d-flex align-items-end justify-content-between gap-3">
                        <div>
                            <h2 class="mb-1">{{ number_format($stats['enrolled_total']) }}</h2>
                            <p class="text-muted mb-0 small">Metadata tersimpan di `pegawai_face_profiles`.</p>
                        </div>
                        <div class="insightface-stat-icon text-primary">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card insightface-stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="insightface-stat-label">Log hari ini</span>
                    <div class="d-flex align-items-end justify-content-between gap-3">
                        <div>
                            <h2 class="mb-1">{{ number_format($stats['logs_today']) }}</h2>
                            <p class="text-muted mb-0 small">Total histori: {{ number_format($stats['logs_total']) }} log.</p>
                        </div>
                        <div class="insightface-stat-icon text-dark">
                            <i class="fas fa-wave-square"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card insightface-stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="insightface-stat-label">Kualitas verifikasi hari ini</span>
                    <div class="d-flex align-items-end justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">
                                <span class="text-success">{{ $stats['match_today'] }}</span>
                                <span class="text-muted">/</span>
                                <span class="text-danger">{{ $stats['mismatch_today'] }}</span>
                                <span class="text-muted">/</span>
                                <span class="text-warning">{{ $stats['error_today'] }}</span>
                            </h4>
                            <p class="text-muted mb-0 small">Match / mismatch / error, ambang {{ $config['min_score'] }}%.</p>
                        </div>
                        <div class="insightface-stat-icon text-success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm mb-4">
        <i class="fas fa-info-circle me-1"></i>
        Wajah referensi tetap disimpan di <strong>server InsightFace</strong>. Laravel hanya menyimpan metadata enroll di <code>pegawai_face_profiles</code> dan histori verifikasi di <code>face_verification_logs</code>.
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                        <div>
                            <h5 class="card-title mb-1">Pegawai Terdaftar Wajah</h5>
                            <p class="text-muted mb-0 small">Cari pegawai yang sudah memiliki metadata enroll dan buka detail untuk re-enroll atau hapus data wajah.</p>
                        </div>
                        <span class="badge text-bg-light border">{{ number_format($profiles->total()) }} data</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('insightface.index') }}" class="insightface-toolbar row g-2 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small text-muted mb-1">Cari pegawai</label>
                            <input type="text" name="q" class="form-control" placeholder="Nama atau NIK..." value="{{ $search }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2 align-items-end">
                            <button type="submit" class="btn btn-primary flex-grow-1">Cari</button>
                            <a href="{{ route('insightface.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table insightface-table align-middle">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th>Departemen</th>
                                    <th>Waktu enroll</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($profiles as $row)
                                    @php $profile = $row['profile']; @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $row['nama'] }}</div>
                                            <div class="small text-muted">NIK {{ $profile->nik }} · {{ $row['stts_aktif'] ?: '-' }}</div>
                                        </td>
                                        <td>{{ $row['departemen'] ?: '-' }}</td>
                                        <td>
                                            <div>{{ $profile->enrolled_at?->timezone('Asia/Jakarta')->format('d-m-Y') }}</div>
                                            <div class="small text-muted">{{ $profile->enrolled_at?->timezone('Asia/Jakarta')->format('H:i') }}</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('insightface.show', $profile->pegawai_id) }}" class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted mb-1">Belum ada pegawai terdaftar.</div>
                                            <small class="text-muted">Mulai dari menu <strong>Enroll Pegawai</strong> untuk mendaftarkan wajah baru.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $profiles->links('vendor.pagination.tabler') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h5 class="card-title mb-1">Log Terbaru</h5>
                            <p class="text-muted mb-0 small">Pantau hasil verifikasi terkini tanpa harus membuka halaman log penuh.</p>
                        </div>
                        <a href="{{ route('insightface.logs') }}" class="btn btn-sm btn-link text-decoration-none px-0">Lihat semua</a>
                    </div>
                </div>
                <div class="card-body p-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-sm insightface-table mb-0">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Pegawai</th>
                                    <th>Status</th>
                                    <th>Skor</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLogs as $log)
                                    @php
                                        $nama = $logPegawaiMap[$log->pegawai_id]->nama ?? '-';
                                        $badge = match($log->status) {
                                            'match' => 'success',
                                            'mismatch' => 'danger',
                                            'error' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="small text-muted">{{ $log->created_at?->timezone('Asia/Jakarta')->format('d/m H:i') }}</td>
                                        <td>
                                            <div class="fw-semibold small">{{ $nama }}</div>
                                            <div class="small text-muted">{{ $log->nik }}</div>
                                        </td>
                                        <td><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ ucfirst($log->status) }}</span></td>
                                        <td class="fw-semibold">{{ $log->score !== null ? number_format($log->score, 0).'%' : '-' }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-link btn-sm p-0 log-detail-btn text-decoration-none" data-log-id="{{ $log->id }}">
                                                Detail
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada log verifikasi terbaru.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
    .insightface-hero {
        background: linear-gradient(135deg, #f8fbff 0%, #ffffff 55%, #f6f9ff 100%);
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
    .insightface-stat-card {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .insightface-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.08) !important;
    }
    .insightface-stat-label {
        display: inline-block;
        margin-bottom: 14px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6c757d;
    }
    .insightface-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        font-size: 1.1rem;
    }
    .insightface-toolbar {
        padding: 14px;
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
