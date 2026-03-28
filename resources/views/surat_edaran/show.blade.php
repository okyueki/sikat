@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $title }}</h5>
                <div>
                    @if(!$surat_edaran->tanggal_ditandatangani)
                        <a href="{{ route('surat_edaran.tandaTangani', $surat_edaran) }}" class="btn btn-info btn-sm">Tanda tangani PDF</a>
                        <a href="{{ route('surat_edaran.edit', $surat_edaran) }}" class="btn btn-warning btn-sm">Edit</a>
                    @else
                        <a href="{{ route('surat_edaran.generateSignedPdf', $surat_edaran) }}" class="btn btn-success btn-sm" target="_blank">Download PDF bertanda tangan</a>
                    @endif
                    <form action="{{ route('surat_edaran.destroy', $surat_edaran) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus dokumen ini?{{ $surat_edaran->tanggal_ditandatangani ? " Dokumen yang sudah ditandatangani akan dihapus permanen." : "" }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">{{ $surat_edaran->tanggal_ditandatangani ? 'Hapus' : 'Hapus Draft' }}</button>
                    </form>
                    <a href="{{ route('surat_edaran.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <!-- Status Card Info -->
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="fe fe-info me-2"></i>
                    <div>
                        <strong>Status Dokumen:</strong> 
                        @if($surat_edaran->tanggal_ditandatangani)
                            <span class="badge bg-success">Sah</span> - Ditandatangani {{ $surat_edaran->tanggal_ditandatangani->format('d-m-Y H:i') }}
                        @else
                            <span class="badge bg-warning text-dark">Draft</span> - Belum ditandatangani
                        @endif
                    </div>
                </div>

                <!-- Document Info in Modern Layout -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fe fe-file-text me-2"></i>Informasi Dokumen</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Judul Surat</div>
                                    <div class="col-sm-8 fw-medium">{{ $surat_edaran->judul_surat }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Nomor Surat</div>
                                    <div class="col-sm-8">{{ $surat_edaran->nomor_surat ?? '-' }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Tanggal</div>
                                    <div class="col-sm-8">{{ $surat_edaran->tanggal ? $surat_edaran->tanggal->format('d-m-Y') : '-' }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Yang menyetujui</div>
                                    <div class="col-sm-8">{{ $surat_edaran->penandatangan ? $surat_edaran->penandatangan->nama . ' (' . $surat_edaran->penandatangan->nik . ')' : '-' }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Deskripsi</div>
                                    <div class="col-sm-8">{{ $surat_edaran->deskripsi ?: '-' }}</div>
                                </div>
                                @if($surat_edaran->tanggal_ditandatangani)
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Ditandatangani (sah)</div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-success">{{ $surat_edaran->tanggal_ditandatangani->format('d-m-Y H:i') }}</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fe fe-file me-2"></i>File PDF</h6>
                            </div>
                            <div class="card-body text-center">
                                @if ($surat_edaran->file_pdf)
                                    <div class="mb-3">
                                        <i class="fe fe-file-text" style="font-size: 3rem; color: #6c757d;"></i>
                                    </div>
                                    <a href="{{ route('surat_edaran.streamPdf', $surat_edaran) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                        <i class="fe fe-eye me-1"></i>Buka PDF
                                    </a>
                                    @if($surat_edaran->tanggal_ditandatangani)
                                    <a href="{{ route('surat_edaran.generateSignedPdf', $surat_edaran) }}" class="btn btn-success btn-sm w-100">
                                        <i class="fe fe-download me-1"></i>Download PDF Sah
                                    </a>
                                    @endif
                                @else
                                    <div class="text-muted">
                                        <i class="fe fe-file" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">File PDF tidak tersedia</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PDF Preview Section -->
                @if ($surat_edaran->file_pdf)
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fe fe-eye me-2"></i>Preview PDF</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="pdf-preview-container" style="height: 600px; overflow: auto;">
                            <iframe src="{{ route('surat_edaran.streamPdf', $surat_edaran) }}" 
                                    width="100%" 
                                    height="100%" 
                                    style="border: none;"
                                    frameborder="0">
                            </iframe>
                        </div>
                    </div>
                </div>
                @endif

                @if($surat_edaran->tanggal_ditandatangani && !empty($verificationQrDataUri))
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fe fe-shield me-2"></i>Verifikasi Keabsahan Dokumen</h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <img src="{{ $verificationQrDataUri }}" alt="QR Verifikasi Surat Edaran" style="max-width: 180px;">
                            </div>
                            <div class="col-md-9">
                                <p class="mb-2">
                                    Scan QR ini untuk memverifikasi bahwa dokumen ini sah dan melihat jejak audit aktivitas dokumen.
                                </p>
                                <a href="{{ $verifyUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    Buka Halaman Verifikasi
                                </a>
                                <div class="small text-muted mt-2">{{ $verifyUrl }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
