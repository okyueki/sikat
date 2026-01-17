@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<!-- Menyertakan CSS PDF.js -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#home" role="tab">
                            <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                            <span class="d-none d-sm-block">Detail Surat Keluar</span>    
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab">
                            <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                            <span class="d-none d-sm-block">PDF</span>    
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lampiran" role="tab">
                            <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                            <span class="d-none d-sm-block">Lampiran</span>    
                        </a>
                    </li>           
                </ul>

                <div class="tab-content p-3">
                    <!-- Tab 1: Detail Surat -->
                    <div class="tab-pane active" id="home" role="tabpanel">
                        {{-- Info Realisasi Agenda --}}
                        @if($surat->agenda)
                            <div class="alert alert-success mb-4">
                                <h5><i class="fas fa-calendar-check me-2"></i>Realisasi: Agenda Acara</h5>
                                <p class="mb-1"><strong>Judul Agenda:</strong> 
                                    <a href="{{ route('acara_show', $surat->agenda->id) }}" target="_blank">
                                        {{ $surat->agenda->judul }}
                                        <i class="fas fa-external-link-alt ms-1"></i>
                                    </a>
                                </p>
                                <p class="mb-1"><strong>Tanggal Acara:</strong> {{ \Carbon\Carbon::parse($surat->agenda->mulai)->format('d M Y H:i') }}</p>
                                <p class="mb-0">
                                    <strong>Status Realisasi:</strong> 
                                    <span class="badge 
                                        @if($surat->agenda->status_realisasi == 'belum') bg-secondary
                                        @elseif($surat->agenda->status_realisasi == 'sedang') bg-warning
                                        @elseif($surat->agenda->status_realisasi == 'selesai') bg-success
                                        @endif">
                                        {{ ucfirst($surat->agenda->status_realisasi) }}
                                    </span>
                                </p>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item"><strong>Nomor Surat:</strong> {{ $surat->nomor_surat }}</li>
                                    <li class="list-group-item"><strong>Perihal Surat:</strong> {{ $surat->perihal }}</li>
                                    <li class="list-group-item"><strong>Pengirim:</strong> {{ $surat->pegawai->nama }}</li>
                                    <li class="list-group-item"><strong>Sifat:</strong> {{ $surat->sifat_surat->nama_sifat_surat }}</li>
                                    <li class="list-group-item"><strong>Tanggal Surat:</strong> {{ $tanggalSurat }}</li>
                                </ol>
                            </div>
                            <div class="col-12 col-md-6">
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item"><strong>Verifikasi:</strong>
                                        <ul>
                                            @foreach ($verifikasiSurat as $vS)
                                            <li><span class="badge 
                                                @if($vS->status_surat == 'Dikirim') bg-warning
                                                @elseif($vS->status_surat == 'Dibaca') bg-success 
                                                @elseif($vS->status_surat == 'Disetujui') bg-success 
                                                @elseif($vS->status_surat == 'Ditolak') bg-danger 
                                                @endif">{{ $vS->status_surat }}</span> {{ $vS->pegawai->nama }} ({{ $vS->tanggal_verifikasi }})</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                    <li class="list-group-item"><strong>Disposisi:</strong>
                                        <ul>
                                            @foreach ($disposisiAll as $dA)
                                            <li><span class="badge 
                                                @if($dA->status_disposisi == 'Dikirim') bg-warning
                                                @elseif($dA->status_disposisi == 'Dibaca') bg-success 
                                                @elseif($dA->status_disposisi == 'Ditindaklanjuti') bg-success 
                                                @elseif($dA->status_disposisi == 'Selesai') bg-success 
                                                @endif">{{ $dA->status_disposisi }}</span> {{ $dA->pegawai2->nama }} ({{ $dA->tanggal_disposisi }})</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: PDF Viewer -->
                    <div class="tab-pane" id="profile" role="tabpanel">
                        <!-- Embed PDF.js Viewer -->
<iframe src="{{ asset('assets/libs/pdfjs/web/viewer.html?file=' . urlencode($pdfUrl) . '&v=' . time()) }}" width="100%" height="600px"></iframe>

                    </div>

                    <!-- Tab 3: Lampiran -->
                    <div class="tab-pane" id="lampiran" role="tabpanel">
                        @if(!empty($surat->file_lampiran))
    @php
        $fileName = basename($surat->file_lampiran);
        $viewerURL = asset('assets/libs/pdfjs/web/viewer.html') . '?file=' . urlencode(route('surat_keluar.show', $fileName)) . '&v=' . time();
        $downloadURL = Storage::url($surat->file_lampiran);
    @endphp

    <iframe src="{{ $viewerURL }}" width="100%" height="600px"></iframe>

    <p class="text-center mt-3">
        <a href="{{ $downloadURL }}" class="btn btn-primary" download>Download PDF</a>
    </p>
@else
    <h4 class="text-center">Tidak Ada Lampiran</h4>
@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection