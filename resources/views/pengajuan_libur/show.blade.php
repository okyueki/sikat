@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title :  $title)

@section('content')
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#home" role="tab">
                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                    <span class="d-none d-sm-block">Detail Pengajuan Libur</span>    
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
                        <!-- Tab panes -->
                        <div class="tab-content p-3 text-muted">
                            <div class="tab-pane active" id="home" role="tabpanel">
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Jenis Pengajuan Libur</div>
                                   {{ $pengajuanlibur->jenis_pengajuan_libur }}
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Nama Pegawai</div>
                                    {{ $pengajuanlibur->pegawai->nama }}
                                    </div>
                                   
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Alamat</div>
                                    {{ $pengajuanlibur->alamat }}
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Keterangan</div>
                                    {{ $pengajuanlibur->keterangan }}
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Tanggal Dibuat</div>
                                    {{ $pengajuanlibur->tanggal_dibuat }}
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Status</div>
                                    <span style="font-size: 14px;" class="badge 
                                        @if($pengajuanlibur->status == 'Dikirim') bg-warning 
                                        @elseif($pengajuanlibur->status == 'Disetujui') bg-success 
                                        @elseif($pengajuanlibur->status == 'Ditolak') bg-danger 
                                        @endif">
                                        {{ $pengajuanlibur->status }}
                                    </span>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Catatan</div>
                                    {{ $pengajuanlibur->catatan }}
                                    </div>
                                </li>
                            </ol>
                            </div>
                            <div class="tab-pane" id="profile" role="tabpanel">
<iframe src="{{ asset('assets/libs/pdfjs/web/viewer.html?file=' . urlencode($pdfUrl) . '&v=' . time()) }}" width="100%" height="600px"></iframe>
                            </div>
                             <div class="tab-pane" id="lampiran" role="tabpanel">
                        @if(!empty($pengajuanlibur->foto))
                            @php
                                $lampiranUrl = asset('storage/' . $pengajuanlibur->foto);
                                $lampiranExt = strtolower(pathinfo($pengajuanlibur->foto, PATHINFO_EXTENSION));
                                $isPdf = $lampiranExt === 'pdf';
                                $isImage = in_array($lampiranExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                            @endphp
                            <div class="mb-3 d-flex flex-wrap gap-2">
                                <a href="{{ $lampiranUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>Buka di tab baru
                                </a>
                                <a href="{{ $lampiranUrl }}" class="btn btn-primary btn-sm" download="{{ basename($pengajuanlibur->foto) }}">
                                    <i class="fas fa-download me-1"></i>Unduh lampiran
                                </a>
                            </div>
                            @if($isPdf)
                                <iframe src="{{ asset('assets/libs/pdfjs/web/viewer.html?file=' . urlencode($lampiranUrl) . '&v=' . time()) }}" width="100%" height="600px" class="border rounded"></iframe>
                            @elseif($isImage)
                                <div class="text-center">
                                    <img src="{{ $lampiranUrl }}" alt="Lampiran" class="img-fluid rounded border" style="max-height: 70vh; object-fit: contain;">
                                </div>
                            @else
                                <p class="text-muted mb-0">Pratinjau tidak tersedia untuk jenis file ini. Gunakan tombol di atas untuk membuka atau mengunduh.</p>
                            @endif
                        @else
                            <h4 class="text-center text-muted">Tidak ada lampiran</h4>
                        @endif
                    </div>
                        </div>                                  
                    </div>
                </div>
            </div>
        </div>
<!-- End Page-content -->
@endsection