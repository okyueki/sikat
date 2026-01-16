@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Jadwal Pegawai')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Kelola Jadwal Presensi</h3>
                        <ul class="nav nav-tabs l_tinynav1">
                            <li class="active">
                                <a href="{{ route('jadwal.index') }}" role="tab">Jadwal</a>
                            </li>
                            <li class="">
                                <a href="#">Tambah</a>
                            </li>
                        </ul>
                    </div>
            
                    <div class="panel-body">
                        <div class="row clearfix">
                            <div class="col col-md-6">
                                <h3 style="margin-top:5px;margin-bottom:15px;">Jumlah: {{ $jadwalPegawai->total() }} || Bulan: {{ strtoupper(date('M', mktime(0, 0, 0, $bulan, 1))) }}</h3>
                            </div>
            
                            <div class="row mb-4">
                                <form method="GET" action="{{ route('jadwal.index') }}" class="row mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Bulan</label>
                                    <select name="b" id="bulan" class="form-control">
                                        <option value="">Pilih Bulan</option>
                                        @foreach(['01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL', '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS', '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'] as $key => $value)
                                            <option value="{{ $key }}" {{ $key == $bulan ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            
                                <div class="col-md-4">
                                    <label class="form-label">Tahun</label>
                                    <select name="y" id="tahun" class="form-control">
                                        @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                            <option value="{{ $year }}" {{ $year == $tahun ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="fa fa-search"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="alert alert-info mb-3">
                            <i class="fa fa-info-circle"></i> <strong>Info:</strong> Halaman ini menampilkan jadwal presensi Anda sendiri.
                        </div>
                        </div>
            
                        @foreach ($jadwalPegawai as $jadwal)
                        <div class="mb-4">
                            <div class="mb-3">
                                <h5 class="mb-0">
                                    <i class="fa fa-user me-2"></i>{{ $jadwal->pegawai->nama }}
                                </h5>
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>Klik pada tanggal untuk mengedit shift
                                </small>
                            </div>
                            
                            <!-- Timeline Grid -->
                            <div class="row g-3" id="jadwal-timeline">
                                @for ($i = 1; $i <= 31; $i++)
                                    @php
                                        $hari = 'h'.$i;
                                        $shift = $jadwal->$hari;
                                        
                                        // Cek apakah tanggal valid untuk bulan tersebut
                                        $daysInMonth = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;
                                        if ($i > $daysInMonth) {
                                            continue;
                                        }
                                        
                                        try {
                                            $tanggal = \Carbon\Carbon::create($tahun, $bulan, $i);
                                            $isWeekend = $tanggal->isWeekend();
                                            $isToday = $tanggal->isToday();
                                            $isPast = $tanggal->isPast() && !$tanggal->isToday();
                                            
                                            // Format hari dalam bahasa Indonesia
                                            $hariNama = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                            $hariIndex = $tanggal->dayOfWeek;
                                            $namaHari = $hariNama[$hariIndex];
                                        } catch (\Exception $e) {
                                            continue;
                                        }
                                        
                                        // Tentukan warna badge berdasarkan shift
                                        $badgeClass = 'bg-secondary';
                                        if (!empty($shift)) {
                                            $shiftLower = strtolower($shift);
                                            if (strpos($shiftLower, 'pagi') !== false) {
                                                $badgeClass = 'bg-primary';
                                            } elseif (strpos($shiftLower, 'siang') !== false) {
                                                $badgeClass = 'bg-warning';
                                            } elseif (strpos($shiftLower, 'malam') !== false || strpos($shiftLower, 'sore') !== false) {
                                                $badgeClass = 'bg-dark';
                                            } elseif (strpos($shiftLower, 'libur') !== false || strpos($shiftLower, 'off') !== false) {
                                                $badgeClass = 'bg-danger';
                                            } else {
                                                $badgeClass = 'bg-info';
                                            }
                                        }
                                    @endphp
                                    
                                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                        <div class="card h-100 jadwal-card {{ $isToday ? 'border-primary border-2' : '' }} {{ $isPast ? 'opacity-75' : '' }} {{ $isWeekend ? 'weekend-card' : '' }}" 
                                             style="transition: all 0.2s ease; cursor: pointer;"
                                             data-jadwal-id="{{ $jadwal->id }}"
                                             data-tanggal="{{ $i }}"
                                             data-bulan="{{ $bulan }}"
                                             data-tahun="{{ $tahun }}"
                                             data-shift="{{ $shift }}"
                                             onclick="openEditModal({{ $jadwal->id }}, {{ $i }}, '{{ $bulan }}', '{{ $tahun }}')">
                                            <div class="card-body p-2 text-center {{ $isWeekend ? 'bg-light' : '' }}">
                                                <div class="d-flex flex-column align-items-center">
                                                    <!-- Tanggal -->
                                                    <div class="mb-1">
                                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                            {{ $namaHari }}
                                                        </small>
                                                        <strong class="d-block" style="font-size: 1.1rem; line-height: 1.2;">
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                                        </strong>
                                                    </div>
                                                    
                                                    <!-- Shift Badge -->
                                                    <div class="w-100 mt-1">
                                                        @if(!empty($shift))
                                                            <span class="badge {{ $badgeClass }} text-white w-100" style="font-size: 0.7rem; padding: 4px 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $shift }}">
                                                                {{ $shift }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-muted w-100" style="font-size: 0.7rem; padding: 4px 8px;">
                                                                -
                                                            </span>
                                                        @endif
                                                    </div>
                                                    
                                                    @if($isToday)
                                                    <small class="text-primary mt-1" style="font-size: 0.65rem;">
                                                        <i class="fa fa-circle"></i> Hari Ini
                                                    </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        @endforeach
                        
                        @if($jadwalPegawai->isEmpty())
                        <div class="text-center py-5">
                            <i class="fa fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada jadwal untuk bulan dan tahun yang dipilih.</p>
                        </div>
                        @endif

            
                        <!-- Pagination -->
                       {{ $jadwalPegawai->withQueryString()->links('vendor.pagination.tabler') }}
                    </div>
                </div>
            </div>
        </div>    
    </div>
</div>

<!-- Modal Edit Jadwal Per Tanggal -->
<div class="modal fade" id="editJadwalModal" tabindex="-1" aria-labelledby="editJadwalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editJadwalModalLabel">
                    <i class="fa fa-calendar me-2"></i>Edit Shift
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data...</p>
                </div>
                <div id="modalContent" style="display: none;">
                    <form id="editJadwalForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Pegawai</label>
                            <input type="text" class="form-control" id="modalNamaPegawai" readonly>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal</label>
                                <input type="text" class="form-control" id="modalTanggal" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hari</label>
                                <input type="text" class="form-control" id="modalHari" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Shift <span class="text-danger">*</span></label>
                            <select class="form-control" id="modalShift" name="shift">
                                <option value="">- Pilih Shift -</option>
                                <!-- Options akan diisi oleh JavaScript -->
                            </select>
                            <small class="text-muted">Pilih shift untuk tanggal ini</small>
                        </div>
                        
                        <input type="hidden" id="modalJadwalId" name="jadwal_id">
                        <input type="hidden" id="modalHariIndex" name="hari_index">
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveJadwalBtn" onclick="saveJadwal()">
                    <i class="fa fa-save me-2"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.jadwal-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.jadwal-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #6c757d;
}

.jadwal-card.border-primary {
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .jadwal-card .card-body {
        padding: 0.5rem !important;
    }
    
    .jadwal-card strong {
        font-size: 0.95rem !important;
    }
    
    .jadwal-card .badge {
        font-size: 0.6rem !important;
        padding: 3px 6px !important;
        line-height: 1.2;
    }
    
    .jadwal-card small {
        font-size: 0.6rem !important;
    }
    
    #jadwal-timeline {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    
    #jadwal-timeline > div {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}

/* Weekend styling */
.weekend-card .card-body {
    background-color: #f8f9fa !important;
}
</style>

<script>
let currentJadwalId = null;
let currentBulan = null;
let currentTahun = null;
let currentHari = null;
let shiftsData = [];

// Fungsi untuk membuka modal edit jadwal per tanggal
function openEditModal(jadwalId, hari, bulan, tahun) {
    currentJadwalId = jadwalId;
    currentBulan = bulan;
    currentTahun = tahun;
    currentHari = hari;
    
    // Reset modal
    const modalLoading = document.getElementById('modalLoading');
    const modalContent = document.getElementById('modalContent');
    const saveBtn = document.getElementById('saveJadwalBtn');
    
    modalLoading.style.display = 'block';
    modalContent.style.display = 'none';
    saveBtn.disabled = true;
    
    // Reset loading content
    modalLoading.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Memuat data...</p>
    `;
    
    // Tampilkan modal
    const modal = new bootstrap.Modal(document.getElementById('editJadwalModal'));
    modal.show();
    
    // Load data jadwal via AJAX
    loadJadwalData(jadwalId, hari, bulan, tahun);
}

// Fungsi untuk load data jadwal
function loadJadwalData(jadwalId, hari, bulan, tahun) {
    fetch(`{{ url('/') }}/jadwal/${jadwalId}/data/${bulan}/${tahun}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Gagal memuat data jadwal');
        }
        return response.json();
    })
    .then(data => {
        // Simpan data shifts
        shiftsData = data.shifts;
        
        // Get current shift untuk tanggal yang dipilih
        const currentShift = data.jadwalHari[hari] || '';
        
        // Hitung nama hari
        const bulanInt = parseInt(bulan);
        const tahunInt = parseInt(tahun);
        const tanggal = new Date(tahunInt, bulanInt - 1, hari);
        const hariNama = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const namaHari = hariNama[tanggal.getDay()];
        const namaBulan = getBulanName(String(bulan).padStart(2, '0'));
        
        // Update modal content
        document.getElementById('modalNamaPegawai').value = data.pegawai.nama;
        document.getElementById('modalTanggal').value = `${String(hari).padStart(2, '0')} ${namaBulan} ${tahun}`;
        document.getElementById('modalHari').value = namaHari;
        document.getElementById('modalJadwalId').value = jadwalId;
        document.getElementById('modalHariIndex').value = hari;
        
        // Render shift dropdown
        const shiftSelect = document.getElementById('modalShift');
        shiftSelect.innerHTML = '<option value="">- Pilih Shift -</option>';
        
        shiftsData.forEach(shift => {
            const option = document.createElement('option');
            option.value = shift.shift;
            option.textContent = `${shift.shift} (${shift.jam_masuk} - ${shift.jam_pulang})`;
            if (currentShift === shift.shift) {
                option.selected = true;
            }
            shiftSelect.appendChild(option);
        });
        
        // Tampilkan content
        document.getElementById('modalLoading').style.display = 'none';
        document.getElementById('modalContent').style.display = 'block';
        document.getElementById('saveJadwalBtn').disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalLoading').innerHTML = `
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle me-2"></i>Gagal memuat data jadwal. Silakan coba lagi.
            </div>
        `;
    });
}

// Fungsi untuk menyimpan jadwal (hanya 1 tanggal)
function saveJadwal() {
    if (!currentJadwalId || !currentBulan || !currentTahun || !currentHari) {
        alert('Data tidak valid');
        return;
    }
    
    const shiftValue = document.getElementById('modalShift').value;
    
    // Disable button
    const saveBtn = document.getElementById('saveJadwalBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    
    // Load full jadwal data dulu untuk mendapatkan semua nilai h1-h31
    fetch(`{{ url('/') }}/jadwal/${currentJadwalId}/data/${currentBulan}/${currentTahun}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        // Prepare form data dengan semua h1-h31, tapi update hanya h{currentHari}
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PUT');
        
        // Set semua nilai h1-h31 dari data existing
        for (let i = 1; i <= 31; i++) {
            if (i == currentHari) {
                // Update hanya tanggal yang dipilih
                formData.append(`h${i}`, shiftValue || '');
            } else {
                // Pertahankan nilai yang sudah ada
                formData.append(`h${i}`, data.jadwalHari[i] || '');
            }
        }
        
        // Send AJAX request
        return fetch(`{{ url('/') }}/jadwal/${currentJadwalId}/update/${currentBulan}/${currentTahun}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        });
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else if (response.redirected) {
            window.location.href = response.url;
            return;
        } else {
            return response.json().then(err => Promise.reject(err));
        }
    })
    .then(data => {
        if (data && data.success) {
            // Tutup modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editJadwalModal'));
            modal.hide();
            
            // Reload page to show updated data
            window.location.reload();
        } else {
            throw new Error(data.message || 'Gagal menyimpan jadwal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal menyimpan jadwal. Silakan coba lagi.');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fa fa-save me-2"></i>Simpan';
    });
}

// Fungsi helper untuk nama bulan
function getBulanName(bulan) {
    const bulanNames = {
        '01': 'JANUARI', '02': 'FEBRUARI', '03': 'MARET', '04': 'APRIL',
        '05': 'MEI', '06': 'JUNI', '07': 'JULI', '08': 'AGUSTUS',
        '09': 'SEPTEMBER', '10': 'OKTOBER', '11': 'NOVEMBER', '12': 'DESEMBER'
    };
    return bulanNames[bulan] || bulan;
}

// Reset modal saat ditutup
document.getElementById('editJadwalModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalContent').style.display = 'none';
    document.getElementById('modalShift').innerHTML = '<option value="">- Pilih Shift -</option>';
    currentJadwalId = null;
    currentBulan = null;
    currentTahun = null;
    currentHari = null;
});
</script>
@endsection
