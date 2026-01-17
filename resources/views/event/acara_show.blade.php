@extends('layouts.pages-layouts')

@section('pageTitle', 'Detail Agenda')

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0">{{ $agenda->judul }}</h2>
                            @if($agenda->status_acara)
                                <span class="badge 
                                    @if($agenda->status_acara == 'draft') bg-secondary
                                    @elseif($agenda->status_acara == 'akan_datang') bg-info
                                    @elseif($agenda->status_acara == 'sedang_berlangsung') bg-success
                                    @elseif($agenda->status_acara == 'selesai') bg-primary
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $agenda->status_acara)) }}
                                </span>
                            @endif
                            @if($agenda->suratKeluar)
                                <span class="badge bg-warning">
                                    <i class="fas fa-file-alt"></i> Realisasi Surat Keluar
                                </span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('rekap-absensi') }}?agenda_id={{ $agenda->id }}" class="btn btn-info me-2">
                                <i class="fas fa-chart-bar"></i> Rekap Absensi
                            </a>
                            <a href="{{ route('agenda.pdf', $agenda->id) }}" target="_blank" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </a>
                        </div>
                    </div>

                    {{-- Info Surat Keluar --}}
                    @if($agenda->suratKeluar)
                        <div class="alert alert-info mb-4">
                            <h5><i class="fas fa-file-alt me-2"></i>Realisasi dari Surat Keluar</h5>
                            <p class="mb-1"><strong>Nomor Surat:</strong> 
                                <a href="{{ route('surat_keluar.detail', encrypt($agenda->suratKeluar->kode_surat)) }}" target="_blank">
                                    {{ $agenda->suratKeluar->nomor_surat }}
                                    <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </p>
                            <p class="mb-1"><strong>Perihal:</strong> {{ $agenda->suratKeluar->perihal }}</p>
                            <p class="mb-1"><strong>Tanggal Surat:</strong> {{ \Carbon\Carbon::parse($agenda->suratKeluar->tanggal_surat)->format('d M Y') }}</p>
                            <p class="mb-0">
                                <strong>Status Realisasi:</strong> 
                                <span class="badge 
                                    @if($agenda->status_realisasi == 'belum') bg-secondary
                                    @elseif($agenda->status_realisasi == 'sedang') bg-warning
                                    @elseif($agenda->status_realisasi == 'selesai') bg-success
                                    @endif">
                                    {{ ucfirst($agenda->status_realisasi) }}
                                </span>
                            </p>
                        </div>
                    @endif
                    
                    {{-- Statistik Absensi --}}
                    <div class="row mb-4">
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="card overflow-hidden sales-card bg-warning-gradient">
                                <div class="px-3 pt-3 pb-2 pt-0">
                                    <div>
                                        <h6 class="mb-3 fs-12 text-fixed-white">Jumlah Undangan</h6>
                                    </div>
                                    <div>
                                        <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahTerundang }} orang</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="card overflow-hidden sales-card bg-success-gradient">
                                <div class="px-3 pt-3 pb-2 pt-0">
                                    <div>
                                        <h6 class="mb-3 fs-12 text-fixed-white">Jumlah Hadir</h6>
                                    </div>
                                    <div>
                                        <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahHadir }} orang</h4>
                                        @if($jumlahTerundang > 0)
                                            <small class="text-fixed-white">({{ number_format(($jumlahHadir / $jumlahTerundang) * 100, 1) }}%)</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="card overflow-hidden sales-card bg-danger-gradient">
                                <div class="px-3 pt-3 pb-2 pt-0">
                                    <div>
                                        <h6 class="mb-3 fs-12 text-fixed-white">Jumlah Tidak Hadir</h6>
                                    </div>
                                    <div>
                                        <h4 class="fs-20 fw-bold mb-1 text-fixed-white">{{ $jumlahTidakHadir }} orang</h4>
                                        @if($jumlahTerundang > 0)
                                            <small class="text-fixed-white">({{ number_format(($jumlahTidakHadir / $jumlahTerundang) * 100, 1) }}%)</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Workflow Guide --}}
                    <div class="card mb-4 bg-light">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="fas fa-tasks me-2"></i>Alur Pengerjaan Acara</h5>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-success rounded-circle p-2">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <small class="d-block fw-bold">1. Buat Acara</small>
                                            <small class="text-muted">Form sudah dibuat</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="badge {{ isset($materiFiles) && $materiFiles->count() > 0 ? 'bg-success' : 'bg-warning' }} rounded-circle p-2">
                                                @if(isset($materiFiles) && $materiFiles->count() > 0)
                                                    <i class="fas fa-check"></i>
                                                @else
                                                    <i class="fas fa-file-alt"></i>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <small class="d-block fw-bold">2. Upload Materi</small>
                                            <small class="text-muted">
                                                @if(isset($materiFiles) && $materiFiles->count() > 0)
                                                    {{ $materiFiles->count() }} file
                                                @else
                                                    Belum ada
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="badge {{ isset($dokumentasiFiles) && $dokumentasiFiles->count() > 0 ? 'bg-success' : 'bg-secondary' }} rounded-circle p-2">
                                                @if(isset($dokumentasiFiles) && $dokumentasiFiles->count() > 0)
                                                    <i class="fas fa-check"></i>
                                                @else
                                                    <i class="fas fa-images"></i>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <small class="d-block fw-bold">3. Upload Foto</small>
                                            <small class="text-muted">
                                                @if(isset($dokumentasiFiles) && $dokumentasiFiles->count() > 0)
                                                    {{ $dokumentasiFiles->count() }} foto
                                                @else
                                                    Opsional
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="badge {{ $agenda->kesimpulan_notulen ? 'bg-success' : 'bg-warning' }} rounded-circle p-2">
                                                @if($agenda->kesimpulan_notulen)
                                                    <i class="fas fa-check"></i>
                                                @else
                                                    <i class="fas fa-sticky-note"></i>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <small class="d-block fw-bold">4. Catat Kesimpulan</small>
                                            <small class="text-muted">
                                                @if($agenda->kesimpulan_notulen)
                                                    Sudah dicatat
                                                @else
                                                    Notulen mencatat
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab Navigation --}}
                    <ul class="nav nav-tabs mt-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#detail" role="tab" title="Informasi lengkap acara">
                                <i class="fas fa-info-circle me-1"></i> Detail
                                <span class="badge bg-info ms-1">{{ $jumlahTerundang }} undangan</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#materi" role="tab" title="Materi acara yang dapat diupload">
                                <i class="fas fa-file-alt me-1"></i> Materi 
                                @if(isset($materiFiles) && $materiFiles->count() > 0)
                                    <span class="badge bg-success">{{ $materiFiles->count() }} file</span>
                                @else
                                    <span class="badge bg-warning">Belum ada</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#dokumentasi" role="tab" title="Foto dokumentasi acara (opsional)">
                                <i class="fas fa-images me-1"></i> Dokumentasi
                                @if(isset($dokumentasiFiles) && $dokumentasiFiles->count() > 0)
                                    <span class="badge bg-success">{{ $dokumentasiFiles->count() }} foto</span>
                                @else
                                    <span class="badge bg-secondary">Opsional</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#absensi" role="tab" title="Daftar kehadiran peserta">
                                <i class="fas fa-check-circle me-1"></i> Absensi
                                @if($jumlahTerundang > 0)
                                    <span class="badge bg-success">{{ $jumlahHadir }}/{{ $jumlahTerundang }}</span>
                                    @if($jumlahTidakHadir > 0)
                                        <span class="badge bg-danger">{{ $jumlahTidakHadir }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">-</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#kesimpulan" role="tab" title="Kesimpulan acara oleh notulen">
                                <i class="fas fa-sticky-note me-1"></i> Kesimpulan
                                @if($agenda->kesimpulan_notulen)
                                    <span class="badge bg-success">Selesai</span>
                                @else
                                    <span class="badge bg-warning">Belum</span>
                                @endif
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-3">
                        {{-- Tab Detail --}}
                        <div class="tab-pane active" id="detail" role="tabpanel">
                            <p><strong>Deskripsi:</strong> {{ $agenda->deskripsi }}</p>
                            <p><strong>Mulai:</strong> {{ \Carbon\Carbon::parse($agenda->mulai)->format('d M Y H:i') }}</p>
                            <p><strong>Akhir:</strong> {{ $agenda->akhir ? \Carbon\Carbon::parse($agenda->akhir)->format('d M Y H:i') : '-' }}</p>
                            <p><strong>Tempat:</strong> {{ $agenda->tempat ?? '-' }}</p>
                            <p><strong>Pimpinan Rapat:</strong> {{ $agenda->pimpinan->nama ?? '-' }}</p>
                            <p><strong>Notulen:</strong> {{ $agenda->notulenPegawai->nama ?? '-' }}</p>
                            <p><strong>Keterangan:</strong> {{ $agenda->keterangan ?? '-' }}</p>
                            
                            {{-- Tampilkan jumlah terundang --}}
                            <p><strong>Yang Terundang:</strong> 
                                @if($isAll)
                                    <span class="badge bg-success">Semua Pegawai ({{ $jumlahTerundang }} orang)</span>
                                @else
                                    <span class="badge bg-primary">{{ $jumlahTerundang }} orang</span>
                                @endif
                            </p>

                            {{-- Tampilkan daftar hanya jika bukan "all" --}}
                            @if(!$isAll && !empty($listTerundang))
                                <div class="mt-2">
                                    <strong>Daftar Nama ({{ $jumlahTerundang }} orang):</strong>
                                    
                                    {{-- Search Box --}}
                                    <div class="mb-3 mt-2">
                                        <input type="text" id="searchTerundang" class="form-control" placeholder="Cari nama pegawai..." style="max-width: 300px;">
                                    </div>

                                    {{-- Pagination Info --}}
                                    <div class="mb-2">
                                        <span id="pagination-info" class="text-muted"></span>
                                    </div>

                                    {{-- List dengan pagination --}}
                                    <div id="terundang-list-container">
                                        <ul id="terundang-list" class="mb-0 list-unstyled">
                                            @foreach($listTerundang as $nik)
                                                @php
                                                    $pegawai = \App\Models\Pegawai::where('nik', $nik)->first();
                                                @endphp
                                                <li class="terundang-item" data-nama="{{ strtolower($pegawai ? $pegawai->nama : 'Pegawai tidak ditemukan (' . $nik . ')') }}">
                                                    <i class="fas fa-user me-2"></i>{{ $pegawai ? $pegawai->nama : 'Pegawai tidak ditemukan (' . $nik . ')' }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    {{-- Pagination Controls --}}
                                    <nav aria-label="Pagination untuk daftar terundang" class="mt-3">
                                        <ul class="pagination pagination-sm" id="pagination-controls">
                                            <!-- Pagination akan di-generate oleh JavaScript -->
                                        </ul>
                                    </nav>
                                </div>
                            @elseif($isAll)
                                <div class="mt-2">
                                    <em>Semua pegawai aktif diundang ({{ $jumlahTerundang }} orang).</em>
                                </div>
                            @endif

                        {{-- Tampilkan Foto jika ada (foto lama dari form create) --}}
                        @if($agenda->foto)
                            <div class="mt-3">
                                <strong>Foto Cover:</strong><br>
                                <img src="{{ asset('storage/' . $agenda->foto) }}" alt="Foto Agenda" class="img-thumbnail" style="max-width: 400px; max-height: 400px;">
                            </div>
                        @endif

                        {{-- Tampilkan Materi jika ada (materi lama dari form create) --}}
                        @if($agenda->materi)
                            <div class="mt-3">
                                <strong>Materi Awal:</strong><br>
                                <a href="{{ asset('storage/' . $agenda->materi) }}" target="_blank" class="btn btn-info">
                                    <i class="fas fa-download"></i> Download Materi
                                </a>
                                <small class="text-muted d-block mt-2">Materi ini diupload saat membuat acara. Materi tambahan dapat diupload di tab "Materi".</small>
                            </div>
                        @endif
                        </div>

                        {{-- Tab Materi --}}
                        <div class="tab-pane" id="materi" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-file-alt me-2"></i>Materi Acara</h5>
                                @if(Auth::check() && ($agenda->created_by == Auth::user()->username || !$agenda->created_by))
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadMateriModal">
                                        <i class="fas fa-upload me-1"></i> Upload Materi
                                    </button>
                                @endif
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Cara Upload Materi:</strong> Klik tombol "Upload Materi" di atas, pilih file (PDF, DOC, DOCX), maksimal 2MB per file. Materi dapat diupload sebelum atau sesudah acara dimulai.
                            </div>

                            @if(isset($materiFiles) && $materiFiles->count() > 0)
                                <div class="list-group">
                                    @foreach($materiFiles as $materi)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-file-alt me-2"></i>
                                                    <strong>{{ $materi->nama_file }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ number_format($materi->ukuran_file / 1024, 2) }} KB | 
                                                        Diupload oleh: {{ $materi->uploader->nama ?? 'N/A' }} | 
                                                        {{ \Carbon\Carbon::parse($materi->diupload_pada)->format('d M Y H:i') }}
                                                    </small>
                                                    @if($materi->keterangan)
                                                        <br><small class="text-muted">{{ $materi->keterangan }}</small>
                                                    @endif
                                                </div>
                                                <a href="{{ asset('storage/' . $materi->path_file) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Belum ada materi yang diupload. Klik tombol "Upload Materi" untuk menambahkan materi acara.
                                </div>
                            @endif
                        </div>

                        {{-- Tab Dokumentasi --}}
                        <div class="tab-pane" id="dokumentasi" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-images me-2"></i>Foto Dokumentasi Acara</h5>
                                @if(Auth::check() && ($agenda->created_by == Auth::user()->username || !$agenda->created_by))
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDokumentasiModal">
                                        <i class="fas fa-upload me-1"></i> Upload Dokumentasi
                                    </button>
                                @endif
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Cara Upload Dokumentasi:</strong> Upload foto dokumentasi setelah acara selesai (opsional). Klik tombol "Upload Dokumentasi", pilih foto (JPG, JPEG, PNG), maksimal 2MB per file. Jika acara ini realisasi dari Surat Keluar, upload dokumentasi akan mengubah status realisasi menjadi "Selesai".
                            </div>

                            @if(isset($dokumentasiFiles) && $dokumentasiFiles->count() > 0)
                                <div class="row row-cols-1 row-cols-md-3 g-4">
                                    @foreach($dokumentasiFiles as $doc)
                                        <div class="col">
                                            <div class="card h-100">
                                                <img src="{{ asset('storage/' . $doc->path_file) }}" class="card-img-top" alt="Dokumentasi" style="height: 200px; object-fit: cover; cursor: pointer;" onclick="window.open('{{ asset('storage/' . $doc->path_file) }}', '_blank')">
                                                <div class="card-body">
                                                    <h6 class="card-title">{{ $doc->nama_file }}</h6>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            Diupload oleh: {{ $doc->uploader->nama ?? 'N/A' }}<br>
                                                            {{ \Carbon\Carbon::parse($doc->diupload_pada)->format('d M Y H:i') }}
                                                        </small>
                                                    </p>
                                                    @if($doc->keterangan)
                                                        <p class="card-text"><small class="text-muted">{{ $doc->keterangan }}</small></p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-secondary">
                                    <i class="fas fa-info-circle me-2"></i>Belum ada foto dokumentasi yang diupload. Upload dokumentasi bersifat opsional dan dapat dilakukan setelah acara selesai.
                                </div>
                            @endif
                        </div>

                        {{-- Tab Absensi --}}
                        <div class="tab-pane" id="absensi" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-check-circle me-2"></i>Daftar Absensi</h5>
                                <div>
                                    @if($jumlahTerundang > 0)
                                        <span class="badge bg-info me-2">
                                            Total: {{ $jumlahTerundang }} undangan
                                        </span>
                                        <span class="badge bg-success me-2">
                                            Hadir: {{ $jumlahHadir }} ({{ number_format(($jumlahHadir / $jumlahTerundang) * 100, 1) }}%)
                                        </span>
                                        @if($jumlahTidakHadir > 0)
                                            <span class="badge bg-danger">
                                                Tidak Hadir: {{ $jumlahTidakHadir }} ({{ number_format(($jumlahTidakHadir / $jumlahTerundang) * 100, 1) }}%)
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            
                            @if($jumlahTerundang > 0)
                                <div class="progress mb-3" style="height: 30px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $jumlahTerundang > 0 ? ($jumlahHadir / $jumlahTerundang) * 100 : 0 }}%" aria-valuenow="{{ $jumlahHadir }}" aria-valuemin="0" aria-valuemax="{{ $jumlahTerundang }}">
                                        <strong>{{ $jumlahHadir }} Hadir ({{ number_format(($jumlahHadir / $jumlahTerundang) * 100, 1) }}%)</strong>
                                    </div>
                                    @if($jumlahTidakHadir > 0)
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $jumlahTerundang > 0 ? ($jumlahTidakHadir / $jumlahTerundang) * 100 : 0 }}%" aria-valuenow="{{ $jumlahTidakHadir }}" aria-valuemin="0" aria-valuemax="{{ $jumlahTerundang }}">
                                            <strong>{{ $jumlahTidakHadir }} Tidak Hadir ({{ number_format(($jumlahTidakHadir / $jumlahTerundang) * 100, 1) }}%)</strong>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Sudah Absen ({{ $absensiList->count() }})</h5>
                                        </div>
                                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                            @if($absensiList->count() > 0)
                                                <ul class="list-group list-group-flush">
                                                    @foreach($absensiList as $absensi)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong>{{ $absensi->pegawai->nama ?? 'Tidak ditemukan' }}</strong><br>
                                                                <small class="text-muted">
                                                                    {{ $absensi->pegawai->jbtn ?? '-' }} | 
                                                                    {{ \Carbon\Carbon::parse($absensi->waktu_kehadiran)->format('d M Y H:i') }}
                                                                </small>
                                                            </div>
                                                            <span class="badge bg-success">Hadir</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted text-center">Belum ada yang absen</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Belum Absen ({{ $belumAbsenList->count() }})</h5>
                                        </div>
                                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                            @if($belumAbsenList->count() > 0)
                                                <ul class="list-group list-group-flush">
                                                    @foreach($belumAbsenList as $pegawai)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong>{{ $pegawai->nama }}</strong><br>
                                                                <small class="text-muted">{{ $pegawai->jbtn ?? '-' }}</small>
                                                            </div>
                                                            <span class="badge bg-danger">Belum</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-success text-center"><i class="fas fa-check-circle me-2"></i>Semua sudah absen!</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Kesimpulan --}}
                        <div class="tab-pane" id="kesimpulan" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-sticky-note me-2"></i>Kesimpulan Notulen</h5>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Cara Mencatat Kesimpulan:</strong> Hanya notulen yang dapat mencatat kesimpulan acara. Masukkan kesimpulan atau isi acara di form di bawah ini, lalu klik "Simpan Kesimpulan".
                            </div>
                            
                            @if($agenda->kesimpulan_notulen)
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Kesimpulan Sudah Dicatat</h6>
                                    </div>
                                    <div class="card-body">
                                        {!! nl2br(e($agenda->kesimpulan_notulen)) !!}
                                        @if($agenda->tanggal_selesai_notulen)
                                            <hr>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Selesai dicatat: {{ \Carbon\Carbon::parse($agenda->tanggal_selesai_notulen)->format('d M Y H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Kesimpulan belum dicatat oleh notulen
                                </div>
                            @endif

                            {{-- Form untuk notulen --}}
                            @if(Auth::check() && Auth::user()->username == $agenda->notulen)
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Catat Kesimpulan</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('agenda.kesimpulan', $agenda->id) }}" method="POST">
                                            @csrf
                                            <div class="form-group">
                                                <label>Kesimpulan/Isi Acara</label>
                                                <textarea name="kesimpulan_notulen" class="form-control" rows="10" required>{{ $agenda->kesimpulan_notulen ?? '' }}</textarea>
                                                <small class="text-muted">Catat kesimpulan atau isi dari acara ini</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Simpan Kesimpulan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @elseif($agenda->notulen)
                                <div class="alert alert-secondary">
                                    <i class="fas fa-user me-2"></i>Hanya <strong>{{ $agenda->notulenPegawai->nama ?? $agenda->notulen }}</strong> yang dapat mencatat kesimpulan acara ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Upload Materi --}}
    <div class="modal fade" id="uploadMateriModal" tabindex="-1" aria-labelledby="uploadMateriModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadMateriModalLabel">Upload Materi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('agenda.upload-materi', $agenda->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih File Materi</label>
                            <input type="file" name="materi[]" class="form-control" multiple accept=".pdf,.doc,.docx" required>
                            <small class="text-muted">Maksimal 2MB per file. Format: PDF, DOC, DOCX</small>
                        </div>
                        <div class="form-group">
                            <label>Keterangan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Upload Dokumentasi --}}
    <div class="modal fade" id="uploadDokumentasiModal" tabindex="-1" aria-labelledby="uploadDokumentasiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadDokumentasiModalLabel">Upload Foto Dokumentasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('agenda.upload-dokumentasi', $agenda->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih Foto Dokumentasi</label>
                            <input type="file" name="dokumentasi[]" class="form-control" multiple accept="image/*" required>
                            <small class="text-muted">Maksimal 2MB per file. Format: JPG, JPEG, PNG</small>
                        </div>
                        <div class="form-group">
                            <label>Keterangan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(!$isAll && !empty($listTerundang))
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const itemsPerPage = 10; // Jumlah item per halaman
    let currentPage = 1;
    let filteredItems = [];
    let allItems = Array.from(document.querySelectorAll('.terundang-item'));
    
    const searchInput = document.getElementById('searchTerundang');
    const listContainer = document.getElementById('terundang-list');
    const paginationControls = document.getElementById('pagination-controls');
    const paginationInfo = document.getElementById('pagination-info');
    
    // Fungsi untuk menampilkan items
    function displayItems() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const itemsToShow = filteredItems.slice(startIndex, endIndex);
        
        // Clear list
        listContainer.innerHTML = '';
        
        // Add items
        itemsToShow.forEach(item => {
            listContainer.appendChild(item.cloneNode(true));
        });
        
        // Update pagination info
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        const startItem = filteredItems.length > 0 ? startIndex + 1 : 0;
        const endItem = Math.min(endIndex, filteredItems.length);
        
        if (filteredItems.length > 0) {
            paginationInfo.textContent = `Menampilkan ${startItem}-${endItem} dari ${filteredItems.length} pegawai`;
        } else {
            paginationInfo.textContent = 'Tidak ada pegawai yang ditemukan';
        }
        
        // Generate pagination controls
        generatePagination(totalPages);
    }
    
    // Fungsi untuk generate pagination controls
    function generatePagination(totalPages) {
        paginationControls.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>`;
        paginationControls.appendChild(prevLi);
        
        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';
            firstLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(1); return false;">1</a>`;
            paginationControls.appendChild(firstLi);
            
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
                paginationControls.appendChild(ellipsisLi);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
            paginationControls.appendChild(li);
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
                paginationControls.appendChild(ellipsisLi);
            }
            
            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';
            lastLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>`;
            paginationControls.appendChild(lastLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>`;
        paginationControls.appendChild(nextLi);
    }
    
    // Fungsi untuk change page (global untuk onclick)
    window.changePage = function(page) {
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        displayItems();
        
        // Scroll to top of list
        document.getElementById('terundang-list-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    
    // Search functionality
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        currentPage = 1; // Reset to first page when searching
        
        if (searchTerm === '') {
            filteredItems = allItems;
        } else {
            filteredItems = allItems.filter(item => {
                const nama = item.getAttribute('data-nama');
                return nama.includes(searchTerm);
            });
        }
        
        displayItems();
    });
    
    // Initialize
    filteredItems = allItems;
    displayItems();
    });
    </script>
    @endif
@endsection