@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
@php
    $routePrefix = $routePrefix ?? 'surat_edaran';
    $numberHint = $numberFormatHint ?? "RS'ASF/EDR/urut/III.6.AU/I/bulan/tahun";
@endphp
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <h5 class="card-title">{{ $title }}</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route($routePrefix . '.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="judul_surat" class="form-label">Judul Surat <span class="text-danger">*</span></label>
                                <input type="text" name="judul_surat" id="judul_surat" class="form-control" value="{{ old('judul_surat') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="preview_nomor_surat" class="form-label">Nomor Surat</label>
                                <input type="text" id="preview_nomor_surat" class="form-control bg-light" value="{{ $previewNomorSurat ?? '' }}" readonly>
                                <small class="text-muted">Dibuat otomatis saat simpan. Format: {{ $numberHint }}</small>
                            </div>
                            @if(!empty($hasMasaBerlaku))
                            <div class="mb-3">
                                <label for="tanggal_mulai_berlaku" class="form-label">Tanggal mulai berlaku <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_mulai_berlaku" id="tanggal_mulai_berlaku" class="form-control" value="{{ old('tanggal_mulai_berlaku') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="tanggal_berakhir_berlaku" class="form-label">Tanggal berakhir berlaku <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_berakhir_berlaku" id="tanggal_berakhir_berlaku" class="form-control" value="{{ old('tanggal_berakhir_berlaku') }}" required>
                            </div>
                            @endif
                            <div class="mb-3">
                                <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" id="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="nik_penandatangan" class="form-label">Yang menyetujui (penandatangan)</label>
                                <select name="nik_penandatangan" id="nik_penandatangan" class="form-select select2-penandatangan" style="width: 100%;">
                                    <option value="">-- Pilih pegawai --</option>
                                    @foreach ($pegawai as $p)
                                        <option value="{{ $p->nik }}" {{ old('nik_penandatangan') == $p->nik ? 'selected' : '' }}>{{ $p->nama }} ({{ $p->nik }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(!empty($isSpo))
                            <div class="mb-3">
                                <label for="dep_terkait" class="form-label">Departemen Terkait</label>
                                <select name="dep_terkait[]" id="dep_terkait" class="form-select" multiple>
                                    @foreach(($departemenList ?? []) as $dep)
                                        <option value="{{ $dep->dep_id }}" {{ in_array($dep->dep_id, (array)($selectedDepartemenTerkait ?? []), true) ? 'selected' : '' }}>
                                            {{ $dep->nama }} ({{ $dep->dep_id }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Bisa pilih lebih dari satu departemen.</small>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4">{{ old('deskripsi') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="file_pdf" class="form-label">File PDF <span class="text-danger">*</span></label>
                                <p class="small text-muted border-start border-primary ps-2 mb-2 ms-1" style="border-width: 3px !important;">
                                    <i class="fe fe-info text-primary me-1"></i>
                                    Kalau file tidak diterima atau nanti tanda tangan di sistem tidak jalan, buka dokumen seperti biasa, pilih <strong>Cetak</strong>, simpan sebagai <strong>PDF</strong> baru, lalu upload file yang baru itu.
                                </p>
                                <!-- Compact File Upload -->
                                <div class="file-upload-compact" id="file-upload-area">
                                    <input type="file" name="file_pdf" id="file_pdf" accept=".pdf" required style="display: none;">
                                    <div class="upload-trigger" onclick="document.getElementById('file_pdf').click()">
                                        <div class="upload-icon">
                                            <i class="fe fe-upload-cloud"></i>
                                        </div>
                                        <div class="upload-text">
                                            <span class="fw-medium">Klik atau drop PDF di sini</span>
                                            <small class="text-muted d-block">Maks. 20 MB</small>
                                        </div>
                                    </div>
                                    <div class="file-info" id="file-info" style="display: none;">
                                        <div class="file-icon"><i class="fe fe-file-text text-primary"></i></div>
                                        <div class="file-details">
                                            <span class="file-name fw-medium" id="file-name"></span>
                                            <small class="file-size text-muted" id="file-size"></small>
                                        </div>
                                        <button type="button" class="btn-remove" onclick="clearFile()" title="Hapus file">
                                            <i class="fe fe-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="pdf-compat-warning" class="alert alert-warning mt-2 py-2 px-3 d-none" role="alert"></div>
                                @error('file_pdf')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            @include('surat_edaran.partials.stirling-ui-panel')
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('backend/assets/js/pdf-compat-warning.js') }}"></script>
<script src="{{ asset('backend/assets/js/surat-edaran-upload.js') }}"></script>
<script>
$(function() {
    $('#nik_penandatangan').select2({
        placeholder: '-- Pilih pegawai --',
        allowClear: true,
        width: '100%'
    });
    @if(!empty($isSpo))
    $('#dep_terkait').select2({
        placeholder: '-- Pilih departemen terkait --',
        allowClear: true,
        width: '100%'
    });
    @endif

    if (typeof initSuratEdaranCreateUpload === 'function') {
        initSuratEdaranCreateUpload();
    }
    if (typeof initPdfCompatibilityWarning === 'function') {
        initPdfCompatibilityWarning('file_pdf', 'pdf-compat-warning');
    }
});
</script>
<style>
/* Compact File Upload Styles */
.file-upload-compact {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 12px 16px;
    transition: all 0.2s ease;
    background: #fff;
}

.file-upload-compact:hover,
.file-upload-compact.dragover {
    border-color: #0d6efd;
    background: #f8f9ff;
}

.upload-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.upload-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.upload-icon i {
    font-size: 20px;
    color: #0d6efd;
}

.upload-text {
    font-size: 14px;
}

.upload-text small {
    font-size: 12px;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-icon {
    width: 40px;
    height: 40px;
    background: #e3f2fd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.file-icon i {
    font-size: 20px;
}

.file-details {
    flex: 1;
    min-width: 0;
}

.file-name {
    display: block;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-size {
    font-size: 12px;
}

.btn-remove {
    width: 28px;
    height: 28px;
    border: none;
    background: #f8f9fa;
    border-radius: 6px;
    color: #dc3545;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.btn-remove:hover {
    background: #dc3545;
    color: #fff;
}

.btn-remove i {
    font-size: 14px;
}
</style>
@endpush
