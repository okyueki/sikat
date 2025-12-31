@extends('layouts.pages-layouts')

@section('content')
<div class="container">
    <div class="card shadow-lg p-4">
        <h3 class="mb-3">📩 Detail Surat (Public Link)</h3>

        <p><strong>Pengirim:</strong> {{ $surat->pengirim }}</p>
        <p><strong>Perihal:</strong> {{ $surat->perihal }}</p>
        <p><strong>Tanggal:</strong> {{ $surat->tanggal }}</p>
        <p><strong>Isi:</strong> {!! nl2br(e($surat->isi)) !!}</p>

        <a href="{{ url('/') }}" class="btn btn-primary mt-3">Kembali</a>
    </div>
</div>
@endsection
