@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
@php($routePrefix = $routePrefix ?? 'surat_edaran')
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
.placement-box.image-placement { padding: 0; background: #fff; }
.placement-box.image-placement .placement-stempel-img,
.placement-box.image-placement .placement-signature-img { width: 100%; height: 100%; object-fit: contain; display: block; pointer-events: none; }
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
        <a href="{{ route($routePrefix . '.show', $surat_edaran) }}" class="btn btn-secondary btn-sm">Kembali</a>
        <a href="{{ route($routePrefix . '.generateSignedPdf', $surat_edaran) }}" class="btn btn-success btn-sm" target="_blank">Download PDF bertanda tangan</a>
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
                    <div id="field-nomor-surat" class="draggable-field" draggable="true" data-field-type="nomor_surat" title="Nomor resmi dokumen — tertanam di PDF saat finalisasi">
                        <span class="drag-handle">⋮⋮</span>
                        <i class="fe fe-hash text-primary"></i>
                        <span class="small flex-grow-1">
                            Nomor Surat
                            <span class="hint-text d-block">Times New Roman — resize tinggi kotak untuk ubah ukuran (default 11pt)</span>
                            <span class="hint-text d-block text-truncate" style="max-width:200px;">{{ $surat_edaran->nomor_surat ?? '—' }}</span>
                        </span>
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
                    <div id="field-qr" class="draggable-field" draggable="true" data-field-type="qr_verifikasi" title="Barcode/QR verifikasi keabsahan — tertanam di dalam PDF (mirip Privy)">
                        <span class="drag-handle">⋮⋮</span>
                        <i class="fe fe-grid text-success"></i>
                        <span class="small flex-grow-1">
                            QR Verifikasi
                            <span class="hint-text d-block">Tertanam di dalam file PDF</span>
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
                <button type="button" id="btn-delete-draft" class="btn btn-outline-danger btn-sm w-100 mt-2" title="Hapus dokumen draft">
                    <i class="fe fe-trash-2 me-1"></i> Hapus Draft
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
                        <div class="signature-preview-wrapper">
                            <img id="signature-image-preview" class="signature-image-preview" src="" alt="Preview">
                            <button type="button" class="btn-crop-overlay" onclick="openSignatureCropModal()">
                                <i class="fe fe-crop"></i> Crop
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="openSignatureCropModal()">
                            <i class="fe fe-crop me-1"></i>Crop Gambar
                        </button>
                    </div>
                    <input type="hidden" id="cropped_signature_image" value="">
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

<!-- Modal: Crop Signature Image -->
<div class="modal fade" id="cropSignatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Gambar Tanda Tangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="crop-container">
                    <img id="signature-crop-image" src="" alt="Crop">
                </div>
                <div class="crop-toolbar mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.signatureCropper && window.signatureCropper.rotate(-90)">
                        <i class="fe fe-rotate-ccw"></i> Rotate Left
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.signatureCropper && window.signatureCropper.rotate(90)">
                        <i class="fe fe-rotate-cw"></i> Rotate Right
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.signatureCropper && window.signatureCropper.reset()">
                        <i class="fe fe-refresh-cw"></i> Reset
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.signatureCropper && window.signatureCropper.setAspectRatio(1)">
                        <i class="fe fe-square"></i> 1:1
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.signatureCropper && window.signatureCropper.setAspectRatio(NaN)">
                        <i class="fe fe-maximize"></i> Free
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="applySignatureCrop()">
                    <i class="fe fe-check me-1"></i>Terapkan Crop
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="save-url" value="{{ route($routePrefix . '.saveSignature', $surat_edaran) }}">
<input type="hidden" id="csrf-token" value="{{ csrf_token() }}">
<input type="hidden" id="redirect-url" value="{{ !empty($isMemoInternal) ? route('memo_internal.kirimsurat', encrypt($surat_edaran->surat->kode_surat)) : route($routePrefix . '.show', $surat_edaran) }}">
<input type="hidden" id="delete-url" value="{{ route($routePrefix . '.destroy', $surat_edaran) }}">
<input type="hidden" id="index-url" value="{{ route($routePrefix . '.index') }}">
@endsection

@push('scripts')
<script src="{{ asset('assets/libs/pdfjs/build/pdf.js') }}"></script>
<script>
window.SuratEdaranTtdConfig = {
    pdfWorkerUrl: "{{ asset('assets/libs/pdfjs/build/pdf.worker.js') }}",
    pdfUrl: @json($pdfUrl),
    placements: @json($placementsForJs ?? []),
    stempelUrl: @json(($masterStempel && $masterStempel->file_path) ? Storage::disk('public')->url($masterStempel->file_path) : null),
    qrPreviewUrl: @json(\App\Support\DocumentVerificationUrl::qrImageUrl($routePrefix ?? 'surat_edaran', $surat_edaran)),
    nomorSurat: @json($surat_edaran->nomor_surat ?? ''),
    routePrefix: @json($routePrefix ?? 'surat_edaran'),
    documentLabel: @json($documentLabel ?? 'Surat Edaran'),
    stirlingUiUrl: @json($stirlingUiUrl ?? config('services.stirling_pdf.url')),
    masterStoreUrl: @json(route('master_tanda_tangan.store')),
};
</script>
<script src="{{ asset('backend/assets/js/surat-edaran-tanda-tangani.js') }}"></script>

<!-- Cropper.js CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<style>
/* Signature Preview with Crop Overlay */
.signature-preview-wrapper {
    position: relative;
    display: inline-block;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
}

.signature-preview-wrapper img {
    max-width: 100%;
    max-height: 200px;
    display: block;
    border-radius: 4px;
}

.btn-crop-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(13, 110, 253, 0.9);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 14px;
}

.signature-preview-wrapper:hover .btn-crop-overlay {
    opacity: 1;
}

/* Crop Modal Styles */
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
