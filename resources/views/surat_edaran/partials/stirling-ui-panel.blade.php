@php
    $stirlingUrl = $stirlingUiUrl ?? config('services.stirling_pdf.url');
    $embedEnabled = ($stirlingEmbedEnabled ?? config('services.stirling_pdf.embed_enabled', true)) && !empty($stirlingUrl);
@endphp
@if(!empty($stirlingUrl))
<div class="card border mt-3 mb-3">
    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fe fe-tool me-1"></i>Stirling PDF (edit opsional)</h6>
        <a href="{{ $stirlingUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
            <i class="fe fe-external-link me-1"></i>Buka Stirling PDF
        </a>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-2">
            Edit PDF di Stirling (merge, rotate, crop, dll.) → download hasilnya → upload ulang di form ini.
            Finalisasi tanda tangan tetap dilakukan di halaman <strong>Tanda tangani</strong> setelah PDF diunggah.
        </p>
        @if($embedEnabled)
        <details class="stirling-embed-panel">
            <summary class="btn btn-sm btn-secondary mb-2">Tampilkan embed Stirling</summary>
            <div class="ratio ratio-16x9 border rounded overflow-hidden" style="min-height: 420px;">
                <iframe src="{{ $stirlingUrl }}" title="Stirling PDF" class="stirling-embed-iframe" loading="lazy" referrerpolicy="no-referrer"></iframe>
            </div>
            <p class="small text-muted mt-2 mb-0">Jika panel kosong, gunakan tombol <strong>Buka Stirling PDF</strong> di tab baru (browser mungkin memblokir embed).</p>
        </details>
        @endif
    </div>
</div>
@endif
