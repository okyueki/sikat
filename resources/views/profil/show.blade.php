@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Profil')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                @if ($message = Session::get('success'))
                    <div class="alert alert-success">
                        {{ $message }}
                    </div>
                @endif

                @php
                    $photoUrl = getPegawaiPhotoUrl($pegawai->photo ?? null);
                @endphp

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}"
                                 alt="Foto {{ $pegawai->nama }}"
                                 style="width:56px;height:56px;object-fit:cover;border-radius:12px;"
                                 class="border">
                        @else
                            <div class="d-flex align-items-center justify-content-center border bg-light text-muted"
                                 style="width:56px;height:56px;border-radius:12px;font-weight:700;">
                                {{ strtoupper(mb_substr($pegawai->nama ?? 'P', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="h5 mb-0">Profil Pegawai</div>
                            <div class="text-muted small">
                                {{ $pegawai->nama }} • NIK {{ $pegawai->nik }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if(!empty($kategoriPegawai))
                            <span class="badge bg-info text-dark">{{ $kategoriPegawai }}</span>
                        @endif
                        @if($pegawai->dokter)
                            <span class="badge bg-primary">Dokter</span>
                        @elseif($pegawai->petugas)
                            <span class="badge bg-secondary">Petugas</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <form action="{{ route('profil.photo.update') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label class="form-label mb-1">Ganti Foto Profil</label>
                            <input type="file" class="form-control" name="photo" accept="image/png,image/jpeg" required>
                            <div class="form-text">Format JPG/PNG, max 2MB.</div>
                        </div>
                        <div class="col-md-2 d-grid">
                            <label class="form-label mb-1">&nbsp;</label>
                            <button type="submit" class="btn btn-outline-primary">Upload Foto</button>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Disimpan ke folder yang diatur di `.env` (bisa webapps SIMRS atau fallback lokal).
                            </div>
                        </div>
                    </form>
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-profil-btn" data-bs-toggle="tab" data-bs-target="#tab-profil" type="button" role="tab" aria-controls="tab-profil" aria-selected="true">
                            Profil
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-berkas-btn" data-bs-toggle="tab" data-bs-target="#tab-berkas" type="button" role="tab" aria-controls="tab-berkas" aria-selected="false">
                            Berkas
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-profil" role="tabpanel" aria-labelledby="tab-profil-btn">

                <div class="mb-3">
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditProfil" aria-expanded="false" aria-controls="collapseEditProfil">
                        Edit Profil
                    </button>
                </div>

                <div class="collapse mb-4" id="collapseEditProfil">
                    <div class="card card-body border">
                        <form action="{{ route('profil.update') }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-md-6">
                                <label class="form-label">Alamat</label>
                                <input type="text" class="form-control" name="alamat" value="{{ old('alamat', $pegawai->alamat) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kota</label>
                                <input type="text" class="form-control" name="kota" value="{{ old('kota', $pegawai->kota) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Pendidikan</label>
                                <input type="text" class="form-control" name="pendidikan" value="{{ old('pendidikan', $pegawai->pendidikan) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NPWP</label>
                                <input type="text" class="form-control" name="npwp" value="{{ old('npwp', $pegawai->npwp) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rekening</label>
                                <input type="text" class="form-control" name="rekening" value="{{ old('rekening', $pegawai->rekening) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank (BPD)</label>
                                <input type="text" class="form-control" name="bpd" value="{{ old('bpd', $pegawai->bpd) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. KTP</label>
                                <input type="text" class="form-control" name="no_ktp" value="{{ old('no_ktp', $pegawai->no_ktp) }}">
                            </div>

                            @if($pegawai->petugas)
                                <div class="col-12">
                                    <hr>
                                    <div class="fw-semibold">Data Petugas</div>
                                    <div class="text-muted small">Disimpan ke tabel `petugas` (SIMRS)</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Alamat (Petugas)</label>
                                    <input type="text" class="form-control" name="petugas_alamat" value="{{ old('petugas_alamat', $pegawai->petugas->alamat) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. Telp (Petugas)</label>
                                    <input type="text" class="form-control" name="petugas_no_telp" value="{{ old('petugas_no_telp', $pegawai->petugas->no_telp) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email (Petugas)</label>
                                    <input type="email" class="form-control" name="petugas_email" value="{{ old('petugas_email', $pegawai->petugas->email) }}">
                                </div>
                            @endif

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Simpan Profil</button>
                                <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#collapseEditProfil">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <tr class="table-active">
                            <th colspan="2">Data Pegawai</th>
                        </tr>
                        <tr>
                            <th>Nama</th>
                            <td>{{ $pegawai->nama }}</td>
                        </tr>
                        <tr>
                            <th>NIK</th>
                            <td>{{ $pegawai->nik }}</td>
                        </tr>
                        <tr>
                            <th>Jenis Kelamin</th>
                            <td>{{ $pegawai->jk }}</td>
                        </tr>
                        <tr>
                            <th>Jabatan</th>
                            <td>{{ $pegawai->jbtn }}</td>
                        </tr>
                        <tr>
                            <th>Jenjang Jabatan</th>
                            <td>{{ $pegawai->jnj_jabatan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Bidang</th>
                            <td>{{ $pegawai->bidang ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Departemen</th>
                            <td>{{ $pegawai->departemen_unit->nama_departemen ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Kode Departemen</th>
                            <td>{{ $pegawai->departemen ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Tipe Profil</th>
                            <td>
                                @if($pegawai->dokter)
                                    Dokter
                                @elseif($pegawai->petugas)
                                    Petugas
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tanggal Lahir</th>
                            <td>
                                {{ $pegawai->tgl_lahir ? \Carbon\Carbon::parse($pegawai->tgl_lahir)->format('d F Y') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Tempat Lahir</th>
                            <td>{{ $pegawai->tmp_lahir ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td>{{ $pegawai->alamat }}</td>
                        </tr>
                        <tr>
                            <th>Kota</th>
                            <td>{{ $pegawai->kota }}</td>
                        </tr>
                        <tr class="table-active">
                            <th colspan="2">Kepegawaian</th>
                        </tr>
                        <tr>
                            <th>Status Aktif</th>
                            <td>{{ $pegawai->stts_aktif ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status Kerja</th>
                            <td>{{ $pegawai->stts_kerja ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status WP</th>
                            <td>{{ $pegawai->stts_wp ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Pendidikan</th>
                            <td>{{ $pegawai->pendidikan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>NPWP</th>
                            <td>{{ $pegawai->npwp ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Mulai Kerja</th>
                            <td>{{ $pegawai->mulai_kerja ? \Carbon\Carbon::parse($pegawai->mulai_kerja)->format('d F Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Masa Kerja</th>
                            <td>{{ $pegawai->ms_kerja ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Mulai Kontrak</th>
                            <td>{{ $pegawai->mulai_kontrak ? \Carbon\Carbon::parse($pegawai->mulai_kontrak)->format('d F Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Cuti Diambil</th>
                            <td>{{ $pegawai->cuti_diambil ?? '-' }}</td>
                        </tr>
                        <tr class="table-active">
                            <th colspan="2">Keuangan</th>
                        </tr>
                        <tr>
                            <th>Gaji Pokok</th>
                            <td>{{ isset($pegawai->gapok) ? number_format((float) $pegawai->gapok, 0, ',', '.') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Dankes</th>
                            <td>{{ isset($pegawai->dankes) ? number_format((float) $pegawai->dankes, 0, ',', '.') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Rekening</th>
                            <td>{{ $pegawai->rekening ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Bank (BPD)</th>
                            <td>{{ $pegawai->bpd ?? '-' }}</td>
                        </tr>
                        <tr class="table-active">
                            <th colspan="2">Identitas</th>
                        </tr>
                        <tr>
                            <th>No. KTP</th>
                            <td>{{ $pegawai->no_ktp ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Photo</th>
                            <td>
                                @if($photoUrl)
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $photoUrl }}"
                                             alt="Foto {{ $pegawai->nama }}"
                                             style="width:96px;height:96px;object-fit:cover;border-radius:14px;"
                                             class="border">
                                        <div class="text-muted small">
                                            <div class="fw-semibold text-dark">Foto profil</div>
                                            <div class="text-break">{{ $pegawai->photo }}</div>
                                            <div class="mt-2">
                                                <a href="{{ $photoUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Buka</a>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Tidak ada foto</span>
                                    @if(!empty($pegawai->photo))
                                        <div class="text-muted small text-break mt-1">{{ $pegawai->photo }}</div>
                                    @endif
                                @endif
                            </td>
                        </tr>

                        @if($pegawai->dokter)
                            <tr class="table-active">
                                <th colspan="2">Detail Dokter</th>
                            </tr>
                            <tr>
                                <th>Kode Dokter</th>
                                <td>{{ $pegawai->dokter->kd_dokter ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Nama Dokter</th>
                                <td>{{ $pegawai->dokter->nm_dokter ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Spesialis (Kode)</th>
                                <td>{{ $pegawai->dokter->kd_sps ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>No. Izin Praktek</th>
                                <td>{{ $pegawai->dokter->no_ijn_praktek ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email (Dokter)</th>
                                <td>{{ $pegawai->dokter->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>No. Telp (Dokter)</th>
                                <td>{{ $pegawai->dokter->no_telp ?? '-' }}</td>
                            </tr>
                        @elseif($pegawai->petugas)
                            <tr class="table-active">
                                <th colspan="2">Detail Petugas</th>
                            </tr>
                            <tr>
                                <th>NIP</th>
                                <td>{{ $pegawai->petugas->nip ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Jabatan (Petugas)</th>
                                <td>{{ $pegawai->petugas->jabatan->nama_jbtn ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email (Petugas)</th>
                                <td>{{ $pegawai->petugas->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>No. Telp (Petugas)</th>
                                <td>{{ $pegawai->petugas->no_telp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Alamat (Petugas)</th>
                                <td>{{ $pegawai->petugas->alamat ?? '-' }}</td>
                            </tr>
                        @endif
                    </table>

                    </div>

                    <div class="tab-pane fade" id="tab-berkas" role="tabpanel" aria-labelledby="tab-berkas-btn">

                    <div class="mt-4">
                        <h5 class="mb-3">Berkas Kepegawaian</h5>
                        @php
                            $totalMaster = ($masterBerkas ?? collect())->count();
                            $uploadedMaster = ($berkasLatestByKode ?? collect())->filter()->count();
                            $missingMaster = max($totalMaster - $uploadedMaster, 0);
                        @endphp
                        <div class="mb-2">
                            <span class="badge bg-primary">{{ $uploadedMaster }}/{{ $totalMaster }} uploaded</span>
                            @if($missingMaster > 0)
                                <span class="badge bg-secondary">belum upload {{ $missingMaster }}</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            @if(!empty($showAllBerkas))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('profil.show') }}">Tampilkan yang sudah upload saja</a>
                            @else
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('profil.show', ['show_all' => 1]) }}">Tampilkan termasuk yang belum upload</a>
                            @endif
                        </div>

                        @if(($masterBerkas ?? collect())->count() === 0)
                            <div class="alert alert-info mb-0">
                                Master berkas pegawai belum tersedia.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60px;">No</th>
                                            <th>Kode</th>
                                            <th>Nama Berkas</th>
                                            <th>Tgl Upload</th>
                                            <th>Masa Berlaku</th>
                                            <th>Verifikasi</th>
                                            <th style="width:140px;">File</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($masterBerkas as $i => $m)
                                            @php
                                                $berkas = ($berkasLatestByKode ?? collect())->get($m->kode);
                                                $meta = ($metaByKode ?? collect())->get($m->kode);
                                                $hasFile = !empty($berkas?->berkas);
                                                $vs = $hasFile ? ($meta->verifikasi_status ?? 'review') : null;
                                            @endphp
                                            @if(empty($showAllBerkas) && !$hasFile)
                                                @continue
                                            @endif
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $m->kode }}</td>
                                                <td>{{ $m->nama_berkas ?? '-' }}</td>
                                                <td>{{ $berkas?->tgl_uploud ?? '-' }}</td>
                                                <td>
                                                    @if($hasFile)
                                                        <form action="{{ route('profil.berkas.masa.update') }}" method="POST" class="d-flex gap-2 align-items-center">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="kode_berkas" value="{{ $m->kode }}">
                                                            <input type="date"
                                                                   class="form-control form-control-sm"
                                                                   name="masa_berlaku_sampai"
                                                                   value="{{ $meta->masa_berlaku_sampai ?? '' }}">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                                        </form>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!$hasFile)
                                                        <span class="badge bg-secondary">Belum upload</span>
                                                    @elseif($vs === 'approved')
                                                        <span class="badge bg-success">Approved</span>
                                                    @elseif($vs === 'rejected')
                                                        <span class="badge bg-danger">Rejected</span>
                                                    @elseif($vs === 'review')
                                                        <span class="badge bg-warning text-dark">Review</span>
                                                    @else
                                                        <span class="badge bg-secondary">Uploaded</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($hasFile)
                                                        <a class="btn btn-sm btn-primary"
                                                           href="{{ simrs_asset($berkas->berkas) }}"
                                                           target="_blank" rel="noopener">
                                                            Download
                                                        </a>
                                                    @else
                                                        <span class="badge bg-secondary">Belum upload</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="mt-4">
                            <h6 class="mb-2">Upload Berkas (SIMRS)</h6>
                            <form action="{{ route('profil.berkas.update') }}" method="POST" enctype="multipart/form-data" class="row g-2">
                                @csrf
                                @method('PUT')
                                <div class="col-md-5">
                                    <label class="form-label">Jenis Berkas</label>
                                    <select class="form-control" name="kode_berkas" required>
                                        @foreach(($masterBerkas ?? collect()) as $m)
                                            <option value="{{ $m->kode }}">{{ $m->kode }} - {{ $m->nama_berkas }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">File</label>
                                    <input type="file" class="form-control" name="file" required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>    
</div>
@endsection
