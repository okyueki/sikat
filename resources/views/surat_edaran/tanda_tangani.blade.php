@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600&family=Pacifico&family=Great+Vibes&family=Sacramento&display=swap" rel="stylesheet">
<style>
.signature-panel { max-width: 320px; }
.signature-cursive { font-family: 'Dancing Script', cursive; font-size: 1.1rem; font-weight: 600; }
.signature-font-1 { font-family: 'Dancing Script', cursive !important; font-weight: 600; }
.signature-font-2 { font-family: 'Pacifico', cursive !important; }
.signature-font-3 { font-family: 'Great Vibes', cursive !important; font-weight: 400; }
.signature-font-4 { font-family: 'Sacramento', cursive !important; font-size: 1.3rem; }
.jenis-option { cursor: pointer; border: 2px solid #dee2e6; border-radius: 8px; padding: 12px; text-align: center; transition: all 0.2s; }
.jenis-option:hover { border-color: #adb5bd; }
.jenis-option.active { border-color: #dc3545; background: #fff5f5; }
.draggable-field { cursor: grab; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; background: #fff; }
.draggable-field:hover { border-color: #0d6efd; background: #f8f9fa; }
.draggable-field:active { cursor: grabbing; }
.drag-handle { color: #6c757d; font-size: 1rem; user-select: none; }
.placement-box { position: absolute; border: 2px solid #0d6efd; border-radius: 4px; background: rgba(255,255,255,0.9); padding: 4px 8px; cursor: move; min-width: 40px; min-height: 20px; box-sizing: border-box; }
.placement-box .placement-remove { position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; border-radius: 50%; background: #dc3545; color: #fff; border: none; cursor: pointer; font-size: 12px; line-height: 1; display: flex; align-items: center; justify-content: center; z-index: 2; }
.placement-box .placement-text { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
.placement-resize-handle { position: absolute; width: 10px; height: 10px; background: #0d6efd; border: 1px solid #fff; border-radius: 2px; cursor: nwse-resize; z-index: 1; }
.placement-resize-handle.se { right: -1px; bottom: -1px; cursor: nwse-resize; }
.placement-resize-handle.sw { left: -1px; bottom: -1px; cursor: nesw-resize; }
.placement-resize-handle.ne { right: -1px; top: -1px; cursor: nesw-resize; }
.placement-resize-handle.nw { left: -1px; top: -1px; cursor: nwse-resize; }
#pdf-drop-zone { position: absolute; left: 0; top: 0; right: 0; bottom: 0; }
.badge-count { min-width: 20px; height: 20px; border-radius: 50%; background: #0d6efd; color: #fff; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; }
.btn-tandatangani { background: #dc3545; border-color: #dc3545; font-weight: 600; }
.btn-tandatangani:hover { background: #bb2d3b; border-color: #b02a37; color: #fff; }
.modal-signature-option { cursor: pointer; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; }
.modal-signature-option:hover { border-color: #0d6efd; }
.modal-signature-option.selected { border-color: #198754; background: #f0fff4; }
.color-dot { width: 28px; height: 28px; border-radius: 50%; cursor: pointer; display: inline-block; margin-right: 8px; border: 2px solid transparent; }
.color-dot.selected { border-color: #333; box-shadow: 0 0 0 2px #fff; }
.stempel-preview { width: 32px; height: 32px; object-fit: contain; border-radius: 4px; background: #f8f9fa; }
.hint-text { font-size: 10px; color: #6c757d; font-style: italic; }
.draggable-field .hint-icon { font-size: 11px; color: #6c757d; }
.signature-type-option { cursor: pointer; border: 2px solid #dee2e6; border-radius: 8px; padding: 10px 12px; text-align: center; transition: all 0.2s; flex: 1; }
/* Loading overlay */
.loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.85); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 100; border-radius: 4px; }
.loading-overlay .spinner-border { width: 3rem; height: 3rem; }
.loading-overlay .loading-text { margin-top: 12px; font-size: 14px; color: #6c757d; }
/* Drag ghost preview */
.drag-ghost { position: fixed; pointer-events: none; z-index: 9999; background: rgba(255,255,255,0.95); border: 2px dashed #0d6efd; border-radius: 6px; padding: 8px 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 13px; font-weight: 500; color: #0d6efd; transform: translate(-50%, -50%); display: none; }
.drag-ghost.visible { display: block; }
/* Drop zone highlight */
#pdf-drop-zone.drag-over { background: rgba(13, 110, 253, 0.1) !important; border: 2px dashed #0d6efd; }
/* Zoom controls */
.zoom-controls { display: flex; align-items: center; gap: 4px; }
.zoom-controls .btn { padding: 2px 8px; font-size: 12px; }
.zoom-controls .zoom-level { font-size: 12px; min-width: 50px; text-align: center; }
/* Selection visual - selected placement box */
.placement-box.selected { border-color: #dc3545 !important; box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25), 0 2px 8px rgba(0,0,0,0.15); z-index: 10; }
.placement-box.selected .placement-resize-handle { background: #dc3545; }
/* Snap grid */
.snap-grid { position: absolute; left: 0; top: 0; pointer-events: none; z-index: 5; }
.snap-grid.visible .grid-line-h, .snap-grid.visible .grid-line-v { stroke: rgba(13, 110, 253, 0.15); }
/* Alignment guide lines */
.align-guide { position: absolute; background: #dc3545; z-index: 50; pointer-events: none; display: none; }
.align-guide.horizontal { height: 1px; left: 0; right: 0; }
.align-guide.vertical { width: 1px; top: 0; bottom: 0; }
.align-guide.visible { display: block; }
/* Summary counter */
.placement-summary { font-size: 11px; padding: 6px 10px; background: #f8f9fa; border-radius: 4px; border: 1px solid #dee2e6; }
.placement-summary .summary-item { display: inline-flex; align-items: center; gap: 4px; margin-right: 10px; }
.placement-summary .summary-dot { width: 8px; height: 8px; border-radius: 50%; }
.signature-type-option:hover { border-color: #adb5bd; }
.signature-type-option.active { border-color: #dc3545; background: #fff5f5; }
.signature-type-option i { font-size: 1.5rem; }
.signature-image-preview { max-width: 200px; max-height: 80px; object-fit: contain; border: 1px dashed #dee2e6; border-radius: 6px; padding: 8px; background: #f8f9fa; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">{{ $title }} – {{ $surat_edaran->judul_surat }}
        @if($surat_edaran->tanggal_ditandatangani)
            <span class="badge bg-success ms-2" title="Ditandatangani pada {{ $surat_edaran->tanggal_ditandatangani->format('d-m-Y H:i') }}">Sah</span>
        @endif
    </h5>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('master_stempel.edit') }}" class="btn btn-outline-secondary btn-sm">Stempel perusahaan</a>
        <a href="{{ route('surat_edaran.show', $surat_edaran) }}" class="btn btn-secondary btn-sm">Kembali</a>
        <a href="{{ route('surat_edaran.generateSignedPdf', $surat_edaran) }}" class="btn btn-success btn-sm" target="_blank">Download PDF bertanda tangan</a>
    </div>
</div>

<div class="row g-3">
    <!-- Kiri: PDF viewer + area drop -->
    <div class="col-lg-8 order-2 order-lg-1">
        <div class="card h-100">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0 small">Halaman:</label>
                        <select id="page-select" class="form-select form-select-sm" style="width:auto;"></select>
                        <span class="small text-muted" id="page-info">Page 1 of 1</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="zoom-controls">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="zoom-out" title="Perkecil"><i class="fe fe-minus"></i></button>
                            <span class="zoom-level text-muted" id="zoom-level">100%</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="zoom-in" title="Perbesar"><i class="fe fe-plus"></i></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="zoom-fit" title="Sesuaikan"><i class="fe fe-maximize"></i></button>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="snap-grid-toggle">
                            <label class="form-check-label small" for="snap-grid-toggle">Grid</label>
                        </div>
                    </div>
                </div>
                <!-- Placement summary -->
                <div class="placement-summary mb-2" id="placement-summary">
                    <span class="summary-item"><span class="summary-dot" style="background:#dc3545;"></span> TTD: <strong id="summary-signature">0</strong></span>
                    <span class="summary-item"><span class="summary-dot" style="background:#0d6efd;"></span> Lainnya: <strong id="summary-other">0</strong></span>
                    <span class="summary-item text-muted">| Halaman ini: <strong id="summary-current-page">0</strong></span>
                </div>
                <div id="pdf-container" style="position:relative; background:#e9ecef; min-height:520px; overflow:auto;">
                    <div id="pdf-loading-overlay" class="loading-overlay">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <div class="loading-text">Memuat dokumen PDF...</div>
                    </div>
                    <canvas id="pdf-canvas"></canvas>
                    <div id="pdf-drop-zone"></div>
                    <!-- Snap grid SVG -->
                    <svg id="snap-grid" class="snap-grid"></svg>
                    <!-- Alignment guides -->
                    <div id="align-guide-h" class="align-guide horizontal"></div>
                    <div id="align-guide-v" class="align-guide vertical"></div>
                    <div id="placement-boxes"></div>
                </div>
                <!-- Ghost preview for drag -->
                <div id="drag-ghost" class="drag-ghost"></div>
            </div>
        </div>
    </div>

    <!-- Kanan: Pilihan tanda tangan (seperti referensi) -->
    <div class="col-lg-4 order-1 order-lg-2">
        <div class="card signature-panel">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0 fw-bold">Pilihan tanda tangan</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted mb-2">Jenis</label>
                    <div class="d-flex gap-2">
                        <div class="jenis-option active flex-grow-1" data-jenis="sederhana" title="Tanda Tangan Sederhana">
                            <i class="fe fe-edit-3 d-block mb-1"></i>
                            <span class="small">Tanda Tangan Sederhana</span>
                        </div>
                        <div class="jenis-option flex-grow-1 opacity-50" data-jenis="digital" title="Tanda Tangan Digital (Premium)">
                            <i class="fe fe-award d-block mb-1"></i>
                            <span class="small">Tanda Tangan Digital</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Pakai master tanda tangan</label>
                    <div class="d-flex gap-1 flex-wrap align-items-center">
                        <select class="form-select form-select-sm" id="master-ttd-select" style="max-width:220px;">
                            <option value="">Sesuaikan manual</option>
                            @foreach($masterTandaTanganList ?? [] as $m)
                            <option value="{{ $m->id }}" data-nama="{{ e($m->nama_lengkap) }}" data-inisial="{{ e($m->inisial ?? '') }}" data-font="{{ $m->font_style ?? '1' }}" data-color="{{ e($m->color ?? '#000000') }}">{{ $m->nama_lengkap }} ({{ \App\Models\MasterTandaTangan::fontStyleLabel($m->font_style ?? '1') }})</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-save-master-ttd" title="Simpan pengaturan saat ini ke master">Simpan ke master</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted mb-2 d-flex justify-content-between align-items-center">
                        <span>Kotak isian wajib diisi</span>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" id="btn-open-signature-modal" title="Tentukan detail tanda tangan">Atur detail</button>
                    </label>
                    <div id="field-signature" class="draggable-field" draggable="true" data-field-type="signature">
                        <span class="drag-handle">⋮⋮</span>
                        <span class="signature-cursive flex-grow-1" id="preview-signature">{{ $signatureDetail['nama_lengkap'] ?? 'Tanda tangan' }}</span>
                        <span class="badge-count" id="count-signature">0</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted mb-2">Kotak isian opsional</label>
                    <div id="field-inisial" class="draggable-field" draggable="true" data-field-type="inisial">
                        <span class="drag-handle">⋮⋮</span>
                        <span class="signature-cursive flex-grow-1" id="preview-inisial">{{ $signatureDetail['inisial'] ?? 'Inisial' }}</span>
                    </div>
                    <div id="field-nama" class="draggable-field" draggable="true" data-field-type="nama">
                        <span class="drag-handle">⋮⋮</span>
                        <i class="fe fe-user text-muted"></i>
                        <span class="small flex-grow-1">Nama</span>
                    </div>
                    <div id="field-tanggal" class="draggable-field" draggable="true" data-field-type="tanggal">
                        <span class="drag-handle">⋮⋮</span>
                        <i class="fe fe-calendar text-muted"></i>
                        <span class="small flex-grow-1">Tanggal</span>
                    </div>
                    <div id="field-teks" class="draggable-field" draggable="true" data-field-type="teks" title="Double-click pada kotak untuk edit teks">
                        <span class="drag-handle">⋮⋮</span>
                        <i class="fe fe-type text-muted"></i>
                        <span class="small flex-grow-1">
                            Teks
                            <span class="hint-text d-block">Double-click untuk edit</span>
                        </span>
                    </div>
                    <div id="field-stempel" class="draggable-field" draggable="true" data-field-type="stempel">
                        <span class="drag-handle">⋮⋮</span>
                        @if($masterStempel && $masterStempel->url)
                            <img src="{{ $masterStempel->url }}" alt="Stempel" class="stempel-preview">
                        @else
                            <i class="fe fe-bookmark text-muted"></i>
                        @endif
                        <span class="small flex-grow-1">
                            Stempel Perusahaan
                            @if(!$masterStempel || !$masterStempel->url)
                                <span class="hint-text d-block text-warning">Belum diatur</span>
                            @endif
                        </span>
                    </div>
                </div>

                <div class="mb-2 d-none">
                    <label class="form-label small text-muted mb-1">Nama lengkap / Inisial</label>
                    <input type="text" id="nama_lengkap" class="form-control form-control-sm mb-1" value="{{ $signatureDetail['nama_lengkap'] ?? '' }}" placeholder="Nama Anda">
                    <input type="text" id="inisial" class="form-control form-control-sm" value="{{ $signatureDetail['inisial'] ?? '' }}" placeholder="Inisial">
                </div>
                <input type="hidden" id="font_style" value="{{ $signatureDetail['font_style'] ?? '1' }}">
                <input type="hidden" id="signature_type" value="{{ $signatureDetail['type'] ?? 'text' }}">
                <input type="hidden" id="signature_image_url" value="{{ $signatureDetail['image_url'] ?? '' }}">
                <div class="mb-3 d-none">
                    <input type="color" id="color" class="form-control form-control-color d-inline-block" value="{{ $signatureDetail['color'] ?? '#000000' }}" style="width:2rem;height:1.8rem;" title="Warna">
                </div>

                <button type="button" id="btn-tandatangani" class="btn btn-tandatangani w-100 py-2">
                    <i class="fe fe-plus me-1"></i> Tanda tangani
                </button>
                <button type="button" id="btn-save" class="btn btn-outline-primary btn-sm w-100 mt-2">Simpan posisi</button>
                <button type="button" id="btn-reset" class="btn btn-outline-secondary btn-sm w-100 mt-2" title="Hapus semua kotak isian dari dokumen">
                    <i class="fe fe-refresh-cw me-1"></i> Reset semua posisi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Tentukan detail tanda tangan -->
<div class="modal fade" id="modalSignatureDetail" tabindex="-1" aria-labelledby="modalSignatureDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalSignatureDetailLabel">Tentukan detail tanda tangan Anda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-1">
                <!-- Pilih tipe tanda tangan -->
                <div class="mb-3">
                    <label class="form-label small text-muted mb-2">Pilih metode tanda tangan</label>
                    <div class="d-flex gap-2">
                        <div class="signature-type-option active" data-type="text">
                            <i class="fe fe-type d-block mb-1"></i>
                            <span class="small">Font Teks</span>
                        </div>
                        <div class="signature-type-option" data-type="image">
                            <i class="fe fe-image d-block mb-1"></i>
                            <span class="small">Upload Gambar</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted">Nama lengkap</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fe fe-user text-danger"></i></span>
                        <input type="text" class="form-control" id="modal_nama_lengkap" placeholder="Nama Anda" value="{{ $signatureDetail['nama_lengkap'] ?? '' }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Inisial</label>
                    <input type="text" class="form-control form-control-sm" id="modal_inisial" placeholder="Contoh: OAK" value="{{ $signatureDetail['inisial'] ?? '' }}">
                </div>

                <!-- Konten tipe TEXT -->
                <div id="signature-type-text-content">
                    <ul class="nav nav-tabs nav-tabs-sm mb-3" id="modalSignatureTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-tandatangani" data-bs-toggle="tab" data-bs-target="#tab-pane-tandatangani" type="button" role="tab"><i class="fe fe-edit-3 me-1"></i>Gaya Font</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-warna" data-bs-toggle="tab" data-bs-target="#tab-pane-warna" type="button" role="tab">Warna</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="modalSignatureTabContent">
                        <div class="tab-pane fade show active" id="tab-pane-tandatangani" role="tabpanel">
                            <p class="small text-muted mb-2">Pilih gaya font tanda tangan</p>
                        <div id="modal-font-options">
                            <div class="modal-signature-option d-flex align-items-center selected" data-font="1">
                                <span class="form-check me-2"><input class="form-check-input" type="radio" name="modal_font" value="1" id="font1" checked></span>
                                <span class="signature-font-1 flex-grow-1 modal-preview-name">Okyanto Agung K</span>
                            </div>
                            <div class="modal-signature-option d-flex align-items-center" data-font="2">
                                <span class="form-check me-2"><input class="form-check-input" type="radio" name="modal_font" value="2" id="font2"></span>
                                <span class="signature-font-2 flex-grow-1 modal-preview-name">Okyanto Agung K</span>
                            </div>
                            <div class="modal-signature-option d-flex align-items-center" data-font="3">
                                <span class="form-check me-2"><input class="form-check-input" type="radio" name="modal_font" value="3" id="font3"></span>
                                <span class="signature-font-3 flex-grow-1 modal-preview-name">Okyanto Agung K</span>
                            </div>
                            <div class="modal-signature-option d-flex align-items-center" data-font="4">
                                <span class="form-check me-2"><input class="form-check-input" type="radio" name="modal_font" value="4" id="font4"></span>
                                <span class="signature-font-4 flex-grow-1 modal-preview-name">Okyanto Agung K</span>
                            </div>
                        </div>
                        </div>
                        <div class="tab-pane fade" id="tab-pane-warna" role="tabpanel">
                            <p class="small text-muted mb-2">Pilih warna tanda tangan</p>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="color-dot selected" data-color="#000000" style="background:#000" title="Hitam"></span>
                                <span class="color-dot" data-color="#dc3545" style="background:#dc3545" title="Merah"></span>
                                <span class="color-dot" data-color="#0d6efd" style="background:#0d6efd" title="Biru"></span>
                                <span class="color-dot" data-color="#198754" style="background:#198754" title="Hijau"></span>
                            </div>
                        </div>
                    </div>
                </div><!-- end #signature-type-text-content -->

                <!-- Konten tipe IMAGE -->
                <div id="signature-type-image-content" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Upload gambar tanda tangan (PNG)</label>
                        <input type="file" class="form-control form-control-sm" id="modal_file_ttd" accept=".png,image/png">
                        <small class="text-muted">Format PNG dengan background transparan, maks. 2 MB</small>
                    </div>
                    <div class="mb-3" id="signature-image-preview-container" style="display:none;">
                        <label class="form-label small text-muted">Preview</label>
                        <div>
                            <img id="signature-image-preview" class="signature-image-preview" src="" alt="Preview">
                        </div>
                    </div>
                    <div class="alert alert-info small py-2">
                        <i class="fe fe-info me-1"></i> Tips: Gunakan gambar tanda tangan dengan latar belakang transparan agar terlihat natural di dokumen.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                <button type="button" class="btn btn-danger" id="modal-signature-apply">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Teks kustom (untuk kotak isian Teks) -->
<div class="modal fade" id="modalTeksKustom" tabindex="-1" aria-labelledby="modalTeksKustomLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTeksKustomLabel">Teks kustom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small text-muted">Isi teks yang akan ditampilkan di PDF</label>
                <textarea class="form-control" id="modal_teks_value" rows="3" placeholder="Contoh: Disahkan oleh Kepala Dinas..."></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                <button type="button" class="btn btn-primary" id="modal-teks-apply">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="save-url" value="{{ route('surat_edaran.saveSignature', $surat_edaran) }}">
<input type="hidden" id="csrf-token" value="{{ csrf_token() }}">
<input type="hidden" id="redirect-url" value="{{ route('surat_edaran.show', $surat_edaran) }}">
@endsection

@push('scripts')
<script src="{{ asset('assets/libs/pdfjs/build/pdf.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('assets/libs/pdfjs/build/pdf.worker.js') }}";
    }
    var pdfUrl = "{{ $pdfUrl }}";
    var container = document.getElementById('pdf-container');
    var canvas = document.getElementById('pdf-canvas');
    var ctx = canvas.getContext('2d');
    var pageSelect = document.getElementById('page-select');
    var dropZone = document.getElementById('pdf-drop-zone');
    var placementBoxes = document.getElementById('placement-boxes');
    var currentPage = 1;
    var pdfDoc = null;
    var scale = 1.4;
    var pageHeight = 0, pageWidth = 0;
    var MM_TO_PX = 2.83465;
    var placements = @json($placementsForJs ?? []);
    var stempelUrl = @if($masterStempel && $masterStempel->file_path)"{{ Storage::disk('public')->url($masterStempel->file_path) }}"@else null @endif;
    var selectedBox = null;
    var resizing = null;
    var teksEditPlacementIndex = -1;
    var LABELS = { signature: 'Tanda tangan', inisial: 'Inisial', nama: 'Nama', tanggal: 'Tanggal', teks: 'Teks', stempel: 'Stempel' };
    var isDirty = false; // Track unsaved changes
    var dragGhost = document.getElementById('drag-ghost');
    var currentDragging = null;
    var selectedPlacementIndex = -1; // Track selected placement
    var snapEnabled = false;
    var SNAP_THRESHOLD = 5; // mm
    var GRID_SIZE = 10; // mm

    function getDisplayValue(fieldType) {
        if (fieldType === 'signature') return document.getElementById('nama_lengkap').value || 'Tanda tangan';
        if (fieldType === 'inisial') return document.getElementById('inisial').value || 'Inisial';
        if (fieldType === 'nama') return document.getElementById('nama_lengkap').value || 'Nama penandatangan';
        if (fieldType === 'tanggal') return new Date().toLocaleDateString('id-ID');
        return LABELS[fieldType] || fieldType;
    }

    // Jenis option toggle
    document.querySelectorAll('.jenis-option').forEach(function(el) {
        el.addEventListener('click', function() {
            if (this.classList.contains('opacity-50')) return;
            document.querySelectorAll('.jenis-option').forEach(function(x) { x.classList.remove('active'); });
            this.classList.add('active');
        });
    });

    // Update preview when nama/inisial change (when not using modal)
    document.getElementById('nama_lengkap').addEventListener('input', function() {
        document.getElementById('preview-signature').textContent = this.value || 'Tanda tangan';
    });
    document.getElementById('inisial').addEventListener('input', function() {
        document.getElementById('preview-inisial').textContent = this.value || 'Inisial';
    });

    // Modal: Tentukan detail tanda tangan
    var modalEl = document.getElementById('modalSignatureDetail');
    var modalNama = document.getElementById('modal_nama_lengkap');
    var modalInisial = document.getElementById('modal_inisial');
    var modalApply = document.getElementById('modal-signature-apply');
    var currentSignatureType = document.getElementById('signature_type').value || 'text';
    var uploadedImageUrl = document.getElementById('signature_image_url').value || '';
    var uploadedImageFile = null;

    function updateModalPreviewNames() {
        var name = modalNama.value || 'Okyanto Agung K';
        document.querySelectorAll('#modal-font-options .modal-preview-name').forEach(function(el) { el.textContent = name; });
    }

    // Signature type toggle (text vs image)
    document.querySelectorAll('.signature-type-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.signature-type-option').forEach(function(o) { o.classList.remove('active'); });
            this.classList.add('active');
            currentSignatureType = this.dataset.type;
            document.getElementById('signature_type').value = currentSignatureType;
            
            // Toggle content visibility
            document.getElementById('signature-type-text-content').style.display = currentSignatureType === 'text' ? 'block' : 'none';
            document.getElementById('signature-type-image-content').style.display = currentSignatureType === 'image' ? 'block' : 'none';
        });
    });

    // Image upload preview
    document.getElementById('modal_file_ttd').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            if (!file.type.match('image/png')) {
                alert('Hanya file PNG yang diperbolehkan.');
                this.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2 MB.');
                this.value = '';
                return;
            }
            uploadedImageFile = file;
            var reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('signature-image-preview').src = ev.target.result;
                document.getElementById('signature-image-preview-container').style.display = 'block';
                uploadedImageUrl = ev.target.result; // Data URL for preview
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('btn-open-signature-modal').addEventListener('click', function() {
        modalNama.value = document.getElementById('nama_lengkap').value;
        modalInisial.value = document.getElementById('inisial').value;
        var font = document.getElementById('font_style').value || '1';
        document.querySelectorAll('input[name="modal_font"]').forEach(function(r) { r.checked = (r.value === font); });
        document.querySelectorAll('#modal-font-options .modal-signature-option').forEach(function(o) {
            o.classList.toggle('selected', o.dataset.font === font);
        });
        var color = document.getElementById('color').value || '#000000';
        document.querySelectorAll('#modalSignatureDetail .color-dot').forEach(function(d) {
            d.classList.toggle('selected', (d.dataset.color || '').toLowerCase() === color.toLowerCase());
        });
        
        // Restore signature type selection
        var sigType = document.getElementById('signature_type').value || 'text';
        document.querySelectorAll('.signature-type-option').forEach(function(o) {
            o.classList.toggle('active', o.dataset.type === sigType);
        });
        document.getElementById('signature-type-text-content').style.display = sigType === 'text' ? 'block' : 'none';
        document.getElementById('signature-type-image-content').style.display = sigType === 'image' ? 'block' : 'none';
        
        // Show existing image preview if available
        var existingUrl = document.getElementById('signature_image_url').value;
        if (existingUrl) {
            document.getElementById('signature-image-preview').src = existingUrl;
            document.getElementById('signature-image-preview-container').style.display = 'block';
        }
        
        updateModalPreviewNames();
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            new bootstrap.Modal(modalEl).show();
        } else { modalEl.classList.add('show'); modalEl.style.display = 'block'; }
    });
    modalNama.addEventListener('input', updateModalPreviewNames);
    document.querySelectorAll('#modal-font-options .modal-signature-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            var font = this.dataset.font;
            document.getElementById('font_style').value = font;
            document.querySelector('input[name="modal_font"][value="' + font + '"]').checked = true;
            document.querySelectorAll('#modal-font-options .modal-signature-option').forEach(function(o) { o.classList.remove('selected'); });
            this.classList.add('selected');
        });
    });
    document.querySelectorAll('input[name="modal_font"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.getElementById('font_style').value = this.value;
            document.querySelectorAll('#modal-font-options .modal-signature-option').forEach(function(o) { o.classList.remove('selected'); });
            var opt = document.querySelector('.modal-signature-option[data-font="' + this.value + '"]');
            if (opt) opt.classList.add('selected');
        });
    });
    document.querySelectorAll('#modalSignatureDetail .color-dot').forEach(function(dot) {
        dot.addEventListener('click', function() {
            document.querySelectorAll('#modalSignatureDetail .color-dot').forEach(function(d) { d.classList.remove('selected'); });
            this.classList.add('selected');
            document.getElementById('color').value = this.dataset.color;
        });
    });
    modalApply.addEventListener('click', function() {
        document.getElementById('nama_lengkap').value = modalNama.value;
        document.getElementById('inisial').value = modalInisial.value;
        
        var sigType = document.getElementById('signature_type').value || 'text';
        var prevSig = document.getElementById('preview-signature');
        var prevIni = document.getElementById('preview-inisial');
        
        if (sigType === 'image') {
            // Validasi: harus ada gambar
            var imgUrl = uploadedImageUrl || document.getElementById('signature_image_url').value;
            if (!imgUrl) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Gambar Belum Dipilih', text: 'Silakan upload gambar tanda tangan terlebih dahulu.' });
                } else {
                    alert('Silakan upload gambar tanda tangan terlebih dahulu.');
                }
                return;
            }
            document.getElementById('signature_image_url').value = imgUrl;
            
            // Update preview dengan gambar
            prevSig.innerHTML = '<img src="' + imgUrl + '" alt="TTD" style="max-height:24px;object-fit:contain;">';
            prevSig.className = 'flex-grow-1';
            prevSig.style.color = '';
        } else {
            // Tipe text - gunakan font
            var font = document.querySelector('input[name="modal_font"]:checked');
            if (font) document.getElementById('font_style').value = font.value;
            var selDot = document.querySelector('#modalSignatureDetail .color-dot.selected');
            if (selDot) document.getElementById('color').value = selDot.dataset.color || '#000000';
            var fontClass = 'signature-font-' + (document.getElementById('font_style').value || '1');
            var colorVal = document.getElementById('color').value || '#000000';
            
            prevSig.textContent = modalNama.value || 'Tanda tangan';
            prevSig.className = 'signature-cursive flex-grow-1 ' + fontClass;
            prevSig.style.color = colorVal;
        }
        
        // Update inisial preview (selalu text)
        var fontClass = 'signature-font-' + (document.getElementById('font_style').value || '1');
        var colorVal = document.getElementById('color').value || '#000000';
        prevIni.textContent = modalInisial.value || 'Inisial';
        prevIni.className = 'signature-cursive flex-grow-1 ' + fontClass;
        prevIni.style.color = colorVal;
        
        renderPlacementBoxes();
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getInstance(modalEl).hide();
        } else { modalEl.classList.remove('show'); modalEl.style.display = 'none'; }
    });

    // Load PDF with loading indicator
    var pdfLoadingOverlay = document.getElementById('pdf-loading-overlay');
    pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
        pdfDoc = pdf;
        var n = pdf.numPages;
        document.getElementById('page-info').textContent = 'Page 1 of ' + n;
        for (var i = 1; i <= n; i++) {
            var o = document.createElement('option');
            o.value = i;
            o.textContent = 'Halaman ' + i;
            pageSelect.appendChild(o);
        }
        pageSelect.addEventListener('change', function() {
            currentPage = parseInt(this.value, 10);
            document.getElementById('page-info').textContent = 'Page ' + currentPage + ' of ' + n;
            renderPage(currentPage);
            renderPlacementBoxes();
        });
        renderPage(1);
        renderPlacementBoxes();
        // Hide loading overlay
        if (pdfLoadingOverlay) pdfLoadingOverlay.style.display = 'none';
    }).catch(function(err) {
        console.error('PDF Load Error:', err);
        if (pdfLoadingOverlay) pdfLoadingOverlay.innerHTML = '<div class="text-danger"><i class="fe fe-alert-circle" style="font-size:2rem;"></i><div class="loading-text">Gagal memuat PDF</div><small class="text-muted">Pastikan file PDF tersedia</small></div>';
    });

    // Unsaved changes warning
    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            return e.returnValue;
        }
    });

    function renderPage(num) {
        if (!pdfDoc) return;
        pdfDoc.getPage(num).then(function(page) {
            var viewport = page.getViewport({ scale: scale });
            pageWidth = viewport.width;
            pageHeight = viewport.height;
            canvas.width = pageWidth;
            canvas.height = pageHeight;
            dropZone.style.width = pageWidth + 'px';
            dropZone.style.height = pageHeight + 'px';
            placementBoxes.style.width = pageWidth + 'px';
            placementBoxes.style.height = pageHeight + 'px';
            page.render({ canvasContext: ctx, viewport: viewport });
            renderSnapGrid();
        });
    }

    // Zoom controls
    var zoomLevels = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];
    var currentZoomIndex = 2; // Start at 1.0 (100%)
    var baseScale = 1.4;
    
    function updateZoom(newIndex) {
        if (newIndex < 0 || newIndex >= zoomLevels.length) return;
        currentZoomIndex = newIndex;
        var zoomFactor = zoomLevels[currentZoomIndex];
        scale = baseScale * zoomFactor;
        document.getElementById('zoom-level').textContent = Math.round(zoomFactor * 100) + '%';
        renderPage(currentPage);
        renderPlacementBoxes();
    }
    
    document.getElementById('zoom-in').addEventListener('click', function() {
        updateZoom(currentZoomIndex + 1);
    });
    document.getElementById('zoom-out').addEventListener('click', function() {
        updateZoom(currentZoomIndex - 1);
    });
    document.getElementById('zoom-fit').addEventListener('click', function() {
        updateZoom(2); // Reset to 100%
    });

    // Snap grid toggle
    document.getElementById('snap-grid-toggle').addEventListener('change', function() {
        snapEnabled = this.checked;
        var grid = document.getElementById('snap-grid');
        if (grid) grid.classList.toggle('visible', snapEnabled);
    });

    function renderSnapGrid() {
        var grid = document.getElementById('snap-grid');
        if (!grid || !pageWidth || !pageHeight) return;
        grid.setAttribute('width', pageWidth);
        grid.setAttribute('height', pageHeight);
        grid.style.width = pageWidth + 'px';
        grid.style.height = pageHeight + 'px';
        
        var lines = '';
        var gridPx = mmToPx(GRID_SIZE);
        // Vertical lines
        for (var x = gridPx; x < pageWidth; x += gridPx) {
            lines += '<line class="grid-line-v" x1="' + x + '" y1="0" x2="' + x + '" y2="' + pageHeight + '" stroke-dasharray="2,4"/>';
        }
        // Horizontal lines
        for (var y = gridPx; y < pageHeight; y += gridPx) {
            lines += '<line class="grid-line-h" x1="0" y1="' + y + '" x2="' + pageWidth + '" y2="' + y + '" stroke-dasharray="2,4"/>';
        }
        grid.innerHTML = lines;
    }

    function snapToGrid(valueMm) {
        if (!snapEnabled) return valueMm;
        return Math.round(valueMm / GRID_SIZE) * GRID_SIZE;
    }

    function showAlignGuide(type, positionMm) {
        var guide = document.getElementById('align-guide-' + type);
        if (!guide) return;
        guide.classList.add('visible');
        if (type === 'h') {
            guide.style.top = mmToPx(positionMm) + 'px';
        } else {
            guide.style.left = mmToPx(positionMm) + 'px';
        }
    }

    function hideAlignGuides() {
        var h = document.getElementById('align-guide-h');
        var v = document.getElementById('align-guide-v');
        if (h) h.classList.remove('visible');
        if (v) v.classList.remove('visible');
    }

    function pxToMm(px) { return px / (MM_TO_PX * scale); }
    function mmToPx(mm) { return mm * MM_TO_PX * scale; }

    function getSignatureStyle() {
        var font = document.getElementById('font_style').value || '1';
        var color = document.getElementById('color').value || '#000000';
        var sigType = document.getElementById('signature_type').value || 'text';
        var imageUrl = document.getElementById('signature_image_url').value || '';
        return { font: font, color: color, type: sigType, imageUrl: imageUrl };
    }

    function renderPlacementBoxes() {
        placementBoxes.innerHTML = '';
        placementBoxes.style.position = 'absolute';
        placementBoxes.style.left = '0';
        placementBoxes.style.top = '0';
        placementBoxes.style.pointerEvents = 'none';
        var style = getSignatureStyle();
        var onPage = placements.filter(function(p) { return p.page === currentPage; });
        onPage.forEach(function(p, idx) {
            var globalIdx = placements.indexOf(p);
            var box = document.createElement('div');
            box.className = 'placement-box' + (globalIdx === selectedPlacementIndex ? ' selected' : '');
            box.style.left = mmToPx(p.x) + 'px';
            box.style.top = mmToPx(p.y) + 'px';
            box.style.width = (p.width ? mmToPx(p.width) : 80) + 'px';
            box.style.height = (p.height ? mmToPx(p.height) : 20) + 'px';
            box.style.pointerEvents = 'auto';
            box.dataset.placementIndex = globalIdx;
            var text = p.value !== null && p.value !== '' ? p.value : getDisplayValue(p.field_type);
            var fontClass = (p.field_type === 'signature' || p.field_type === 'inisial') ? ' signature-font-' + (p.font_style || style.font) : '';
            var colorStyle = (p.field_type === 'signature' || p.field_type === 'inisial') ? ('color:' + (p.color || style.color)) : '';
            var content = '';
            if (p.field_type === 'stempel' && stempelUrl) {
                content = '<img src="' + stempelUrl + '" alt="Stempel" class="placement-stempel-img" style="width:100%;height:100%;object-fit:contain;pointer-events:none;" onerror="this.style.display=\'none\';this.parentNode.querySelector(\'.stempel-fallback\')&&(this.parentNode.querySelector(\'.stempel-fallback\').style.display=\'block\');this.insertAdjacentHTML(\'afterend\',\'<span class=stempel-fallback style=font-size:11px;color:#dc3545>Stempel error</span>\');">';
            } else if (p.field_type === 'stempel' && !stempelUrl) {
                content = '<span class="placement-text" style="font-size:11px;color:#6c757d;">Stempel (belum diatur)</span>';
            } else if (p.field_type === 'signature' && style.type === 'image' && style.imageUrl) {
                // Image signature
                content = '<img src="' + style.imageUrl + '" alt="TTD" style="width:100%;height:100%;object-fit:contain;pointer-events:none;">';
            } else {
                content = '<span class="placement-text' + fontClass + '" style="' + colorStyle + '">' + (p.field_type === 'stempel' ? 'Stempel' : (text || LABELS[p.field_type])) + '</span>';
            }
            box.innerHTML = '<button type="button" class="placement-remove" title="Hapus">&times;</button>' + content +
                '<span class="placement-resize-handle nw" data-corner="nw"></span><span class="placement-resize-handle ne" data-corner="ne"></span><span class="placement-resize-handle sw" data-corner="sw"></span><span class="placement-resize-handle se" data-corner="se"></span>';
            box.querySelector('.placement-remove').addEventListener('click', function(e) {
                e.stopPropagation();
                var i = parseInt(box.dataset.placementIndex, 10);
                if (i >= 0) { placements.splice(i, 1); isDirty = true; renderPlacementBoxes(); updateBadgeCount(); }
            });
            box.querySelectorAll('.placement-resize-handle').forEach(function(handle) {
                handle.addEventListener('mousedown', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var i = parseInt(box.dataset.placementIndex, 10);
                    if (i < 0) return;
                    var pl = placements[i];
                    var corner = handle.dataset.corner;
                    resizing = { index: i, corner: corner, startX: e.clientX, startY: e.clientY, startW: pl.width || 40, startH: pl.height || 20, startLeft: pl.x, startTop: pl.y };
                });
            });
            if (p.field_type === 'teks') {
                box.addEventListener('dblclick', function(e) {
                    if (e.target.classList.contains('placement-remove') || e.target.classList.contains('placement-resize-handle')) return;
                    teksEditPlacementIndex = globalIdx;
                    pendingTeksDrop = null;
                    document.getElementById('modal_teks_value').value = (p.value || '');
                    var modalTeks = document.getElementById('modalTeksKustom');
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) new bootstrap.Modal(modalTeks).show();
                    else { modalTeks.classList.add('show'); modalTeks.style.display = 'block'; }
                });
            }
            placementBoxes.appendChild(box);
        });
        updateBadgeCount();
    }

    function updateBadgeCount() {
        var sigCount = placements.filter(function(p) { return p.field_type === 'signature'; }).length;
        var otherCount = placements.filter(function(p) { return p.field_type !== 'signature'; }).length;
        var currentPageCount = placements.filter(function(p) { return p.page === currentPage; }).length;
        
        // Update badge on draggable field
        var el = document.getElementById('count-signature');
        if (el) { el.textContent = sigCount; el.style.visibility = sigCount ? 'visible' : 'hidden'; }
        
        // Update summary counters
        var sumSig = document.getElementById('summary-signature');
        var sumOther = document.getElementById('summary-other');
        var sumCurrent = document.getElementById('summary-current-page');
        if (sumSig) sumSig.textContent = sigCount;
        if (sumOther) sumOther.textContent = otherCount;
        if (sumCurrent) sumCurrent.textContent = currentPageCount;
    }

    // Drag from panel with ghost preview
    document.querySelectorAll('.draggable-field').forEach(function(el) {
        el.addEventListener('dragstart', function(e) {
            var fieldType = el.dataset.fieldType;
            var displayValue = getDisplayValue(fieldType);
            e.dataTransfer.setData('fieldType', fieldType);
            e.dataTransfer.setData('displayValue', displayValue);
            e.dataTransfer.effectAllowed = 'copy';
            
            // Show drag ghost
            currentDragging = { type: fieldType, display: displayValue };
            if (dragGhost) {
                dragGhost.innerHTML = '<i class="fe fe-' + (fieldType === 'signature' ? 'edit-3' : fieldType === 'tanggal' ? 'calendar' : fieldType === 'stempel' ? 'bookmark' : 'type') + ' me-1"></i>' + LABELS[fieldType];
                dragGhost.classList.add('visible');
            }
            
            // Use transparent drag image (browser default)
            var img = new Image();
            img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            e.dataTransfer.setDragImage(img, 0, 0);
        });
        
        el.addEventListener('dragend', function() {
            if (dragGhost) dragGhost.classList.remove('visible');
            currentDragging = null;
        });
    });
    
    // Track ghost position
    document.addEventListener('drag', function(e) {
        if (currentDragging && dragGhost && e.clientX > 0 && e.clientY > 0) {
            dragGhost.style.left = e.clientX + 'px';
            dragGhost.style.top = e.clientY + 'px';
        }
    });

    // Drop on PDF with visual feedback
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        dropZone.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', function() {
        dropZone.classList.remove('drag-over');
    });
    var pendingTeksDrop = null; // { x, y, page } untuk drop Teks yang menunggu modal
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (dragGhost) dragGhost.classList.remove('visible');
        var fieldType = e.dataTransfer.getData('fieldType');
        var displayValue = e.dataTransfer.getData('displayValue');
        if (!fieldType) return;
        var rect = dropZone.getBoundingClientRect();
        var xPx = e.clientX - rect.left;
        var yPx = e.clientY - rect.top;
        var xMm = Math.round(pxToMm(xPx) * 10) / 10;
        var yMm = Math.round(pxToMm(yPx) * 10) / 10;
        var w = 40, h = 8;
        if (fieldType === 'signature' || fieldType === 'inisial') h = 10;
        if (fieldType === 'teks') {
            pendingTeksDrop = { x: xMm, y: yMm, page: currentPage, w: w, h: h };
            document.getElementById('modal_teks_value').value = '';
            var modalTeks = document.getElementById('modalTeksKustom');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) new bootstrap.Modal(modalTeks).show();
            else { modalTeks.classList.add('show'); modalTeks.style.display = 'block'; }
            return;
        }
        placements.push({ field_type: fieldType, page: currentPage, x: xMm, y: yMm, width: w, height: h, value: fieldType === 'tanggal' ? new Date().toLocaleDateString('id-ID') : (displayValue || null) });
        isDirty = true; // Mark as dirty
        renderPlacementBoxes();
    });

    document.getElementById('modal-teks-apply').addEventListener('click', function() {
        var val = document.getElementById('modal_teks_value').value.trim();
        if (pendingTeksDrop) {
            placements.push({ field_type: 'teks', page: pendingTeksDrop.page, x: pendingTeksDrop.x, y: pendingTeksDrop.y, width: pendingTeksDrop.w || 40, height: pendingTeksDrop.h || 8, value: val || null });
            pendingTeksDrop = null;
            isDirty = true;
            renderPlacementBoxes();
        } else if (typeof teksEditPlacementIndex !== 'undefined' && teksEditPlacementIndex >= 0) {
            placements[teksEditPlacementIndex].value = val || null;
            teksEditPlacementIndex = -1;
            isDirty = true;
            renderPlacementBoxes();
        }
        var modalTeks = document.getElementById('modalTeksKustom');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) { var inst = bootstrap.Modal.getInstance(modalTeks); if (inst) inst.hide(); }
        else { modalTeks.classList.remove('show'); modalTeks.style.display = 'none'; }
    });

    // Make placement boxes draggable (move on PDF) and resizable
    placementBoxes.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('placement-resize-handle')) return;
        var box = e.target.closest('.placement-box');
        if (!box || e.target.classList.contains('placement-remove')) return;
        var idx = parseInt(box.dataset.placementIndex, 10);
        if (idx < 0) return;
        var p = placements[idx];
        if (p.page !== currentPage) return;
        
        // Set selection
        selectedPlacementIndex = idx;
        renderPlacementBoxes();
        
        selectedBox = { index: idx, startX: e.clientX, startY: e.clientY, startLeft: p.x, startTop: p.y };
        e.preventDefault();
    });
    
    // Click outside to deselect
    dropZone.addEventListener('click', function(e) {
        if (e.target === dropZone) {
            selectedPlacementIndex = -1;
            renderPlacementBoxes();
        }
    });
    document.addEventListener('mousemove', function(e) {
        var dxMm = (e.clientX - (resizing ? resizing.startX : (selectedBox ? selectedBox.startX : 0))) / (MM_TO_PX * scale);
        var dyMm = (e.clientY - (resizing ? resizing.startY : (selectedBox ? selectedBox.startY : 0))) / (MM_TO_PX * scale);
        if (resizing) {
            var p = placements[resizing.index];
            var minMm = 8;
            var w = resizing.startW, h = resizing.startH, x = resizing.startLeft, y = resizing.startTop;
            switch (resizing.corner) {
                case 'se': w = Math.max(minMm, resizing.startW + dxMm); h = Math.max(minMm, resizing.startH + dyMm); break;
                case 'sw': w = Math.max(minMm, resizing.startW - dxMm); h = Math.max(minMm, resizing.startH + dyMm); x = resizing.startLeft + dxMm; break;
                case 'ne': w = Math.max(minMm, resizing.startW + dxMm); h = Math.max(minMm, resizing.startH - dyMm); y = resizing.startTop + dyMm; break;
                case 'nw': w = Math.max(minMm, resizing.startW - dxMm); h = Math.max(minMm, resizing.startH - dyMm); x = resizing.startLeft + dxMm; y = resizing.startTop + dyMm; break;
            }
            p.width = snapToGrid(Math.round(w * 10) / 10);
            p.height = snapToGrid(Math.round(h * 10) / 10);
            p.x = snapToGrid(Math.round(x * 10) / 10);
            p.y = snapToGrid(Math.round(y * 10) / 10);
            renderPlacementBoxes();
            return;
        }
        if (!selectedBox) return;
        var p = placements[selectedBox.index];
        var newX = snapToGrid(Math.round((selectedBox.startLeft + dxMm) * 10) / 10);
        var newY = snapToGrid(Math.round((selectedBox.startTop + dyMm) * 10) / 10);
        
        // Check for alignment with other placements
        var aligned = checkAlignment(selectedBox.index, newX, newY, p.width || 40, p.height || 20);
        p.x = aligned.x;
        p.y = aligned.y;
        renderPlacementBoxes();
    });
    
    // Check alignment with other placements and show guides
    function checkAlignment(excludeIndex, x, y, w, h) {
        hideAlignGuides();
        var threshold = 3; // mm
        var result = { x: x, y: y };
        
        placements.forEach(function(other, i) {
            if (i === excludeIndex || other.page !== currentPage) return;
            var ox = other.x, oy = other.y, ow = other.width || 40, oh = other.height || 20;
            
            // Horizontal alignment (top/bottom edges)
            if (Math.abs(y - oy) < threshold) { result.y = oy; showAlignGuide('h', oy); }
            if (Math.abs(y - (oy + oh)) < threshold) { result.y = oy + oh; showAlignGuide('h', oy + oh); }
            if (Math.abs((y + h) - oy) < threshold) { result.y = oy - h; showAlignGuide('h', oy); }
            if (Math.abs((y + h) - (oy + oh)) < threshold) { result.y = oy + oh - h; showAlignGuide('h', oy + oh); }
            
            // Vertical alignment (left/right edges)
            if (Math.abs(x - ox) < threshold) { result.x = ox; showAlignGuide('v', ox); }
            if (Math.abs(x - (ox + ow)) < threshold) { result.x = ox + ow; showAlignGuide('v', ox + ow); }
            if (Math.abs((x + w) - ox) < threshold) { result.x = ox - w; showAlignGuide('v', ox); }
            if (Math.abs((x + w) - (ox + ow)) < threshold) { result.x = ox + ow - w; showAlignGuide('v', ox + ow); }
        });
        
        return result;
    }
    document.addEventListener('mouseup', function() {
        if (selectedBox || resizing) isDirty = true; // Mark dirty on move/resize
        hideAlignGuides();
        selectedBox = null;
        resizing = null;
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Delete selected placement
        if ((e.key === 'Delete' || e.key === 'Backspace') && selectedPlacementIndex >= 0) {
            // Don't delete if focused on input/textarea
            if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
            e.preventDefault();
            placements.splice(selectedPlacementIndex, 1);
            selectedPlacementIndex = -1;
            isDirty = true;
            renderPlacementBoxes();
            updateBadgeCount();
        }
        // Escape to deselect
        if (e.key === 'Escape' && selectedPlacementIndex >= 0) {
            selectedPlacementIndex = -1;
            renderPlacementBoxes();
        }
    });

    // Fungsi validasi sebelum tanda tangan
    function validateBeforeSign() {
        var hasSignature = placements.some(function(p) { return p.field_type === 'signature'; });
        var namaLengkap = document.getElementById('nama_lengkap').value.trim();
        
        if (!namaLengkap) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Nama Wajib Diisi', text: 'Silakan atur detail tanda tangan terlebih dahulu (klik tombol "Atur detail").', confirmButtonColor: '#dc3545' });
            } else {
                alert('Silakan atur detail tanda tangan terlebih dahulu.');
            }
            return false;
        }
        
        if (!hasSignature) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Tanda Tangan Wajib', text: 'Drag dan letakkan minimal 1 kotak "Tanda tangan" ke dokumen PDF sebelum menandatangani.', confirmButtonColor: '#dc3545' });
            } else {
                alert('Letakkan minimal 1 tanda tangan di dokumen.');
            }
            return false;
        }
        
        return true;
    }

    // Fungsi submit tanda tangan
    function submitSignature() {
        var btn = document.getElementById('btn-save');
        btn.disabled = true;
        document.getElementById('btn-tandatangani').disabled = true;
        
        // Show loading indicator
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyimpan...',
                html: '<div class="d-flex flex-column align-items-center"><div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;"></div><p class="mb-0">Sedang memproses tanda tangan dan membuat PDF...</p></div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
        }
        
        var payload = {
            _token: document.getElementById('csrf-token').value,
            nama_lengkap: document.getElementById('nama_lengkap').value,
            inisial: document.getElementById('inisial').value,
            font_style: document.getElementById('font_style').value || '1',
            color: document.getElementById('color').value,
            signature_type: document.getElementById('signature_type').value || 'text',
            signature_image_url: document.getElementById('signature_image_url').value || '',
            placements: placements.map(function(p, i) {
                return { field_type: p.field_type, page: p.page, x: p.x, y: p.y, width: p.width || 40, height: p.height || 8, value: p.value, options: {} };
            })
        };
        
        fetch(document.getElementById('save-url').value, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                isDirty = false; // Clear dirty flag before redirect
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Dokumen Berhasil Ditandatangani!',
                        text: 'Dokumen telah sah dan PDF bertanda tangan tersimpan.',
                        confirmButtonText: 'Lihat Dokumen',
                        confirmButtonColor: '#198754',
                        allowOutsideClick: false
                    }).then(function(result) {
                        window.location.href = document.getElementById('redirect-url').value;
                    });
                } else {
                    isDirty = false;
                    alert('Dokumen berhasil ditandatangani!');
                    window.location.href = document.getElementById('redirect-url').value;
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan saat menyimpan.' });
                } else {
                    alert(data.message || 'Gagal menyimpan.');
                }
                btn.disabled = false;
                document.getElementById('btn-tandatangani').disabled = false;
            }
        }).catch(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan koneksi. Silakan coba lagi.' });
            } else {
                alert('Gagal menyimpan.');
            }
            btn.disabled = false;
            document.getElementById('btn-tandatangani').disabled = false;
        });
    }

    // Button "Tanda tangani" dengan konfirmasi
    document.getElementById('btn-tandatangani').addEventListener('click', function() {
        if (!validateBeforeSign()) return;
        
        var namaLengkap = document.getElementById('nama_lengkap').value;
        var signatureCount = placements.filter(function(p) { return p.field_type === 'signature'; }).length;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'question',
                title: 'Konfirmasi Tanda Tangan',
                html: '<p>Dokumen akan ditandatangani atas nama:</p><p class="fw-bold fs-5 text-primary">' + namaLengkap + '</p><p class="small text-muted mt-2">Jumlah tanda tangan: ' + signatureCount + ' posisi<br>Setelah ditandatangani, dokumen akan menjadi <strong>SAH</strong> dan file asli akan diganti dengan versi bertanda tangan.</p>',
                showCancelButton: true,
                confirmButtonText: '<i class="fe fe-check me-1"></i> Ya, Tanda Tangani',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    submitSignature();
                }
            });
        } else {
            if (confirm('Tanda tangani dokumen atas nama "' + namaLengkap + '"? Dokumen akan menjadi SAH.')) {
                submitSignature();
            }
        }
    });

    // Button "Simpan posisi" (hanya simpan tanpa konfirmasi untuk draft)
    document.getElementById('btn-save').addEventListener('click', function() {
        if (!validateBeforeSign()) return;
        submitSignature();
    });

    // Button "Reset semua posisi"
    document.getElementById('btn-reset').addEventListener('click', function() {
        if (placements.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'info', title: 'Tidak Ada Posisi', text: 'Belum ada kotak isian yang diletakkan di dokumen.' });
            } else {
                alert('Belum ada posisi yang perlu direset.');
            }
            return;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Reset Semua Posisi?',
                text: 'Semua kotak isian (' + placements.length + ' item) akan dihapus dari dokumen. Tindakan ini tidak dapat dibatalkan.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset Semua',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    placements = [];
                    isDirty = true;
                    renderPlacementBoxes();
                    updateBadgeCount();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Semua posisi telah direset.', timer: 1500, showConfirmButton: false });
                }
            });
        } else {
            if (confirm('Reset semua posisi (' + placements.length + ' item)? Tindakan ini tidak dapat dibatalkan.')) {
                placements = [];
                isDirty = true;
                renderPlacementBoxes();
                updateBadgeCount();
            }
        }
    });

    // Master TTD: pilih dari dropdown isi form + preview
    var masterSelect = document.getElementById('master-ttd-select');
    if (masterSelect) {
        masterSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;
            var nama = opt.getAttribute('data-nama') || '';
            var inisial = opt.getAttribute('data-inisial') || '';
            var font = opt.getAttribute('data-font') || '1';
            var color = opt.getAttribute('data-color') || '#000000';
            document.getElementById('nama_lengkap').value = nama;
            document.getElementById('inisial').value = inisial;
            document.getElementById('font_style').value = font;
            document.getElementById('color').value = color;
            var fontClass = 'signature-font-' + font;
            document.getElementById('preview-signature').textContent = nama || 'Tanda tangan';
            document.getElementById('preview-signature').className = 'signature-cursive flex-grow-1 ' + fontClass;
            document.getElementById('preview-signature').style.color = color;
            document.getElementById('preview-inisial').textContent = inisial || 'Inisial';
            document.getElementById('preview-inisial').className = 'signature-cursive flex-grow-1 ' + fontClass;
            document.getElementById('preview-inisial').style.color = color;
            if (typeof renderPlacementBoxes === 'function') renderPlacementBoxes();
        });
    }
    document.getElementById('btn-save-master-ttd').addEventListener('click', function() {
        var btn = this;
        var payload = {
            _token: document.getElementById('csrf-token').value,
            nama_lengkap: document.getElementById('nama_lengkap').value,
            inisial: document.getElementById('inisial').value,
            font_style: document.getElementById('font_style').value || '1',
            color: document.getElementById('color').value || '#000000',
            is_default: true
        };
        if (!payload.nama_lengkap.trim()) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Nama wajib', text: 'Isi nama lengkap terlebih dahulu (Atur detail).' });
            else alert('Isi nama lengkap terlebih dahulu.');
            return;
        }
        btn.disabled = true;
        fetch('{{ route("master_tanda_tangan.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success && data.master) {
                var m = data.master;
                var fontLabels = { '1': 'Dancing Script', '2': 'Pacifico', '3': 'Great Vibes', '4': 'Sacramento' };
                var label = fontLabels[m.font_style] || 'Dancing Script';
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.setAttribute('data-nama', m.nama_lengkap);
                opt.setAttribute('data-inisial', m.inisial || '');
                opt.setAttribute('data-font', m.font_style || '1');
                opt.setAttribute('data-color', m.color || '#000000');
                opt.textContent = m.nama_lengkap + ' (' + label + ')';
                masterSelect.appendChild(opt);
                masterSelect.value = m.id;
                masterSelect.dispatchEvent(new Event('change'));
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Tersimpan', text: data.message || 'Master tanda tangan disimpan.' });
                else alert('Master tanda tangan disimpan.');
            }
        }).catch(function() { if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menyimpan master.' }); else alert('Gagal menyimpan.'); }).finally(function() { btn.disabled = false; });
    });

    // Apply initial signature style to preview
    (function applyInitialPreviewStyle() {
        var fontClass = 'signature-font-' + (document.getElementById('font_style').value || '1');
        var colorVal = document.getElementById('color').value || '#000000';
        var prevSig = document.getElementById('preview-signature');
        var prevIni = document.getElementById('preview-inisial');
        if (prevSig) { prevSig.className = 'signature-cursive flex-grow-1 ' + fontClass; prevSig.style.color = colorVal; }
        if (prevIni) { prevIni.className = 'signature-cursive flex-grow-1 ' + fontClass; prevIni.style.color = colorVal; }
    })();

    updateBadgeCount();
});
</script>
@endpush
