@extends('layouts.pages-layouts')

@section('pageTitle', 'Log Verifikasi InsightFace')

@section('content')
<div class="container-fluid page-body insightface-ui">
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('insightface.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard InsightFace
            </a>
        </div>
        <span class="badge text-bg-light border px-3 py-2">Ambang lolos {{ $minScore }}%</span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="row g-4 align-items-start">
                <div class="col-xl-8">
                    <span class="badge text-bg-light border mb-3">Audit dan Monitoring</span>
                    <h3 class="mb-2">Log verifikasi wajah pegawai</h3>
                    <p class="text-muted mb-0 insightface-lead">
                        Gunakan halaman ini untuk menelusuri histori verifikasi berdasarkan tanggal, status,
                        tipe, serta nama atau NIK pegawai saat presensi dan proses verify lainnya.
                    </p>
                </div>
                <div class="col-xl-4">
                    <div class="insightface-meta">
                        <div><span>Sumber data</span><strong>face_verification_logs</strong></div>
                        <div><span>Rentang aktif</span><strong>{{ $startDate }} s/d {{ $endDate }}</strong></div>
                        <div><span>Filter nama/NIK</span><strong>{{ $search !== '' ? $search : 'Semua data' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                <div>
                    <h5 class="card-title mb-1">Filter dan hasil log</h5>
                    <p class="text-muted mb-0 small">Gabungkan beberapa filter untuk menemukan mismatch, error, atau histori pegawai tertentu.</p>
                </div>
                <span class="badge text-bg-light border">{{ number_format($logs->total()) }} log</span>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('insightface.logs') }}" class="insightface-toolbar row g-3 align-items-end mb-4">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Dari</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Sampai</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusOptions as $opt)
                            <option value="{{ $opt }}" {{ $status === $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Tipe</label>
                    <select name="tipe" class="form-select">
                        <option value="">Semua</option>
                        @foreach($tipeOptions as $opt)
                            <option value="{{ $opt }}" {{ $tipe === $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="NIK / nama">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    <a href="{{ route('insightface.logs') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table insightface-table align-middle table-sm">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pegawai</th>
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
                                $pegawai = $pegawaiMap[$log->pegawai_id] ?? null;
                                $badge = match($log->status) {
                                    'match' => 'success',
                                    'mismatch' => 'danger',
                                    'error' => 'warning',
                                    default => 'secondary',
                                };
                                $scoreOk = $log->score !== null && $log->score >= $minScore;
                            @endphp
                            <tr>
                                <td class="small text-muted">{{ $log->created_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $pegawai->nama ?? '-' }}</div>
                                    <div class="small text-muted">
                                        @if($pegawai)
                                            <a href="{{ route('insightface.show', $log->pegawai_id) }}" class="text-decoration-none">{{ $log->nik }}</a>
                                        @else
                                            {{ $log->nik }}
                                        @endif
                                    </div>
                                </td>
                                <td><span class="badge text-bg-light border">{{ $log->tipe }}</span></td>
                                <td><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ ucfirst($log->status) }}</span></td>
                                <td>
                                    @if($log->score !== null)
                                        <span class="{{ $scoreOk ? 'text-success' : 'text-danger' }} fw-semibold">
                                            {{ number_format($log->score, 0) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $log->shift ?? '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-link btn-sm p-0 log-detail-btn text-decoration-none" data-log-id="{{ $log->id }}">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-1">Tidak ada log untuk filter ini.</div>
                                    <small class="text-muted">Ubah rentang tanggal atau kosongkan filter untuk melihat data lain.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
                <small class="text-muted">Badge warna menandai hasil verifikasi, skor merah berarti di bawah ambang lolos.</small>
                {{ $logs->links('vendor.pagination.tabler') }}
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
