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
                                <input type="file" name="file_pdf" id="file_pdf" class="form-control" accept=".pdf" required>
                                <small class="text-muted">Maks. 20 MB, format PDF.</small>
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
</script>
@endpush
