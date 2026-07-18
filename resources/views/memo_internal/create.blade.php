@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
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
                <div class="alert alert-info">
                    Nomor surat berikutnya (preview): <strong id="preview_nomor_surat">{{ $previewNomorSurat }}</strong>
                </div>
                <form action="{{ route('memo_internal.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_klasifikasi_surat">Klasifikasi Surat</label>
                                <select name="id_klasifikasi_surat" class="form-control" id="id_klasifikasi_surat" required>
                                    @foreach($klasifikasiSurat as $klasifikasi)
                                        <option value="{{ $klasifikasi->id_klasifikasi_surat }}" {{ (string) old('id_klasifikasi_surat', $klasifikasiSurat->first()->id_klasifikasi_surat ?? '') === (string) $klasifikasi->id_klasifikasi_surat ? 'selected' : '' }}>{{ $klasifikasi->nama_klasifikasi_surat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="id_sifat_surat">Sifat Surat</label>
                                <select name="id_sifat_surat" class="form-control" id="id_sifat_surat" required>
                                    @foreach($sifatSurat as $sifat)
                                        <option value="{{ $sifat->id_sifat_surat }}" {{ (string) old('id_sifat_surat') === (string) $sifat->id_sifat_surat ? 'selected' : '' }}>{{ $sifat->nama_sifat_surat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="perihal">Perihal</label>
                                <input type="text" name="perihal" class="form-control" id="perihal" value="{{ old('perihal') }}" placeholder="Masukkan perihal" required>
                            </div>
                            <div class="form-group">
                                <label for="tanggal_surat">Tanggal Surat</label>
                                <input type="date" name="tanggal_surat" class="form-control" id="tanggal_surat" value="{{ old('tanggal_surat', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group">
                                <label for="lampiran">Jumlah Lampiran</label>
                                <input type="text" name="lampiran" class="form-control" id="lampiran" value="{{ old('lampiran') }}" placeholder="Jumlah lampiran" required>
                            </div>
                            <hr>
                            <div class="form-group mb-3">
                                <label for="ttd_utama">Penandatangan Surat Utama:</label>
                                <select name="ttd_utama" id="ttd_utama" class="form-control" required>
                                    <option value="">-- Select Pegawai --</option>
                                    @foreach ($pegawai as $p)
                                        <option value="{{ $p->nik }}" {{ (string) old('ttd_utama') === (string) $p->nik ? 'selected' : '' }}>{{ $p->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <hr>
                            @for ($i = 1; $i <= 3; $i++)
                                <div class="form-group mb-3">
                                    <label for="ttd_{{ $i + 1 }}">Penandatangan Surat {{ $i + 1 }} (Opsional):</label>
                                    <select name="ttd_{{ $i + 1 }}" id="ttd_{{ $i + 1 }}" class="form-control">
                                        <option value="">-- Select Pegawai --</option>
                                        @foreach ($pegawai as $p)
                                            <option value="{{ $p->nik }}" {{ (string) old('ttd_' . ($i + 1)) === (string) $p->nik ? 'selected' : '' }}>{{ $p->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endfor
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="file_surat">Unggah Surat (PDF)</label>
                                <input type="file" name="file_surat" class="form-control" id="file_surat" accept=".pdf,application/pdf" required>
                                <div id="pdf-warning-file-surat" class="alert alert-warning mt-2 py-2 px-3 d-none" role="alert"></div>
                            </div>
                            <div class="form-group">
                                <label for="file_lampiran">Unggah Lampiran</label>
                                <input type="file" name="file_lampiran" class="form-control" id="file_lampiran" accept=".pdf">
                                <div id="pdf-warning-file-lampiran" class="alert alert-warning mt-2 py-2 px-3 d-none" role="alert"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan &amp; Tanda Tangan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('backend/assets/js/pdf-compat-warning.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const previewUrl = @json(route('memo_internal.previewNomor'));
        const previewEl = document.getElementById('preview_nomor_surat');
        const klasifikasiEl = document.getElementById('id_klasifikasi_surat');
        const tanggalEl = document.getElementById('tanggal_surat');
        let previewTimer = null;

        function updatePreviewNomor() {
            if (!klasifikasiEl || !tanggalEl || !previewEl) {
                return;
            }

            const params = new URLSearchParams({
                id_klasifikasi_surat: klasifikasiEl.value,
                tanggal_surat: tanggalEl.value,
            });

            fetch(previewUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.nomor) {
                        previewEl.textContent = data.nomor;
                    }
                })
                .catch(function () {});
        }

        function schedulePreviewUpdate() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(updatePreviewNomor, 200);
        }

        flatpickr('#tanggal_surat', {
            dateFormat: 'Y-m-d',
            onChange: schedulePreviewUpdate,
        });

        ['id_klasifikasi_surat', 'id_sifat_surat', 'ttd_utama', 'ttd_2', 'ttd_3', 'ttd_4'].forEach(function(id) {
            const element = document.getElementById(id);
            if (element) {
                new Choices(element, { searchEnabled: true, position: 'bottom', shouldSort: false });
            }
        });

        if (klasifikasiEl) {
            klasifikasiEl.addEventListener('change', schedulePreviewUpdate);
        }
        if (tanggalEl) {
            tanggalEl.addEventListener('change', schedulePreviewUpdate);
        }

        if (typeof initPdfCompatibilityWarning === 'function') {
            initPdfCompatibilityWarning('file_surat', 'pdf-warning-file-surat');
            initPdfCompatibilityWarning('file_lampiran', 'pdf-warning-file-lampiran');
        }
    });
</script>
@endsection
