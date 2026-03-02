@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $title }}
                    @if($surat_edaran->tanggal_ditandatangani)
                        <span class="badge bg-success ms-2">Sah</span>
                    @endif
                </h5>
                <div>
                    <a href="{{ route('surat_edaran.tandaTangani', $surat_edaran) }}" class="btn btn-info btn-sm">Tanda tangani PDF</a>
                    @if($surat_edaran->tanggal_ditandatangani)
                        <a href="{{ route('surat_edaran.generateSignedPdf', $surat_edaran) }}" class="btn btn-success btn-sm" target="_blank">Download PDF bertanda tangan</a>
                    @endif
                    <a href="{{ route('surat_edaran.edit', $surat_edaran) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('surat_edaran.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Judul Surat</dt>
                    <dd class="col-sm-9">{{ $surat_edaran->judul_surat }}</dd>

                    <dt class="col-sm-3">Nomor Surat</dt>
                    <dd class="col-sm-9">{{ $surat_edaran->nomor_surat ?? '-' }}</dd>

                    <dt class="col-sm-3">Tanggal</dt>
                    <dd class="col-sm-9">{{ $surat_edaran->tanggal ? $surat_edaran->tanggal->format('d-m-Y') : '-' }}</dd>

                    <dt class="col-sm-3">Yang menyetujui</dt>
                    <dd class="col-sm-9">{{ $surat_edaran->penandatangan ? $surat_edaran->penandatangan->nama . ' (' . $surat_edaran->penandatangan->nik . ')' : '-' }}</dd>

                    <dt class="col-sm-3">Deskripsi</dt>
                    <dd class="col-sm-9">{{ $surat_edaran->deskripsi ?: '-' }}</dd>

                    @if($surat_edaran->tanggal_ditandatangani)
                    <dt class="col-sm-3">Ditandatangani (sah)</dt>
                    <dd class="col-sm-9">{{ $surat_edaran->tanggal_ditandatangani->format('d-m-Y H:i') }}</dd>
                    @endif

                    <dt class="col-sm-3">File PDF</dt>
                    <dd class="col-sm-9">
                        @if ($surat_edaran->file_pdf)
                            <a href="{{ route('surat_edaran.streamPdf', $surat_edaran) }}" target="_blank" class="btn btn-outline-primary btn-sm">Buka PDF</a>
                        @else
                            -
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
