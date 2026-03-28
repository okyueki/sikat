@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
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
                <form action="{{ route('surat_edaran.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="judul_surat" class="form-label">Judul Surat <span class="text-danger">*</span></label>
                                <input type="text" name="judul_surat" id="judul_surat" class="form-control" value="{{ old('judul_surat') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="nomor_surat" class="form-label">Nomor Surat</label>
                                <input type="text" name="nomor_surat" id="nomor_surat" class="form-control" value="{{ old('nomor_surat') }}">
                            </div>
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
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4">{{ old('deskripsi') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="file_pdf" class="form-label">File PDF <span class="text-danger">*</span></label>
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
                                @error('file_pdf')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('surat_edaran.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#nik_penandatangan').select2({
        placeholder: '-- Pilih pegawai --',
        allowClear: true,
        width: '100%'
    });
});

// Compact File Upload Functionality
(function() {
    var fileInput = document.getElementById('file_pdf');
    var uploadArea = document.getElementById('file-upload-area');
    var uploadTrigger = uploadArea.querySelector('.upload-trigger');
    var fileInfo = document.getElementById('file-info');
    var fileName = document.getElementById('file-name');
    var fileSize = document.getElementById('file-size');

    // Handle file selection
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            showFileInfo(this.files[0]);
        }
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function() {
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        var files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/pdf') {
            fileInput.files = files;
            showFileInfo(files[0]);
        } else if (files.length > 0) {
            alert('Hanya file PDF yang diizinkan.');
        }
    });

    function showFileInfo(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        uploadTrigger.style.display = 'none';
        fileInfo.style.display = 'flex';
    }

    window.clearFile = function() {
        fileInput.value = '';
        uploadTrigger.style.display = 'flex';
        fileInfo.style.display = 'none';
        fileName.textContent = '';
        fileSize.textContent = '';
    };

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
})();
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
