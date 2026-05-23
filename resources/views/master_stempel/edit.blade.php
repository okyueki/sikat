@extends('layouts.pages-layouts')

@section('pageTitle', 'Stempel Perusahaan')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Stempel Perusahaan</h5>
                <a href="{{ route('surat_edaran.index') }}" class="btn btn-secondary btn-sm">Kembali ke Surat Edaran</a>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
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
                <p class="text-muted small">Upload gambar stempel (PNG) untuk ditampilkan di PDF bertanda tangan. Hanya satu stempel perusahaan.</p>
                <form action="{{ route('master_stempel.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama stempel (opsional)</label>
                        <input type="text" name="nama" id="nama" class="form-control" value="{{ old('nama', $stempel->nama ?? '') }}" placeholder="Contoh: Stempel Dinas Kesehatan">
                    </div>
                    <div class="mb-3">
                        <label for="file_stempel" class="form-label">File stempel (PNG) <span class="text-danger">*</span></label>
                        <!-- Compact File Upload with Crop -->
                        <div class="file-upload-compact" id="stempel-upload-area">
                            <input type="file" name="file_stempel" id="file_stempel" accept=".png,image/png" style="display: none;">
                            <input type="hidden" name="cropped_image" id="cropped_image">
                            
                            <div class="upload-trigger" id="upload-trigger" onclick="document.getElementById('file_stempel').click()">
                                <div class="upload-icon"><i class="fe fe-image"></i></div>
                                <div class="upload-text">
                                    <span class="fw-medium">Klik atau drop gambar stempel</span>
                                    <small class="text-muted d-block">PNG, maks. 2 MB</small>
                                </div>
                            </div>
                            
                            <div class="crop-preview" id="crop-preview" style="display: none;">
                                <div class="preview-container">
                                    <img id="preview-image" src="" alt="Preview">
                                </div>
                                <div class="crop-actions">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openCropModal()">
                                        <i class="fe fe-crop me-1"></i>Crop
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearStempel()">
                                        <i class="fe fe-trash-2 me-1"></i>Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        @if ($stempel && $stempel->file_path)
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <span class="text-muted small">Stempel saat ini:</span>
                                <img src="{{ $stempel->url }}" alt="Stempel" class="img-thumbnail" style="max-height:60px;">
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan (opsional)</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="2">{{ old('keterangan', $stempel->keterangan ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="qr_position" class="form-label">Posisi QR Verifikasi di PDF Sah</label>
                        <select name="qr_position" id="qr_position" class="form-select">
                            @php $currentQrPos = old('qr_position', $stempel->qr_position ?? 'bottom_right'); @endphp
                            <option value="bottom_right" {{ $currentQrPos === 'bottom_right' ? 'selected' : '' }}>Kanan Bawah</option>
                            <option value="bottom_left" {{ $currentQrPos === 'bottom_left' ? 'selected' : '' }}>Kiri Bawah</option>
                            <option value="top_right" {{ $currentQrPos === 'top_right' ? 'selected' : '' }}>Kanan Atas</option>
                            <option value="top_left" {{ $currentQrPos === 'top_left' ? 'selected' : '' }}>Kiri Atas</option>
                        </select>
                        <small class="text-muted">Pengaturan ini menentukan posisi QR verifikasi pada dokumen PDF yang sudah disahkan.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan stempel</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Cropper.js CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Gambar Stempel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="crop-container">
                    <img id="crop-image" src="" alt="Crop">
                </div>
                <div class="crop-toolbar mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cropper.rotate(-90)">
                        <i class="fe fe-rotate-ccw"></i> Rotate Left
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cropper.rotate(90)">
                        <i class="fe fe-rotate-cw"></i> Rotate Right
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cropper.reset()">
                        <i class="fe fe-refresh-cw"></i> Reset
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cropper.setAspectRatio(1)">
                        <i class="fe fe-square"></i> 1:1
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cropper.setAspectRatio(NaN)">
                        <i class="fe fe-maximize"></i> Free
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="applyCrop()">
                    <i class="fe fe-check me-1"></i>Terapkan Crop
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cropper = null;
let currentFile = null;

// File upload handling
document.getElementById('file_stempel').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    if (!file.type.includes('png') && !file.type.includes('image')) {
        alert('Hanya file PNG yang diizinkan.');
        return;
    }
    
    currentFile = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('preview-image').src = e.target.result;
        document.getElementById('upload-trigger').style.display = 'none';
        document.getElementById('crop-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
});

// Drag and drop
const uploadArea = document.getElementById('stempel-upload-area');
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});
uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});
uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('file_stempel').files = files;
        document.getElementById('file_stempel').dispatchEvent(new Event('change'));
    }
});

function openCropModal() {
    const imgSrc = document.getElementById('preview-image').src;
    document.getElementById('crop-image').src = imgSrc;
    
    const modal = new bootstrap.Modal(document.getElementById('cropModal'));
    modal.show();
    
    // Initialize cropper after modal is shown
    setTimeout(() => {
        const image = document.getElementById('crop-image');
        if (cropper) cropper.destroy();
        cropper = new Cropper(image, {
            aspectRatio: NaN,
            viewMode: 1,
            autoCropArea: 0.8,
            responsive: true,
            background: false,
        });
    }, 200);
}

function applyCrop() {
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        maxWidth: 1024,
        maxHeight: 1024,
        fillColor: 'transparent',
    });
    
    // Convert to PNG with transparency
    const croppedDataUrl = canvas.toDataURL('image/png');
    
    // Update preview
    document.getElementById('preview-image').src = croppedDataUrl;
    
    // Store in hidden input for form submission
    document.getElementById('cropped_image').value = croppedDataUrl;
    
    // Clear the file input since we're sending cropped data
    document.getElementById('file_stempel').value = '';
    
    bootstrap.Modal.getInstance(document.getElementById('cropModal')).hide();
}

function clearStempel() {
    document.getElementById('file_stempel').value = '';
    document.getElementById('cropped_image').value = '';
    document.getElementById('preview-image').src = '';
    document.getElementById('upload-trigger').style.display = 'flex';
    document.getElementById('crop-preview').style.display = 'none';
    currentFile = null;
}
</script>

<style>
/* Compact Upload Styles */
.file-upload-compact {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 16px;
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
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.upload-icon i {
    font-size: 24px;
    color: #0d6efd;
}
.upload-text {
    font-size: 14px;
}

/* Crop Preview */
.crop-preview {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.preview-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 120px;
}
.preview-container img {
    max-width: 100%;
    max-height: 200px;
    object-fit: contain;
    border-radius: 4px;
}
.crop-actions {
    display: flex;
    gap: 8px;
}

/* Crop Modal */
.crop-container {
    max-height: 400px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}
.crop-container img {
    max-width: 100%;
    display: block;
}
.crop-toolbar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

/* Cropper.js custom styles */
.cropper-view-box,
.cropper-face {
    border-radius: 0;
}
.cropper-point {
    background-color: #0d6efd;
}
.cropper-line {
    background-color: #0d6efd;
}
</style>
@endpush
