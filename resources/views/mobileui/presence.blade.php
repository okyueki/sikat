@extends('mobileui.layouts.mobile')

@section('title', 'Presensi - SIKAT Mobile')
@section('body_style', 'background-color:#e9ecef;')
@section('has_header', true)

@section('content')
    @include('mobileui.partials.header', [
        'title' => 'Presensi',
        'showBack' => true,
        'bgClass' => 'bg-primary',
        'textClass' => 'text-light',
    ])

    <div id="appCapsule">
        <div class="section mt-2">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Gagal</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="section">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Informasi Pegawai</div>
                    <ul class="listview image-listview">
                        <li>
                            <div class="item">
                                <div class="icon-box bg-primary">
                                    <ion-icon name="person-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>Nama</div>
                                    <span class="text-muted">{{ $pegawai->nama ?? '-' }}</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-secondary">
                                    <ion-icon name="id-card-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>NIK</div>
                                    <span class="text-muted">{{ $pegawai->nik ?? '-' }}</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-warning">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>Hari / Tanggal</div>
                                    <span class="text-muted">{{ $hariNama ?? '-' }}, {{ $tanggalHariIni ?? '-' }}</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <div class="icon-box bg-success">
                                    <ion-icon name="time-outline"></ion-icon>
                                </div>
                                <div class="in">
                                    <div>Jam</div>
                                    <span class="text-muted"><span id="currentTime">{{ $jamSaatIni ?? '-' }}</span></span>
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
                    <div class="section-title">Jadwal Shift Hari Ini</div>
                    @if ($jadwalAda && !empty($shiftHariIni['shift']))
                        <div class="alert alert-success mb-2">
                            <div><strong>Shift:</strong> {{ $shiftHariIni['shift'] }}</div>
                            @if ($jamJaga)
                                <div class="mt-1 text-muted">
                                    <div><strong>Jam Masuk:</strong> {{ $jamJaga->jam_masuk ?? '-' }}</div>
                                    <div><strong>Jam Pulang:</strong> {{ $jamJaga->jam_pulang ?? '-' }}</div>
                                </div>
                            @else
                                <div class="mt-1 text-muted">
                                    Data jam masuk/pulang untuk shift ini belum dikonfigurasi.
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-danger mb-2">
                            Jadwal shift hari ini tidak ditemukan. Silakan hubungi administrator / cek jadwal.
                        </div>
                    @endif

                    <div class="alert alert-info mb-0">
                        <div><strong>Status Presensi:</strong>
                            @if ($statusPresensi === 'belum')
                                <span class="badge badge-warning">Belum Presensi</span>
                            @elseif($statusPresensi === 'datang')
                                <span class="badge badge-success">Sudah Datang</span>
                            @else
                                <span class="badge badge-primary">Selesai</span>
                            @endif
                        </div>
                        <div class="mt-1 text-muted small">
                            @if ($statusPresensi === 'belum')
                                Silakan ambil foto untuk <strong>Presensi Datang</strong>.
                            @elseif($statusPresensi === 'datang')
                                Silakan ambil foto untuk <strong>Presensi Pulang</strong>.
                            @else
                                Anda sudah menyelesaikan presensi hari ini.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Presensi --}}
        <div class="section mt-2">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Ambil Foto Presensi</div>

                    <div id="gpsStatus" class="alert alert-warning mb-2" style="display:none;">
                        <span id="gpsStatusText">Meminta izin akses lokasi...</span>
                    </div>

                    @if ($jadwalAda && $statusPresensi !== 'selesai')
                        <form id="presensiForm" action="{{ route('absensi.handle') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="form-group boxed">
                                <div class="input-wrapper">
                                    <input type="file" class="form-control" id="imageFile" name="image"
                                        accept="image/*" capture="user" required>
                                </div>
                                <div class="form-text text-muted mt-1">
                                    Pastikan wajah terlihat jelas. Ukuran max 2MB.
                                </div>
                            </div>

                            <div id="previewWrap" class="mt-2" style="display:none;">
                                <div class="section-title mb-1">Preview</div>
                                <div class="wide-block pt-2 pb-2">
                                    <img id="previewImage" src="" alt="Preview" style="width:100%;height:auto;">
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-success btn-lg btn-block" id="btnSubmit" disabled>
                                    <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status"
                                        aria-hidden="true"></span>
                                    <span id="submitText">Simpan Presensi</span>
                                </button>
                            </div>
                        </form>
                    @elseif($statusPresensi === 'selesai')
                        <div class="alert alert-success mb-0">
                            Presensi selesai. Terima kasih!
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            Presensi tidak bisa dilakukan karena jadwal shift tidak ditemukan.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div style="height: 90px;"></div>
    </div>

    @include('mobileui.partials.bottom-menu', ['active' => 'presence'])
@endsection

