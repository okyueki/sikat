@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title :  $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
           
            <div class="card-body">
                <div class="row gy-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h4 class="mb-1"><strong>{{ $Pegawai->nama }}</strong></h4>
                            <div class="text-muted">NIK: <strong>{{ $Pegawai->nik }}</strong></div>
                            <div class="mt-2">
                                <span class="badge bg-info text-dark">Kategori: {{ $kategoriPegawai ?? '-' }}</span>
                            </div>
                        </div>

                        @php
                            $total = ($masterBerkas ?? collect())->count();
                            $uploaded = ($berkasLatestByKode ?? collect())->filter(fn ($b) => !empty($b?->berkas))->count();
                            $missing = max($total - $uploaded, 0);
                            $review = ($metaByKode ?? collect())->where('verifikasi_status', 'review')->count();
                            $rejected = ($metaByKode ?? collect())->where('verifikasi_status', 'rejected')->count();
                        @endphp
                        <div class="text-end">
                            <div class="mb-1">
                                <span class="badge bg-primary">{{ $uploaded }}/{{ $total }} uploaded</span>
                                @if($missing > 0)
                                    <span class="badge bg-secondary">belum upload {{ $missing }}</span>
                                @endif
                            </div>
                            <div>
                                @if($review > 0)
                                    <span class="badge bg-warning text-dark">review {{ $review }}</span>
                                @endif
                                @if($rejected > 0)
                                    <span class="badge bg-danger">rejected {{ $rejected }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Cari berkas (kode/nama)</label>
                            <input id="berkasSearch" type="text" class="form-control" placeholder="Contoh: MBP0013 / Ijazah / STR">
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-3 justify-content-md-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="filterMissingOnly">
                                    <label class="form-check-label" for="filterMissingOnly">Tampilkan yang belum upload saja</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="filterReviewRejected">
                                    <label class="form-check-label" for="filterReviewRejected">Fokus Review/Rejected</label>
                                </div>
                            </div>
                        </div>
                    </div>

    <form action="{{ route('berkas_pegawai.update', $Pegawai->nik) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="accordion" id="accordionExample">
            @foreach ($masterBerkas as $key => $m)
                @php
                    $berkas = ($berkasLatestByKode ?? collect())->get($m->kode);
                    $meta = ($metaByKode ?? collect())->get($m->kode);
                    $hasFile = !empty($berkas?->berkas);
                    $vs = $meta->verifikasi_status ?? ($hasFile ? 'review' : 'uploaded');
                    $masa = $meta->masa_berlaku_sampai ?? null;
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $key }}">
                        <button class="accordion-button {{ $key !== 0 ? 'collapsed' : '' }}" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse{{ $key }}" 
                                aria-expanded="{{ $key === 0 ? 'true' : 'false' }}" 
                                aria-controls="collapse{{ $key }}"
                                data-berkas-kode="{{ $m->kode }}"
                                data-berkas-nama="{{ $m->nama_berkas }}"
                                data-has-file="{{ $hasFile ? 1 : 0 }}"
                                data-verif="{{ $vs }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 w-100">
                                <div class="me-auto">
                                    <strong>{{ $m->kode }}</strong> — {{ $m->nama_berkas }}
                                </div>

                                @if($hasFile)
                                    <span class="badge bg-success">Uploaded</span>
                                @else
                                    <span class="badge bg-secondary">Belum upload</span>
                                @endif

                                @if($vs === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($vs === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($vs === 'review')
                                    <span class="badge bg-warning text-dark">Review</span>
                                @else
                                    <span class="badge bg-secondary">Uploaded</span>
                                @endif

                                @if(!empty($masa))
                                    <span class="badge bg-info text-dark">Berlaku s/d {{ $masa }}</span>
                                @endif
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $key }}" 
                         class="accordion-collapse collapse {{ $key === 0 ? 'show' : '' }}" 
                         aria-labelledby="heading{{ $key }}" 
                         data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-lg-5">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">Berkas saat ini</div>
                                                <div class="text-muted small">Upload terbaru per kode berkas</div>
                                            </div>
                                        </div>

                                        @if ($hasFile)
                                            <div class="mt-2 small">
                                                <div><strong>Tgl Upload:</strong> {{ $berkas->tgl_uploud ?? '-' }}</div>
                                                <div class="text-muted text-break"><strong>Path:</strong> {{ $berkas->berkas }}</div>
                                            </div>
                                            <div class="mt-2">
                                                <a class="btn btn-sm btn-primary" href="{{ simrs_asset($berkas->berkas) }}" target="_blank" rel="noopener">Download</a>
                                            </div>
                                        @else
                                            <div class="mt-2 text-muted">Belum ada berkas.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-lg-7">
                                    <div class="p-3 border rounded">
                                        <div class="fw-semibold mb-2">Input & Verifikasi</div>

                                        <div class="row g-2">
                                            <div class="col-md-12">
                                                <label for="file_{{ $m->kode }}" class="form-label">Unggah Berkas Baru (opsional)</label>
                                                <input type="file"
                                                       class="form-control"
                                                       id="file_{{ $m->kode }}"
                                                       name="file[{{ $m->kode }}]">
                                                <div class="form-text">Format: PDF/JPG/PNG, max 10MB.</div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="masa_berlaku_{{ $m->kode }}" class="form-label">Masa Berlaku Sampai</label>
                                                <input type="date"
                                                       class="form-control"
                                                       id="masa_berlaku_{{ $m->kode }}"
                                                       name="masa_berlaku_sampai[{{ $m->kode }}]"
                                                       value="{{ $meta->masa_berlaku_sampai ?? '' }}">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="verifikasi_status_{{ $m->kode }}" class="form-label">Status Verifikasi</label>
                                                <select class="form-control"
                                                        id="verifikasi_status_{{ $m->kode }}"
                                                        name="verifikasi_status[{{ $m->kode }}]">
                                                    <option value="uploaded" {{ $vs === 'uploaded' ? 'selected' : '' }}>Uploaded</option>
                                                    <option value="review" {{ $vs === 'review' ? 'selected' : '' }}>Review</option>
                                                    <option value="approved" {{ $vs === 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="rejected" {{ $vs === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                </select>
                                                <div class="form-text">Jika Rejected, catatan wajib diisi.</div>
                                            </div>

                                            <div class="col-md-12">
                                                <label for="catatan_verifikator_{{ $m->kode }}" class="form-label">Catatan Verifikator</label>
                                                <textarea class="form-control"
                                                          id="catatan_verifikator_{{ $m->kode }}"
                                                          name="catatan_verifikator[{{ $m->kode }}]"
                                                          rows="3"
                                                          placeholder="Contoh: mohon upload ulang versi legalisir / file blur / masa berlaku habis">{{ $meta->catatan_verifikator ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="text-muted small">Tips: pakai kolom pencarian untuk cepat menemukan berkas tertentu.</div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('berkas_pegawai.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const search = document.getElementById('berkasSearch');
        const missingOnly = document.getElementById('filterMissingOnly');
        const focusRR = document.getElementById('filterReviewRejected');
        const items = Array.from(document.querySelectorAll('#accordionExample .accordion-item'));

        function norm(s) {
            return (s || '').toString().toLowerCase();
        }

        function applyFilter() {
            const q = norm(search?.value);
            const onlyMissing = !!missingOnly?.checked;
            const onlyRR = !!focusRR?.checked;

            items.forEach((item) => {
                const btn = item.querySelector('.accordion-button');
                const kode = norm(btn?.getAttribute('data-berkas-kode'));
                const nama = norm(btn?.getAttribute('data-berkas-nama'));
                const hasFile = (btn?.getAttribute('data-has-file') === '1');
                const verif = norm(btn?.getAttribute('data-verif'));

                let ok = true;
                if (q) ok = (kode.includes(q) || nama.includes(q));
                if (ok && onlyMissing) ok = !hasFile;
                if (ok && onlyRR) ok = (verif === 'review' || verif === 'rejected');

                item.style.display = ok ? '' : 'none';
            });
        }

        search?.addEventListener('input', applyFilter);
        missingOnly?.addEventListener('change', applyFilter);
        focusRR?.addEventListener('change', applyFilter);
    })();
</script>

@endsection