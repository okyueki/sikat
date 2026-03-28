@extends('layouts.pages-layouts')

@section('pageTitle', 'Tambah Jadwal Budaya Kerja')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Jadwal Budaya Kerja</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('jadwalbudayakerja.store') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="nik" class="form-label">Pilih Petugas / Dokter</label>
                                <select class="form-control" id="nik" name="nik" required>
                                    <option value="" data-dept="" data-hp="">-- Pilih --</option>
                                    
                                    <optgroup label="Petugas">
                                        @foreach ($petugas as $p)
                                            <option value="{{ $p->nip }}" 
                                                    data-dept="{{ $p->pegawai?->departemen_unit?->nama ?? $p->pegawai?->departemen ?? '-' }}" 
                                                    data-hp="{{ $p->no_telp ?? '-' }}"
                                                    data-jenis="petugas">
                                                {{ $p->nama }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    
                                    <optgroup label="Dokter">
                                        @foreach ($dokter as $d)
                                            <option value="{{ $d->kd_dokter }}" 
                                                    data-dept="{{ $d->pegawai?->departemen_unit?->nama ?? $d->pegawai?->departemen ?? '-' }}" 
                                                    data-hp="{{ $d->no_telp ?? '-' }}"
                                                    data-jenis="dokter">
                                                {{ $d->nm_dokter }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Info Panel -->
                            <div id="info-pegawai" class="alert alert-info d-none mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fa fa-building"></i> Jabatan/Spesialis:</strong>
                                        <span id="info-dept">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fa fa-phone"></i> No. HP:</strong>
                                        <span id="info-hp">-</span>
                                    </div>
                                </div>
                            </div>


                            <div class="mb-3">
                                <label for="tanggal_bertugas" class="form-label">Tanggal Bertugas</label>
                                <input type="date" class="form-control" id="tanggal_bertugas" name="tanggal_bertugas" 
                                    value="{{ $tanggal_bertugas }}" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="shift" class="form-label">Shift</label>
                                <select class="form-control" id="shift" name="shift" required>
                                    <option value="Pagi">Pagi</option>
                                    <option value="Sore">Sore</option>
                                </select>
                            </div>

                            <!-- Dept & No HP Readonly Fields -->
                            <div class="mb-3">
                                <label for="dept" class="form-label">Departemen / Jabatan</label>
                                <input type="text" class="form-control" id="dept" readonly placeholder="Pilih pegawai terlebih dahulu">
                            </div>

                            <div class="mb-3">
                                <label for="no_hp" class="form-label">Nomor HP</label>
                                <input type="text" class="form-control" id="no_hp" readonly placeholder="Pilih pegawai terlebih dahulu">
                            </div>

                            <div class="mb-3 text-end">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
   document.addEventListener('DOMContentLoaded', function () {
        const pegawaiMeta = @json($pegawaiMeta);

        if (typeof flatpickr !== 'undefined') {
            flatpickr('#tanggal_bertugas', { dateFormat: "Y-m-d" });
        }

        // Initialize Choices for shift
        const shiftSelect = document.getElementById('shift');
        if (shiftSelect && typeof Choices !== 'undefined') {
            new Choices(shiftSelect, {
                searchEnabled: true,
                position: 'auto',
                shouldSort: false,
                allowHTML: true,
            });
        }

        // Initialize Choices for nik select
        const nikSelect = document.getElementById('nik');
        if (nikSelect && typeof Choices !== 'undefined') {
            new Choices(nikSelect, {
                searchEnabled: true,
                position: 'auto',
                shouldSort: false,
                allowHTML: true
            });
        }

        // Function to update info panel and readonly fields
        function updateInfoPanel() {
            const selectedValue = nikSelect.value;
            if (!selectedValue) {
                document.getElementById('info-pegawai').classList.add('d-none');
                document.getElementById('dept').value = '';
                document.getElementById('no_hp').value = '';
                return;
            }

            const selectedMeta = pegawaiMeta[selectedValue] || {};
            const dept = selectedMeta.dept || '-';
            const hp = selectedMeta.hp || '-';

            // Update info panel
            if (dept !== '-' || hp !== '-') {
                document.getElementById('info-dept').textContent = dept;
                document.getElementById('info-hp').textContent = hp;
                document.getElementById('info-pegawai').classList.remove('d-none');
            } else {
                document.getElementById('info-pegawai').classList.add('d-none');
            }
            
            // Update readonly input fields
            document.getElementById('dept').value = dept !== '-' ? dept : '';
            document.getElementById('no_hp').value = hp !== '-' ? hp : '';
        }

        // Listen to change event
        nikSelect.addEventListener('change', updateInfoPanel);
        nikSelect.addEventListener('input', updateInfoPanel);
        nikSelect.addEventListener('choice', updateInfoPanel);
        nikSelect.addEventListener('addItem', updateInfoPanel);

        // Also check periodically for changes (fallback)
        let lastValue = nikSelect.value;
        setInterval(function() {
            if (nikSelect.value !== lastValue) {
                lastValue = nikSelect.value;
                updateInfoPanel();
            }
        }, 500);

        // Initial check
        updateInfoPanel();
    });
</script>

@endsection