@push('scripts')
    <script>
        // ===== GPS CONFIG (copy konsep dari presensi/form.blade.php) =====
        const TARGET_LAT = -7.485628943494862;
        const TARGET_LNG = 112.6527141877153;
        const ALLOWED_RADIUS = 30; // meter

        let isLocationValid = false;
        let hasImage = false;

        function isAndroidWebView() {
            return typeof AndroidGPS !== 'undefined';
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function setGpsStatus(type, html) {
            const gpsStatus = document.getElementById('gpsStatus');
            const gpsStatusText = document.getElementById('gpsStatusText');
            if (!gpsStatus || !gpsStatusText) return;
            gpsStatus.style.display = 'block';
            gpsStatus.className = `alert alert-${type} mb-2`;
            gpsStatusText.innerHTML = html;
        }

        function updateSubmitButton() {
            const btn = document.getElementById('btnSubmit');
            if (!btn) return;
            btn.disabled = !(isLocationValid && hasImage);
        }

        function getLocationFromAndroid() {
            return new Promise((resolve, reject) => {
                if (!isAndroidWebView()) return reject('Not in Android WebView');
                try {
                    const data = JSON.parse(AndroidGPS.getLocation());
                    if (data.success) {
                        resolve({
                            lat: data.latitude,
                            lng: data.longitude,
                            accuracy: data.accuracy || 0,
                            isMockLocation: data.is_mock_location || false
                        });
                    } else {
                        reject(data.error || 'Failed to get location');
                    }
                } catch (e) {
                    reject('Error parsing location data');
                }
            });
        }

        function getCurrentLocation() {
            if (isAndroidWebView()) {
                setGpsStatus('warning', 'Mendapatkan lokasi (Android)...');
                getLocationFromAndroid().then(loc => {
                    if (loc.isMockLocation) {
                        isLocationValid = false;
                        setGpsStatus('danger',
                            '<strong>Fake GPS terdeteksi!</strong> Matikan aplikasi fake GPS untuk presensi.');
                        updateSubmitButton();
                        return;
                    }
                    const d = calculateDistance(loc.lat, loc.lng, TARGET_LAT, TARGET_LNG);
                    if (d <= ALLOWED_RADIUS) {
                        isLocationValid = true;
                        setGpsStatus('success', `Lokasi valid. Jarak: ${Math.round(d)} meter.`);
                    } else {
                        isLocationValid = false;
                        setGpsStatus('danger',
                            `Di luar radius presensi. Jarak: ${Math.round(d)} meter (maks ${ALLOWED_RADIUS}).`);
                    }
                    updateSubmitButton();
                }).catch(err => {
                    isLocationValid = false;
                    setGpsStatus('danger', `Gagal mengambil lokasi: ${err}`);
                    updateSubmitButton();
                });
                return;
            }

            if (!navigator.geolocation) {
                isLocationValid = false;
                setGpsStatus('danger', 'Browser tidak mendukung GPS.');
                updateSubmitButton();
                return;
            }

            setGpsStatus('warning', 'Mendapatkan lokasi GPS...');
            navigator.geolocation.getCurrentPosition(pos => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const d = calculateDistance(lat, lng, TARGET_LAT, TARGET_LNG);
                if (d <= ALLOWED_RADIUS) {
                    isLocationValid = true;
                    setGpsStatus('success', `Lokasi valid. Jarak: ${Math.round(d)} meter.`);
                } else {
                    isLocationValid = false;
                    setGpsStatus('danger', `Di luar radius presensi. Jarak: ${Math.round(d)} meter (maks ${ALLOWED_RADIUS}).`);
                }
                updateSubmitButton();
            }, () => {
                isLocationValid = false;
                setGpsStatus('danger', 'Gagal mendapatkan lokasi. Pastikan izin lokasi aktif.');
                updateSubmitButton();
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // GPS
            getCurrentLocation();
            setInterval(getCurrentLocation, 30000);

            // Preview image + enable submit
            const imageFile = document.getElementById('imageFile');
            if (imageFile) {
                imageFile.addEventListener('change', function() {
                    hasImage = !!(this.files && this.files[0]);
                    updateSubmitButton();

                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('previewImage').src = e.target.result;
                            document.getElementById('previewWrap').style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        document.getElementById('previewWrap').style.display = 'none';
                    }
                });
            }

            // jam realtime
            setInterval(function() {
                const now = new Date();
                const t = String(now.getHours()).padStart(2, '0') + ':' +
                    String(now.getMinutes()).padStart(2, '0') + ':' +
                    String(now.getSeconds()).padStart(2, '0');
                const el = document.getElementById('currentTime');
                if (el) el.textContent = t;
            }, 1000);

            // submit loading
            const form = document.getElementById('presensiForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!isLocationValid) {
                        e.preventDefault();
                        alert('Lokasi GPS tidak valid. Pastikan Anda berada dalam radius presensi.');
                        getCurrentLocation();
                        return false;
                    }
                    const btn = document.getElementById('btnSubmit');
                    const sp = document.getElementById('submitSpinner');
                    const tx = document.getElementById('submitText');
                    if (btn && sp && tx) {
                        btn.disabled = true;
                        sp.classList.remove('d-none');
                        tx.textContent = 'Memproses...';
                    }
                });
            }
        });
    </script>
@endpush

