@extends('mobileui.layouts.mobile')

@section('title', 'Profil - SIKAT Mobile')
@section('body_style', 'background-color:#e9ecef;')
@section('has_header', true)

@section('content')
    @php
        $photoUrl = function_exists('getPegawaiPhotoUrl') ? getPegawaiPhotoUrl($pegawai->photo ?? null) : null;
        $initial = strtoupper(mb_substr($pegawai->nama ?? 'P', 0, 1));
    @endphp

    @include('mobileui.partials.header', [
        'title' => 'Profil',
        'showBack' => true,
        'bgClass' => 'bg-primary',
        'textClass' => 'text-light',
    ])

    <div id="appCapsule">
        <div class="section">
            <div class="card">
                <div class="card-body">
                    <div class="profile-head">
                        <div class="avatar avatar-wrap">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Foto {{ $pegawai->nama }}"
                                    class="imaged w64 rounded border avatar-img"
                                    onerror="this.classList.add('d-none'); this.closest('.avatar-wrap').querySelector('.avatar-fallback').classList.remove('d-none');">
                                <div
                                    class="imaged w64 rounded border bg-light text-muted d-none d-flex align-items-center justify-content-center avatar-fallback"
                                    style="font-weight:700;font-size:22px;">
                                    {{ $initial }}
                                </div>
                            @else
                                <div
                                    class="imaged w64 rounded border bg-light text-muted d-flex align-items-center justify-content-center">
                                    <span style="font-weight:700;font-size:22px;">{{ $initial }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="in">
                            <h3 class="name mb-0">{{ $pegawai->nama }}</h3>
                            <p class="subtext mb-0">NIK {{ $pegawai->nik }}</p>

                            <div class="mt-2 d-flex flex-wrap" style="gap:6px;">
                                @if (!empty($kategoriPegawai))
                                    <span class="badge badge-info text-dark">{{ $kategoriPegawai }}</span>
                                @endif
                                @if ($pegawai->dokter)
                                    <span class="badge badge-primary">Dokter</span>
                                @elseif($pegawai->petugas)
                                    <span class="badge badge-secondary">Petugas</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex" style="gap:10px;">
                        <a href="{{ url('/profil') }}" class="btn btn-primary btn-block">
                            <ion-icon name="create-outline"></ion-icon>
                            <span class="ms-1">Edit Profil</span>
                        </a>
                        <a href="{{ route('logout') }}" class="btn btn-outline-danger btn-block"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <ion-icon name="log-out-outline"></ion-icon>
                            <span class="ms-1">Logout</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="section mt-2">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Informasi</div>
                    <ul class="listview image-listview">
                        <li>
                            <div class="item">
                                <div class="icon-box bg-primary">
                                    <ion-icon name="briefcase-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>
                                        <div class="fw-semibold">Jabatan</div>
                                        <div class="text-muted">{{ $pegawai->jbtn ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-secondary">
                                    <ion-icon name="business-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>
                                        <div class="fw-semibold">Departemen</div>
                                        <div class="text-muted">{{ $pegawai->departemen_unit->nama_departemen ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-warning">
                                    <ion-icon name="grid-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>
                                        <div class="fw-semibold">Bidang</div>
                                        <div class="text-muted">{{ $pegawai->bidang ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-success">
                                    <ion-icon name="call-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>
                                        <div class="fw-semibold">No. Telp</div>
                                        <div class="text-muted">{{ $pegawai->petugas->no_telp ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-danger">
                                    <ion-icon name="mail-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>
                                        <div class="fw-semibold">Email</div>
                                        <div class="text-muted">{{ $pegawai->petugas->email ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-dark">
                                    <ion-icon name="location-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>
                                        <div class="fw-semibold">Alamat</div>
                                        <div class="text-muted">
                                            {{ trim(($pegawai->alamat ?? '') . ' ' . ($pegawai->kota ?? '')) ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="section mt-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="section-title mb-1">Berkas</div>
                            <div class="text-muted small">Ringkas (tap item untuk lihat detail di web)</div>
                        </div>
                        <a href="{{ url('/profil?show_all=1') }}" class="btn btn-outline-primary btn-sm">
                            Lihat Semua
                        </a>
                    </div>

                    @php
                        $statusBadge = function ($status) {
                            $status = strtolower((string) $status);
                            return match ($status) {
                                'approved', 'approve', 'verified', 'verif', 'ok' => 'badge-success',
                                'rejected', 'reject', 'ditolak' => 'badge-danger',
                                'review', 'pending', 'menunggu' => 'badge-warning',
                                default => 'badge-secondary',
                            };
                        };
                    @endphp

                    <div class="mt-2">
                        <ul class="listview image-listview">
                            @forelse (($masterBerkas ?? collect())->take(5) as $m)
                                @php
                                    $latest = ($berkasLatestByKode ?? collect())->get($m->kode);
                                    $meta = ($metaByKode ?? collect())->get($m->kode);
                                    $status = $meta->verifikasi_status ?? null;
                                @endphp
                                <li>
                                    <a href="{{ url('/profil?show_all=1') }}" class="item">
                                        <div class="icon-box bg-primary">
                                            <ion-icon name="document-text-outline"></ion-icon>
                                        </div>
                                        <div class="in">
                                            <div>
                                                <div class="fw-semibold">{{ $m->nama_berkas ?? $m->kode }}</div>
                                                <div class="text-muted small">
                                                    @if ($latest)
                                                        Upload: {{ $latest->tgl_uploud ?? '-' }}
                                                    @else
                                                        Belum ada berkas
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center" style="gap:8px;">
                                                @if ($status)
                                                    <span class="badge {{ $statusBadge($status) }}">{{ $status }}</span>
                                                @endif
                                                <ion-icon name="chevron-forward-outline" class="text-muted"></ion-icon>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <li>
                                    <div class="item">
                                        <div class="in">
                                            <div class="text-muted">Belum ada master berkas untuk kategori ini.</div>
                                        </div>
                                    </div>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div style="height: 90px;"></div>
    </div>

    @include('mobileui.partials.bottom-menu', ['active' => 'profile'])
@endsection

