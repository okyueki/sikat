@extends('layouts.pages-layouts')

@section('pageTitle', 'Token QR Absensi Sholat Masjid')

@section('content')
<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Token QR Masjid</h5>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-muted small mb-4">
                    Satu token statis untuk semua karyawan. Scan QR di masjid RS untuk absensi sholat. 
                    Setelah generate token baru, token lama tidak berlaku. Cetak QR dan pasang di masjid.
                </p>

                @if($tokenAktif)
                    <div class="alert alert-info mb-4">
                        <strong>Token aktif saat ini</strong> — berlaku sampai 
                        <strong>{{ \Carbon\Carbon::parse($tokenAktif->valid_until)->format('d M Y') }}</strong>.
                    </div>
                @else
                    <div class="alert alert-warning mb-4">
                        Belum ada token aktif. Generate token baru dan tentukan masa berlaku, lalu cetak QR untuk dipasang di masjid.
                    </div>
                @endif

                <form action="{{ route('masjid_token.store') }}" method="POST" class="mb-4">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-5 mb-2 mb-md-0">
                            <label for="valid_until" class="form-label">Masa berlaku token sampai <span class="text-danger">*</span></label>
                            <input type="date" name="valid_until" id="valid_until" class="form-control" 
                                   value="{{ old('valid_until', now()->addMonths(3)->format('Y-m-d')) }}" 
                                   min="{{ now()->addDay()->format('Y-m-d') }}" required>
                            <small class="text-muted">Token lama otomatis tidak berlaku setelah generate baru.</small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-plus me-1"></i> Generate Token Baru &amp; Tampilkan QR
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($qrCodeUrl && $tokenString)
<div class="row">
    <div class="col-xl-6">
        <div class="card custom-card" id="card-qr-cetak">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">QR Code untuk Dicetak</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print();">
                    <i class="fe fe-printer me-1"></i> Cetak
                </button>
            </div>
            <div class="card-body text-center print-area">
                <p class="text-muted small mb-2">Scan QR ini di portal absensi sholat (dengan GPS di lokasi masjid).</p>
                <div class="mb-3">
                    <img src="{{ $qrCodeUrl }}" alt="QR Code Masjid" class="img-fluid" style="max-width: 280px;">
                </div>
                <p class="small text-muted mb-0">Token: <code class="user-select-all">{{ $tokenString }}</code></p>
                <p class="small text-muted mt-2">Berlaku sampai: <strong>{{ $tokenAktif ? \Carbon\Carbon::parse($tokenAktif->valid_until)->format('d M Y') : '-' }}</strong></p>
            </div>
        </div>
    </div>
</div>
@endif

<style>
@media print {
    body * { visibility: hidden; }
    #card-qr-cetak, #card-qr-cetak * { visibility: visible; }
    #card-qr-cetak { position: absolute; left: 0; top: 0; width: 100%; }
    .btn, .app-sidebar, .app-header, .main-content-header, nav, .no-print { display: none !important; }
}
</style>
@endsection
