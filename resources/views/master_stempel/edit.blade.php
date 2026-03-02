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
                        <input type="file" name="file_stempel" id="file_stempel" class="form-control" accept=".png,image/png">
                        <small class="text-muted">Maks. 2 MB, format PNG (background transparan disarankan).</small>
                        @if ($stempel && $stempel->file_path)
                            <div class="mt-2">
                                <span class="text-muted">File saat ini:</span>
                                <img src="{{ $stempel->url }}" alt="Stempel" class="img-thumbnail ms-2" style="max-height:80px;">
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan (opsional)</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="2">{{ old('keterangan', $stempel->keterangan ?? '') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan stempel</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
