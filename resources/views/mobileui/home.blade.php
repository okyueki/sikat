@extends('mobileui.layouts.mobile')

@section('title', 'SIKAT Mobile')
@section('body_style', 'background-color:#e9ecef;')

@section('content')
    @php
        $namaUser = \Illuminate\Support\Facades\Auth::user()->name ?? 'User';
        $pegawaiNama = $pegawai->nama ?? $namaUser;
        $pegawaiRole = $pegawai->jbtn ?? ($pegawai->bidang ?? '');

        $initial = strtoupper(mb_substr($pegawaiNama ?: $namaUser, 0, 1));
        $photoUrl = isset($pegawai) && function_exists('getPegawaiPhotoUrl') ? getPegawaiPhotoUrl($pegawai->photo ?? null) : null;

        $jamMasuk = isset($presensiHariIni) && $presensiHariIni?->jam_datang ? \Carbon\Carbon::parse($presensiHariIni->jam_datang)->format('H:i') : '--:--';
        $jamPulang = isset($presensiHariIni) && $presensiHariIni?->jam_pulang ? \Carbon\Carbon::parse($presensiHariIni->jam_pulang)->format('H:i') : '--:--';
    @endphp

    <!-- App Capsule -->
    <div id="appCapsule">
        <div class="section" id="user-section">
            <div id="user-detail">
                <div class="avatar">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Foto {{ $pegawaiNama }}" class="imaged w64 rounded border"
                            onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                        <div class="imaged w64 rounded border bg-light text-muted d-none d-flex align-items-center justify-content-center"
                            style="font-weight:700;font-size:22px;">
                            {{ $initial }}
                        </div>
                    @else
                        <div class="imaged w64 rounded border bg-light text-muted d-flex align-items-center justify-content-center"
                            style="font-weight:700;font-size:22px;">
                            {{ $initial }}
                        </div>
                    @endif
                </div>
                <div id="user-info">
                    <h2 id="user-name">{{ $pegawaiNama }}</h2>
                    <span id="user-role">{{ $pegawaiRole ?: ' ' }}</span>
                    @if (!empty($presensiMessage))
                        <div class="text-white-50" style="font-size: 13px; margin-top: 4px;">
                            {{ $presensiMessage }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Menu Quick Actions --}}
        <div class="section mt-3" id="menu-section" style="margin-top: 160px !important;">
            <div class="card">
                <div class="card-body text-center">
                    <div class="list-menu">
                        <div class="item-menu text-center">
                            <div class="menu-icon">
                                <a href="{{ url('/mobile/profile') }}" class="green" style="font-size: 40px;">
                                    <ion-icon name="person-sharp"></ion-icon>
                                </a>
                            </div>
                            <div class="menu-name">
                                <span class="text-center">Profil</span>
                            </div>
                        </div>
                        <div class="item-menu text-center">
                            <div class="menu-icon">
                                <a href="{{ url('/mobile/cuti') }}" class="danger" style="font-size: 40px;">
                                    <ion-icon name="calendar-number"></ion-icon>
                                </a>
                            </div>
                            <div class="menu-name">
                                <span class="text-center">Cuti</span>
                            </div>
                        </div>
                        <div class="item-menu text-center">
                            <div class="menu-icon">
                                <a href="{{ url('/mobile/histori') }}" class="warning" style="font-size: 40px;">
                                    <ion-icon name="document-text"></ion-icon>
                                </a>
                            </div>
                            <div class="menu-name">
                                <span class="text-center">Histori</span>
                            </div>
                        </div>
                        <div class="item-menu text-center">
                            <div class="menu-icon">
                                <a href="{{ url('/mobile/lokasi') }}" class="orange" style="font-size: 40px;">
                                    <ion-icon name="location"></ion-icon>
                                </a>
                            </div>
                            <div class="menu-name">
                                Lokasi
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($agendaTerundang) && $agendaTerundang->count() > 0)
            <div class="section mt-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="section-title mb-0">Agenda Saya ({{ $agendaTerundang->count() }})</div>
                            <a href="{{ route('absensi_agenda.index') }}" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
                        </div>

                        <div class="mt-2">
                            <ul class="listview link-listview">
                                @foreach ($agendaTerundang as $agenda)
                                    <li>
                                        <a href="{{ route('acara_show', $agenda->id) }}" class="item">
                                            <div class="in">
                                                <div>
                                                    <div class="fw-semibold text-truncate" style="max-width: 260px;">
                                                        {{ $agenda->judul }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        <span>{{ $agenda->waktu_info ?? '-' }}</span>
                                                        @if (!empty($agenda->tempat))
                                                            <span> • {{ $agenda->tempat }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center" style="gap:8px;">
                                                    <span class="badge badge-{{ $agenda->status_class ?? 'secondary' }}">
                                                        {{ $agenda->status_label ?? '-' }}
                                                    </span>
                                                    @if (!$agenda->sudah_absen && in_array(($agenda->status_class ?? ''), ['info', 'success'], true))
                                                        <span class="badge badge-success">Scan</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="section mt-3" id="presence-section">
            <div class="todaypresence" style="margin-top: 0 !important;">
                <div class="row">
                    <div class="col-6">
                        <div class="card gradasigreen" onclick="window.location.href='{{ url('/mobile/presence') }}'" style="cursor:pointer;">
                            <div class="card-body text-white">
                                <div class="presencecontent">
                                    <div class="iconpresence">
                                        <ion-icon name="camera"></ion-icon>
                                    </div>
                                    <div class="presencedetail">
                                        <h4 class="presencetitle">Masuk</h4>
                                        <span>{{ $jamMasuk }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card gradasired" onclick="window.location.href='{{ url('/mobile/presence') }}'" style="cursor:pointer;">
                            <div class="card-body text-white">
                                <div class="presencecontent">
                                    <div class="iconpresence">
                                        <ion-icon name="camera"></ion-icon>
                                    </div>
                                    <div class="presencedetail">
                                        <h4 class="presencetitle">Pulang</h4>
                                        <span>{{ $jamPulang }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rekappresence" style="margin-top: 20px;">
                <div id="chartdiv"></div>
            </div>
        </div>

        {{-- Sisa Cuti Tahunan --}}
        <div class="section mt-3">
            <div class="card gradasigreen">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold mb-1">Cuti Tahunan</div>
                            <h3 class="mb-0">{{ $sisaCuti ?? 12 }} Hari</h3>
                            <div class="text-white-50 small">Sisa cuti tahun ini</div>
                        </div>
                        <div>
                            <ion-icon name="calendar-outline" style="font-size: 48px; opacity: 0.7;"></ion-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Presensi (10 terakhir) --}}
        @if (isset($presensiUser) && $presensiUser->count() > 0)
            <div class="section mt-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="section-title mb-0">Riwayat Presensi</div>
                            <a href="{{ route('presensi.index') }}" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
                        </div>

                        <div class="mt-2">
                            <ul class="listview image-listview">
                                @foreach ($presensiUser->take(5) as $presensi)
                                    <li>
                                        <div class="item">
                                            <div class="icon-box bg-primary">
                                                <ion-icon name="time-outline"></ion-icon>
                                            </div>
                                            <div class="in">
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $presensi->jam_datang ? \Carbon\Carbon::parse($presensi->jam_datang)->format('d M Y') : '-' }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        Shift: {{ $presensi->shift ?? '-' }}
                                                        @if ($presensi->jam_datang)
                                                            • {{ \Carbon\Carbon::parse($presensi->jam_datang)->format('H:i') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div>
                                                    @if ($presensi->status)
                                                        <span class="badge badge-{{ strpos(strtolower($presensi->status), 'tepat') !== false ? 'success' : (strpos(strtolower($presensi->status), 'terlambat') !== false ? 'danger' : 'warning') }}">
                                                            {{ $presensi->status }}
                                                        </span>
                                                    @endif
                                                    @if ($presensi->keterlambatan && $presensi->keterlambatan !== '00:00:00')
                                                        <div class="text-muted small mt-1">
                                                            Terlambat: {{ $presensi->keterlambatan }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div style="height: 90px;"></div>
    </div>
    <!-- * App Capsule -->

    @include('mobileui.partials.bottom-menu', ['active' => 'today'])
@endsection

@push('scripts')
    <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
    <script>
        am4core.ready(function() {
            am4core.useTheme(am4themes_animated);

            var chart = am4core.create("chartdiv", am4charts.PieChart3D);
            chart.hiddenState.properties.opacity = 0;
            chart.legend = new am4charts.Legend();

            // Data chart dari controller (rekap presensi bulan ini)
            chart.data = [
                @if(isset($chartData))
                {
                    label: "Hadir",
                    value: {{ $chartData['hadir'] ?? 0 }}
                },
                {
                    label: "Sakit",
                    value: {{ $chartData['sakit'] ?? 0 }}
                },
                {
                    label: "Izin",
                    value: {{ $chartData['izin'] ?? 0 }}
                },
                {
                    label: "Terlambat",
                    value: {{ $chartData['terlambat'] ?? 0 }}
                }
                @else
                {
                    label: "Hadir",
                    value: 0
                },
                {
                    label: "Sakit",
                    value: 0
                },
                {
                    label: "Izin",
                    value: 0
                },
                {
                    label: "Terlambat",
                    value: 0
                }
                @endif
            ];

            var series = chart.series.push(new am4charts.PieSeries3D());
            series.dataFields.value = "value";
            series.dataFields.category = "label";
            series.alignLabels = false;
            series.labels.template.text = "{value.percent.formatNumber('#.0')}%";
            series.labels.template.radius = am4core.percent(-40);
            series.labels.template.fill = am4core.color("white");
            series.colors.list = [
                am4core.color("#1171ba"),
                am4core.color("#fca903"),
                am4core.color("#37db63"),
                am4core.color("#ba113b"),
            ];
        });
    </script>
@endpush
