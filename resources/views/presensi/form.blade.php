@extends('layouts.pages-layouts')

@section('pageTitle', 'Presensi Pegawai')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <h1 class="mb-4">Presensi Pegawai</h1>
            
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Info Pegawai & Waktu -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card" style="background-color: #f8f9fa;">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fa fa-user me-2"></i>Informasi Pegawai
                                </h5>
                                <p class="mb-1"><strong>Nama:</strong> {{ $pegawai->nama ?? 'Tidak ditemukan' }}</p>
                                <p class="mb-1"><strong>NIK:</strong> {{ $pegawai->nik ?? 'Tidak ditemukan' }}</p>
                                <p class="mb-0"><strong>Departemen:</strong> {{ $pegawai->departemen ?? 'Tidak ditemukan' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card" style="background-color: #e7f3ff;">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fa fa-calendar me-2"></i>Informasi Waktu
                                </h5>
                                <p class="mb-1"><strong>Hari:</strong> {{ $hariNama ?? 'Tidak ditemukan' }}</p>
                                <p class="mb-1"><strong>Tanggal:</strong> {{ $tanggalHariIni ?? 'Tidak ditemukan' }}</p>
                                <p class="mb-0"><strong>Jam:</strong> <span id="currentTime">{{ $jamSaatIni ?? 'Tidak ditemukan' }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Jadwal Shift -->
                @if($jadwalAda && $shiftHariIni)
                <div class="alert alert-success mb-4" role="alert">
                    <h5 class="alert-heading">
                        <i class="fa fa-check-circle me-2"></i>Jadwal Shift Hari Ini
                    </h5>
                    <p class="mb-1"><strong>Shift:</strong> {{ $shiftHariIni['shift'] }}</p>
                    @if($jamJaga)
                        <p class="mb-1"><strong>Jam Masuk:</strong> {{ $jamJaga->jam_masuk ?? '-' }}</p>
                        <p class="mb-0"><strong>Jam Pulang:</strong> {{ $jamJaga->jam_pulang ?? '-' }}</p>
                    @else
                        <p class="mb-0 text-warning">
                            <i class="fa fa-exclamation-triangle me-1"></i>
                            <small>Data jam masuk/pulang untuk shift ini belum dikonfigurasi di sistem.</small>
                        </p>
                    @endif
                </div>
                @else
                <div class="alert alert-danger mb-4" role="alert">
                    <h5 class="alert-heading">
                        <i class="fa fa-exclamation-triangle me-2"></i>Jadwal Tidak Ditemukan
                    </h5>
                    <p class="mb-2">Anda belum memiliki jadwal shift untuk hari ini.</p>
                    <p class="mb-0">
                        <strong>Silakan input jadwal terlebih dahulu di:</strong>
                        <a href="{{ route('jadwal.index') }}" class="alert-link">
                            <i class="fa fa-calendar me-1"></i>Kelola Jadwal Presensi
                        </a>
                    </p>
                </div>
                @endif

                <!-- Status Presensi Hari Ini - Hanya tampilkan jika ada jadwal -->
                @if($jadwalAda)
                <div class="alert alert-info mb-4" role="alert">
                    <h5 class="alert-heading">Status Presensi Hari Ini</h5>
                    @if($statusPresensi === 'belum')
                        <p class="mb-0"><strong>Status:</strong> <span class="badge bg-warning">Belum Presensi</span></p>
                        <p class="mb-0 mt-2">Silakan ambil foto untuk melakukan <strong>Presensi Datang</strong>.</p>
                    @elseif($statusPresensi === 'datang')
                        <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Sudah Presensi Datang</span></p>
                        <p class="mb-0 mt-2">Silakan ambil foto untuk melakukan <strong>Presensi Pulang</strong>.</p>
                    @elseif($statusPresensi === 'selesai')
                        <p class="mb-0"><strong>Status:</strong> <span class="badge bg-primary">Sudah Presensi Datang & Pulang</span></p>
                        <p class="mb-0 mt-2">Anda sudah menyelesaikan presensi hari ini.</p>
                    @endif
                </div>
                @endif

                <!-- Form Presensi -->
                @if($jadwalAda && $statusPresensi !== 'selesai')
                <form id="presensiForm" action="{{ route('absensi.handle') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- GPS Location Status -->
                    <div id="gpsStatus" class="alert alert-warning mb-3" style="display:none;">
                        <i class="fa fa-map-marker-alt me-2"></i>
                        <span id="gpsStatusText">Meminta izin akses lokasi...</span>
                    </div>

                    <!-- Peta Lokasi (OpenStreetMap) -->
                    <div class="mb-4">
                        <h6 class="mb-2"><i class="fas fa-map me-2"></i>Peta Lokasi & Titik Presensi</h6>
                        <p class="text-muted small mb-2">Peta menampilkan titik presensi (biru). Jika izin lokasi diizinkan, posisi Anda (hijau) juga akan muncul.</p>
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" id="btnAmbilLokasi" class="btn btn-outline-primary btn-sm" onclick="getCurrentLocation(); this.disabled=true; setTimeout(function(){ document.getElementById('btnAmbilLokasi').disabled=false; }, 3000);">
                                <i class="fas fa-location-crosshairs me-1"></i> Ambil lokasi saya
                            </button>
                        </div>
                        <div id="presensiMap" style="height: 280px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6; display: none;"></div>
                        <div id="mapPlaceholder" class="text-center py-4 text-muted small bg-light rounded">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>
                            Memuat peta...
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label class="form-label"><strong>Ambil Foto Presensi</strong></label>
                        <div class="alert alert-info">
                            <small>
                                <strong>Petunjuk:</strong>
                                <ol class="mb-0">
                                    <li>Pastikan GPS/lokasi aktif dan berikan izin akses lokasi</li>
                                    <li>Pastikan wajah terlihat jelas di kamera</li>
                                    <li>Klik tombol "Ambil Foto" di bawah</li>
                                    <li>Pastikan foto jelas, lalu klik "Simpan Presensi"</li>
                                </ol>
                            </small>
                        </div>
                        
                        <!-- Error handling untuk webcam -->
                        <div id="camera-error" class="alert alert-warning" style="display:none;">
                            <strong>Peringatan:</strong> Kamera tidak dapat diakses. Pastikan Anda memberikan izin akses kamera.
                        </div>

                        <div id="my_camera" class="mb-3"></div>
                        <input type="hidden" name="image" class="image-tag" id="imageInput">
                        
                        <div class="d-flex gap-2">
                            <button type="button" onclick="take_snapshot()" class="btn btn-primary" id="btnTakePhoto">
                                <i class="fas fa-camera"></i> Ambil Foto
                            </button>
                            <button type="button" onclick="reset_camera()" class="btn btn-secondary" id="btnReset" style="display:none;">
                                <i class="fas fa-redo"></i> Ambil Ulang
                            </button>
                        </div>
                    </div>
            
                    <div id="results" class="mb-4" style="display:none;">
                        <h6>Preview Foto:</h6>
                        <div class="border rounded p-2" style="max-width: 400px;">
                            <img id="previewImage" src="" alt="Preview" style="max-width: 100%; height: auto;">
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-lg" id="btnSubmit" disabled>
                            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                            <span id="submitText">Simpan Presensi</span>
                        </button>
                    </div>
                </form>
                @elseif($statusPresensi === 'selesai')
                <div class="alert alert-success">
                    <h5>Presensi Selesai</h5>
                    <p class="mb-0">Anda sudah menyelesaikan presensi hari ini. Terima kasih!</p>
                </div>
                @endif
            </div>
        </div>
    </div>            
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
<script>
    // Konfigurasi Webcam
    Webcam.set({
        width: 400,
        height: 300,
        image_format: 'jpeg',
        jpeg_quality: 90,
        force_flash: false,
        flip_horiz: true,
        fps: 30
    });

    // Attach webcam ke elemen
    Webcam.attach('#my_camera');

    // Error handling untuk webcam
    Webcam.on('error', function(err) {
        document.getElementById('camera-error').style.display = 'block';
        console.error('Webcam error:', err);
    });

    // Auto-focus pada field pertama (jika ada)
    document.addEventListener('DOMContentLoaded', function() {
        // Cek apakah webcam berhasil di-attach
        setTimeout(function() {
            const cameraElement = document.getElementById('my_camera');
            if (cameraElement && cameraElement.children.length === 0) {
                document.getElementById('camera-error').style.display = 'block';
            }
        }, 1000);
    });

    // Konfigurasi GPS
    const TARGET_LAT = -7.485628943494862;
    const TARGET_LNG = 112.6527141877153;
    const ALLOWED_RADIUS = 30; // dalam meter
    let currentLocation = null;
    let isLocationValid = false;

    // Peta OpenStreetMap (Leaflet)
    let presensiMapObj = null;
    let userMarker = null;
    let targetMarker = null;

    function showMapAndHidePlaceholder() {
        const mapEl = document.getElementById('presensiMap');
        const placeholder = document.getElementById('mapPlaceholder');
        if (placeholder) placeholder.style.display = 'none';
        if (mapEl) {
            mapEl.style.display = 'block';
            mapEl.style.width = '100%';
        }
        // Penting: setelah container tampil, Leaflet harus hitung ulang ukuran agar tile tidak terpotong
        if (presensiMapObj) {
            setTimeout(function() {
                presensiMapObj.invalidateSize();
            }, 150);
        }
    }

    // Tampilkan peta dengan titik presensi saja (tanpa lokasi user) - dipanggil saat halaman load
    function initPresensiMapWithTargetOnly() {
        const mapEl = document.getElementById('presensiMap');
        if (!mapEl) return;
        if (presensiMapObj) return; // sudah ada

        showMapAndHidePlaceholder();

        presensiMapObj = L.map('presensiMap', { attributionControl: true }).setView([TARGET_LAT, TARGET_LNG], 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(presensiMapObj);

        targetMarker = L.marker([TARGET_LAT, TARGET_LNG], {
            icon: L.divIcon({
                className: 'presensi-target-marker',
                html: '<div style="background:#0d6efd;color:#fff;padding:4px 8px;border-radius:4px;font-size:11px;white-space:nowrap;">Titik Presensi (radius ' + ALLOWED_RADIUS + ' m)</div>',
                iconSize: [140, 24],
                iconAnchor: [70, 12]
            })
        }).addTo(presensiMapObj).bindPopup('Titik presensi. Berada dalam radius ' + ALLOWED_RADIUS + ' m untuk bisa absen.');

        setTimeout(function() {
            if (presensiMapObj) presensiMapObj.invalidateSize();
        }, 200);
    }

    function initOrUpdatePresensiMap(userLat, userLng) {
        const mapEl = document.getElementById('presensiMap');
        if (!mapEl) return;

        showMapAndHidePlaceholder();

        if (!presensiMapObj) {
            initPresensiMapWithTargetOnly();
        }

        if (!userMarker) {
            userMarker = L.marker([userLat, userLng], {
                icon: L.divIcon({
                    className: 'presensi-user-marker',
                    html: '<div style="background:#198754;color:#fff;padding:4px 8px;border-radius:4px;font-size:11px;white-space:nowrap;"><i class="fa fa-user"></i> Lokasi Saya</div>',
                    iconSize: [100, 24],
                    iconAnchor: [50, 12]
                })
            }).addTo(presensiMapObj).bindPopup('Posisi Anda sekarang');
        } else {
            userMarker.setLatLng([userLat, userLng]);
            userMarker.getPopup().setContent('Posisi Anda sekarang');
        }

        var bounds = L.latLngBounds([userLat, userLng], [TARGET_LAT, TARGET_LNG]);
        presensiMapObj.fitBounds(bounds.pad(0.35));
    }

    // Deteksi apakah di Android WebView
    function isAndroidWebView() {
        return typeof AndroidGPS !== 'undefined';
    }

    // Fungsi untuk menghitung jarak antara dua koordinat (Haversine formula)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3; // Radius bumi dalam meter
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

        return R * c; // Jarak dalam meter
    }

    // Fungsi untuk mendapatkan lokasi dari Android native (dengan deteksi fake GPS)
    function getLocationFromAndroid() {
        return new Promise((resolve, reject) => {
            if (!isAndroidWebView()) {
                reject('Not in Android WebView');
                return;
            }
            
            try {
                const locationData = JSON.parse(AndroidGPS.getLocation());
                
                if (locationData.success) {
                    resolve({
                        lat: locationData.latitude,
                        lng: locationData.longitude,
                        accuracy: locationData.accuracy || 0,
                        isMockLocation: locationData.is_mock_location || false
                    });
                } else {
                    reject(locationData.error || 'Failed to get location');
                }
            } catch (e) {
                reject('Error parsing location data: ' + e.message);
            }
        });
    }

    // Fungsi untuk mendapatkan lokasi GPS
    function getCurrentLocation() {
        const gpsStatus = document.getElementById('gpsStatus');
        const gpsStatusText = document.getElementById('gpsStatusText');
        
        if (!gpsStatus || !gpsStatusText) return; // Skip jika form tidak ada
        
        // Jika di Android WebView, gunakan native API dengan deteksi fake GPS
        if (isAndroidWebView()) {
            gpsStatus.className = 'alert alert-warning mb-3';
            gpsStatus.style.display = 'block';
            gpsStatusText.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Mendapatkan lokasi dari Android (dengan deteksi fake GPS)...';
            
            getLocationFromAndroid()
                .then(location => {
                    currentLocation = { lat: location.lat, lng: location.lng };
                    
                    // Validasi fake GPS (dari Android native detection)
                    if (location.isMockLocation) {
                        gpsStatus.className = 'alert alert-danger mb-3';
                        gpsStatusText.innerHTML = '<i class="fa fa-exclamation-triangle me-2"></i><strong>Fake GPS terdeteksi!</strong> Presensi tidak dapat dilakukan. Silakan matikan aplikasi fake GPS.';
                        isLocationValid = false;
                        updateSubmitButton();
                        return;
                    }
                    
                    // Validasi jarak
                    const distance = calculateDistance(location.lat, location.lng, TARGET_LAT, TARGET_LNG);
                    
                    if (distance <= ALLOWED_RADIUS) {
                        gpsStatus.className = 'alert alert-success mb-3';
                        gpsStatusText.innerHTML = `<i class="fa fa-check-circle me-2"></i>Lokasi valid (Android native). Jarak: ${Math.round(distance)} meter dari titik presensi.`;
                        isLocationValid = true;
                    } else {
                        gpsStatus.className = 'alert alert-danger mb-3';
                        gpsStatusText.innerHTML = `<i class="fa fa-exclamation-triangle me-2"></i>Anda berada di luar radius presensi. Jarak: ${Math.round(distance)} meter (maksimal ${ALLOWED_RADIUS} meter). Silakan mendekat ke titik presensi.`;
                        isLocationValid = false;
                    }
                    initOrUpdatePresensiMap(location.lat, location.lng);
                    updateSubmitButton();
                })
                .catch(error => {
                    gpsStatus.className = 'alert alert-danger mb-3';
                    gpsStatus.style.display = 'block';
                    gpsStatusText.innerHTML = `<i class="fa fa-exclamation-triangle me-2"></i>Error: ${error}`;
                    isLocationValid = false;
                    updateSubmitButton();
                });
            
            return;
        }
        
        // Fallback ke browser GPS (untuk browser biasa - tidak bisa deteksi fake GPS)
        if (!navigator.geolocation) {
            gpsStatus.className = 'alert alert-danger mb-3';
            gpsStatus.style.display = 'block';
            gpsStatusText.innerHTML = '<i class="fa fa-exclamation-triangle me-2"></i>Browser Anda tidak mendukung GPS. Silakan gunakan browser lain atau aplikasi Android.';
            isLocationValid = false;
            updateSubmitButton();
            return;
        }

        gpsStatus.className = 'alert alert-warning mb-3';
        gpsStatus.style.display = 'block';
        gpsStatusText.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Mendapatkan lokasi GPS...';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                currentLocation = { lat, lng };

                // Hitung jarak dari titik target
                const distance = calculateDistance(lat, lng, TARGET_LAT, TARGET_LNG);
                
                if (distance <= ALLOWED_RADIUS) {
                    gpsStatus.className = 'alert alert-success mb-3';
                    gpsStatusText.innerHTML = `<i class="fa fa-check-circle me-2"></i>Lokasi valid. Jarak: ${Math.round(distance)} meter dari titik presensi.`;
                    isLocationValid = true;
                } else {
                    gpsStatus.className = 'alert alert-danger mb-3';
                    gpsStatusText.innerHTML = `<i class="fa fa-exclamation-triangle me-2"></i>Anda berada di luar radius presensi. Jarak: ${Math.round(distance)} meter (maksimal ${ALLOWED_RADIUS} meter). Silakan mendekat ke titik presensi.`;
                    isLocationValid = false;
                }
                initOrUpdatePresensiMap(lat, lng);
                updateSubmitButton();
            },
            function(error) {
                let errorMsg = 'Gagal mendapatkan lokasi GPS. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMsg += 'Izin akses lokasi ditolak. Silakan izinkan akses lokasi di pengaturan browser.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg += 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        errorMsg += 'Waktu permintaan lokasi habis.';
                        break;
                    default:
                        errorMsg += 'Terjadi kesalahan tidak diketahui.';
                        break;
                }
                
                gpsStatus.className = 'alert alert-danger mb-3';
                gpsStatus.style.display = 'block';
                gpsStatusText.innerHTML = `<i class="fa fa-exclamation-triangle me-2"></i>${errorMsg}`;
                isLocationValid = false;
                updateSubmitButton();
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Fungsi untuk update status tombol submit
    function updateSubmitButton() {
        const submitBtn = document.getElementById('btnSubmit');
        if (!submitBtn) return; // Skip jika form tidak ada
        
        const imageInput = document.getElementById('imageInput');
        const hasImage = imageInput && imageInput.value && imageInput.value.trim() !== '';
        
        if (isLocationValid && hasImage) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    // Peta ditampilkan dulu dengan titik presensi; lokasi user ditambah jika GPS berhasil
    document.addEventListener('DOMContentLoaded', function() {
        initPresensiMapWithTargetOnly();
        getCurrentLocation();

        setInterval(getCurrentLocation, 30000);
    });

    // Fungsi ambil foto
    function take_snapshot() {
        Webcam.snap(function(data_uri) {
            document.getElementById('imageInput').value = data_uri;
            document.getElementById('previewImage').src = data_uri;
            document.getElementById('results').style.display = 'block';
            document.getElementById('btnTakePhoto').style.display = 'none';
            document.getElementById('btnReset').style.display = 'inline-block';
            updateSubmitButton(); // Update status tombol submit
            document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    // Fungsi reset kamera
    function reset_camera() {
        document.getElementById('imageInput').value = '';
        document.getElementById('results').style.display = 'none';
        document.getElementById('btnTakePhoto').style.display = 'inline-block';
        document.getElementById('btnReset').style.display = 'none';
        updateSubmitButton(); // Update status tombol submit
    }


    // Validasi form sebelum submit
    const presensiForm = document.getElementById('presensiForm');
    if (presensiForm) {
        presensiForm.addEventListener('submit', function(e) {
            const imageInput = document.getElementById('imageInput').value;
            
            if (!imageInput || imageInput.trim() === '') {
                e.preventDefault();
                alert('Silakan ambil foto terlebih dahulu!');
                return false;
            }

            if (!isLocationValid) {
                e.preventDefault();
                alert('Lokasi GPS tidak valid. Pastikan Anda berada dalam radius 30 meter dari titik presensi.');
                getCurrentLocation(); // Coba dapatkan lokasi lagi
                return false;
            }

            // Tampilkan loading indicator
            const submitBtn = document.getElementById('btnSubmit');
            const submitSpinner = document.getElementById('submitSpinner');
            const submitText = document.getElementById('submitText');
            
            submitBtn.disabled = true;
            submitSpinner.classList.remove('d-none');
            submitText.textContent = 'Memproses...';
        });
    }

    // Auto-hide alerts setelah 5 detik
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            if (!alert.classList.contains('alert-info') && !alert.classList.contains('alert-warning') && !alert.classList.contains('alert-danger') && !alert.classList.contains('alert-success')) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }
        });
    }, 5000);

    // Update jam real-time setiap detik
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    // Update jam setiap detik
    setInterval(updateTime, 1000);
    updateTime(); // Panggil sekali untuk langsung update
</script>
@endsection
