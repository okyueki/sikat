@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Absensi Agenda')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Rekap Absensi Agenda</h4>
                    <div>
                        <button type="button" class="btn btn-danger" id="btnExportPDF" style="display: none;">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        @if(request('agenda_id'))
                            <a href="{{ route('acara_show', request('agenda_id')) }}" class="btn btn-info">
                                <i class="fas fa-arrow-left"></i> Kembali ke Detail Agenda
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Filter by Date -->
                <form id="filterForm" class="mb-4">
                    <div class="card border">
                        <div class="card-body">
                            <h6 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filter & Pencarian</h6>
                            
                            <div class="row g-3">
                                <!-- Filter Rentang Tanggal -->
                                <div class="col-md-3">
                                    <label for="filterTanggalStart" class="form-label"><strong>Tanggal Mulai</strong></label>
                                    <input type="date" class="form-control" id="filterTanggalStart" name="filterTanggalStart">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Dari tanggal</small>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="filterTanggalEnd" class="form-label"><strong>Tanggal Akhir</strong></label>
                                    <input type="date" class="form-control" id="filterTanggalEnd" name="filterTanggalEnd">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Sampai tanggal</small>
                                </div>
                                
                                <!-- Pilih Agenda (akan muncul setelah pilih rentang tanggal) -->
                                <div class="col-md-6" id="agendaFilterContainer" style="display: none;">
                                    <label for="agenda_id" class="form-label"><strong>Pilih Agenda</strong></label>
                                    <select class="form-control" id="agenda_id" name="agenda_id">
                                        <option value="">-- Pilih Agenda --</option>
                                        @foreach ($agendas as $agenda)
                                            @php
                                                $tanggalMulai = \Carbon\Carbon::parse($agenda->mulai)->format('d M Y H:i');
                                                $pimpinanNama = $agenda->pimpinan ? $agenda->pimpinan->nama : '-';
                                                $tanggalOnly = \Carbon\Carbon::parse($agenda->mulai)->format('Y-m-d');
                                            @endphp
                                            <option value="{{ $agenda->id }}" 
                                                data-tanggal="{{ $tanggalOnly }}"
                                                data-pimpinan="{{ $pimpinanNama }}"
                                                data-tanggal-full="{{ $tanggalMulai }}"
                                                {{ request('agenda_id') == $agenda->id ? 'selected' : '' }}>
                                                {{ $agenda->judul }} | {{ $pimpinanNama }} | {{ $tanggalMulai }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Pilih agenda untuk melihat rekap absensi</small>
                                </div>
                                
                                <!-- Pencarian Pegawai (hanya muncul setelah pilih agenda) -->
                                <div class="col-md-12" id="searchPegawaiContainer" style="display: none;">
                                    <label for="searchInput" class="form-label"><strong>Cari Pegawai</strong></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Cari nama, NIK, jabatan, atau departemen...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Hapus pencarian">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Statistik Cards -->
                <div id="rekapContainer" class="mb-4"></div>

                <!-- Progress Bar -->
                <div id="progressContainer" class="mb-4" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">Tingkat Kehadiran</h6>
                            <div class="progress" style="height: 30px;">
                                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%">
                                    <span id="progressText" class="fw-bold">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" id="absensiTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>Semua (<span id="count-all">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="hadir-tab" data-bs-toggle="tab" data-bs-target="#hadir" type="button" role="tab">
                            <i class="fas fa-check-circle me-2 text-success"></i>Hadir (<span id="count-hadir">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ijin-tab" data-bs-toggle="tab" data-bs-target="#ijin" type="button" role="tab">
                            <i class="fas fa-file-alt me-2 text-info"></i>Ijin (<span id="count-ijin">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cuti-tab" data-bs-toggle="tab" data-bs-target="#cuti" type="button" role="tab">
                            <i class="fas fa-calendar-alt me-2 text-primary"></i>Cuti (<span id="count-cuti">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sakit-tab" data-bs-toggle="tab" data-bs-target="#sakit" type="button" role="tab">
                            <i class="fas fa-heartbeat me-2 text-warning"></i>Sakit (<span id="count-sakit">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="berhalangan-tab" data-bs-toggle="tab" data-bs-target="#berhalangan" type="button" role="tab">
                            <i class="fas fa-exclamation-triangle me-2 text-secondary"></i>Berhalangan (<span id="count-berhalangan">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tidak-hadir-tab" data-bs-toggle="tab" data-bs-target="#tidak-hadir" type="button" role="tab">
                            <i class="fas fa-times-circle me-2 text-danger"></i>Tidak Hadir (<span id="count-tidak-hadir">0</span>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="absensiTabContent">
                    <!-- Tab Semua -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableAll" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="25%">Nama</th>
                                        <th width="20%">Jabatan</th>
                                        <th width="15%">Departemen</th>
                                        <th width="12%">Waktu Kehadiran</th>
                                        <th width="15%">Status</th>
                                        <th width="8%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Hadir -->
                    <div class="tab-pane fade" id="hadir" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableHadir" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="25%">Nama</th>
                                        <th width="20%">Jabatan</th>
                                        <th width="15%">Departemen</th>
                                        <th width="20%">Waktu Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Ijin -->
                    <div class="tab-pane fade" id="ijin" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableIjin" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="25%">Nama</th>
                                        <th width="20%">Jabatan</th>
                                        <th width="15%">Departemen</th>
                                        <th width="20%">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Cuti -->
                    <div class="tab-pane fade" id="cuti" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableCuti" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="25%">Nama</th>
                                        <th width="20%">Jabatan</th>
                                        <th width="15%">Departemen</th>
                                        <th width="20%">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Sakit -->
                    <div class="tab-pane fade" id="sakit" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableSakit" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="25%">Nama</th>
                                        <th width="20%">Jabatan</th>
                                        <th width="15%">Departemen</th>
                                        <th width="20%">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Berhalangan -->
                    <div class="tab-pane fade" id="berhalangan" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableBerhalangan" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="25%">Nama</th>
                                        <th width="20%">Jabatan</th>
                                        <th width="15%">Departemen</th>
                                        <th width="20%">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Tidak Hadir -->
                    <div class="tab-pane fade" id="tidak-hadir" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rekapTableTidakHadir" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">NIK</th>
                                        <th width="30%">Nama</th>
                                        <th width="25%">Jabatan</th>
                                        <th width="25%">Departemen</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Filter agenda berdasarkan rentang tanggal
    function filterAgendaByRentangTanggal() {
        const tanggalStart = $('#filterTanggalStart').val();
        const tanggalEnd = $('#filterTanggalEnd').val();
        const agendaSelect = $('#agenda_id');
        
        // Validasi: tanggal akhir harus >= tanggal mulai
        if (tanggalStart && tanggalEnd && tanggalEnd < tanggalStart) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai!'
            });
            $('#filterTanggalEnd').val('');
            return;
        }
        
        if (tanggalStart || tanggalEnd) {
            // Tampilkan container agenda
            $('#agendaFilterContainer').show();
            
            // Simpan semua options terlebih dahulu (kecuali option placeholder)
            const allOptions = agendaSelect.find('option[value!=""]').clone();
            
            // Clear select dan tambahkan placeholder sekali saja
            agendaSelect.empty();
            agendaSelect.append('<option value="">-- Pilih Agenda --</option>');
            
            // Filter dan tambahkan options yang sesuai rentang tanggal
            allOptions.each(function() {
                const $option = $(this);
                const optionTanggal = $option.data('tanggal');
                
                if (optionTanggal) {
                    let include = true;
                    
                    // Cek apakah tanggal agenda dalam rentang
                    if (tanggalStart && optionTanggal < tanggalStart) {
                        include = false;
                    }
                    if (tanggalEnd && optionTanggal > tanggalEnd) {
                        include = false;
                    }
                    
                    if (include) {
                        agendaSelect.append($option);
                    }
                }
            });
            
            // Reset pilihan jika yang dipilih tidak sesuai rentang
            const selectedValue = agendaSelect.val();
            if (selectedValue) {
                const selectedOption = agendaSelect.find('option[value="' + selectedValue + '"]');
                if (selectedOption.length === 0 || selectedOption.val() === '') {
                    agendaSelect.val('');
                }
            }
        } else {
            // Sembunyikan container agenda dan reset
            $('#agendaFilterContainer').hide();
            $('#searchPegawaiContainer').hide();
            agendaSelect.val('');
            allData = [];
            filteredData = [];
            updateTables();
            $('#rekapContainer').html('');
            $('#progressContainer').hide();
        }
    }
    
    // Handle change rentang tanggal
    $('#filterTanggalStart, #filterTanggalEnd').on('change', function() {
        filterAgendaByRentangTanggal();
    });
    
    // Handle change agenda
    $('#agenda_id').on('change', function() {
        const agendaId = $(this).val();
        if (agendaId) {
            $('#searchPegawaiContainer').show();
            $('#btnExportPDF').show();
            loadData();
        } else {
            $('#searchPegawaiContainer').hide();
            $('#btnExportPDF').hide();
            allData = [];
            filteredData = [];
            updateTables();
            $('#rekapContainer').html('');
            $('#progressContainer').hide();
        }
    });
    
    // Handle export PDF
    $('#btnExportPDF').on('click', function() {
        const agendaId = $('#agenda_id').val();
        if (!agendaId) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Pilih agenda terlebih dahulu!'
            });
            return;
        }
        
        // Redirect ke route export PDF
        window.open('/absensi-agenda/export-pdf?agenda_id=' + agendaId, '_blank');
    });
    
    let allData = [];
    let filteredData = [];
    let searchTimeout = null;
    
    // Debounce function untuk pencarian
    function debounce(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(searchTimeout);
                func(...args);
            };
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(later, wait);
        };
    }
    
    // Function to filter data berdasarkan pencarian
    function filterData() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        
        if (!searchTerm) {
            filteredData = allData;
        } else {
            filteredData = allData.filter(item => {
                return item.nama.toLowerCase().includes(searchTerm) ||
                       item.nik.toLowerCase().includes(searchTerm) ||
                       (item.jabatan && item.jabatan.toLowerCase().includes(searchTerm)) ||
                       (item.departemen && item.departemen.toLowerCase().includes(searchTerm));
            });
        }
        
        updateTables();
    }
    
    // Debounced filter function
    const debouncedFilter = debounce(filterData, 300);
    
    // Function to get status badge HTML
    function getStatusBadge(status, alasan) {
        const badges = {
            'hadir': '<span class="badge bg-success"><i class="fas fa-check"></i> Hadir</span>',
            'ijin': '<span class="badge bg-info"><i class="fas fa-file-alt"></i> Ijin</span>',
            'cuti': '<span class="badge bg-primary"><i class="fas fa-calendar-alt"></i> Cuti</span>',
            'sakit': '<span class="badge bg-warning"><i class="fas fa-heartbeat"></i> Sakit</span>',
            'berhalangan': '<span class="badge bg-secondary"><i class="fas fa-exclamation-triangle"></i> Berhalangan</span>',
            'tidak_hadir': '<span class="badge bg-danger"><i class="fas fa-times"></i> Tidak Hadir</span>'
        };
        let badge = badges[status] || badges['tidak_hadir'];
        if (alasan) {
            badge += ' <small class="text-muted d-block mt-1" title="' + alasan + '"><i class="fas fa-info-circle"></i> ' + (alasan.length > 50 ? alasan.substring(0, 50) + '...' : alasan) + '</small>';
        }
        return badge;
    }
    
    // Function to update all tables
    function updateTables() {
        const hadirData = filteredData.filter(item => item.status === 'hadir');
        const ijinData = filteredData.filter(item => item.status === 'ijin');
        const cutiData = filteredData.filter(item => item.status === 'cuti');
        const sakitData = filteredData.filter(item => item.status === 'sakit');
        const berhalanganData = filteredData.filter(item => item.status === 'berhalangan');
        const tidakHadirData = filteredData.filter(item => item.status === 'tidak_hadir');
        
        // Update counts
        $('#count-all').text(filteredData.length);
        $('#count-hadir').text(hadirData.length);
        $('#count-ijin').text(ijinData.length);
        $('#count-cuti').text(cutiData.length);
        $('#count-sakit').text(sakitData.length);
        $('#count-berhalangan').text(berhalanganData.length);
        $('#count-tidak-hadir').text(tidakHadirData.length);
        
        // Update tables
        updateTable('rekapTableAll', filteredData);
        updateTable('rekapTableHadir', hadirData);
        updateTable('rekapTableIjin', ijinData);
        updateTable('rekapTableCuti', cutiData);
        updateTable('rekapTableSakit', sakitData);
        updateTable('rekapTableBerhalangan', berhalanganData);
        updateTable('rekapTableTidakHadir', tidakHadirData);
        
        // Update statistics
        updateStatistics();
    }
    
    // Function to highlight search term in text
    function highlightText(text, searchTerm) {
        if (!searchTerm || !text) return text;
        const regex = new RegExp('(' + searchTerm + ')', 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }
    
    // Function to update single table
    function updateTable(tableId, data) {
        const tbody = $('#' + tableId + ' tbody');
        tbody.empty();
        
        if (data.length === 0) {
            const colSpan = tableId === 'rekapTableAll' ? 8 : (tableId === 'rekapTableTidakHadir' ? 6 : 7);
            tbody.append('<tr><td colspan="' + colSpan + '" class="text-center text-muted py-4"><i class="fas fa-search me-2"></i>Tidak ada data yang sesuai dengan filter</td></tr>');
            return;
        }
        
        const searchTerm = $('#searchInput').val().toLowerCase();
        
        data.forEach((item, index) => {
            let row = '<tr>';
            row += '<td>' + (index + 1) + '</td>';
            row += '<td>' + (searchTerm && item.nik.toLowerCase().includes(searchTerm) ? highlightText(item.nik, searchTerm) : item.nik) + '</td>';
            row += '<td><strong>' + (searchTerm && item.nama.toLowerCase().includes(searchTerm) ? highlightText(item.nama, searchTerm) : item.nama) + '</strong></td>';
            row += '<td>' + (searchTerm && item.jabatan && item.jabatan.toLowerCase().includes(searchTerm) ? highlightText(item.jabatan, searchTerm) : item.jabatan) + '</td>';
            row += '<td>' + (searchTerm && item.departemen && item.departemen.toLowerCase().includes(searchTerm) ? highlightText(item.departemen, searchTerm) : item.departemen) + '</td>';
            
            if (tableId === 'rekapTableAll') {
                row += '<td>' + (item.waktu_kehadiran !== '-' ? '<span class="text-success"><i class="fas fa-clock me-1"></i>' + item.waktu_kehadiran + '</span>' : '<span class="text-muted">-</span>') + '</td>';
                row += '<td class="text-center">' + getStatusBadge(item.status, item.alasan) + '</td>';
                row += '<td class="text-center">';
                if (item.can_edit) {
                    const agendaId = $('#agenda_id').val();
                    const alasanEscaped = item.alasan ? item.alasan.replace(/'/g, "\\'").replace(/\n/g, ' ') : '';
                    row += '<button class="btn btn-sm btn-primary" onclick="editStatus(' + 
                        (item.id_absensi ? item.id_absensi : 'null') + ', \'' + item.nik + '\', \'' + 
                        item.nama.replace(/'/g, "\\'") + '\', \'' + (item.status || 'tidak_hadir') + '\', ' + 
                        (alasanEscaped ? '\'' + alasanEscaped + '\'' : 'null') + 
                        ', ' + (agendaId ? agendaId : 'null') + ')">' +
                        '<i class="fas fa-edit"></i> Edit</button>';
                } else {
                    row += '<span class="text-muted">-</span>';
                }
                row += '</td>';
            } else if (tableId === 'rekapTableHadir') {
                row += '<td><span class="text-success"><i class="fas fa-clock me-1"></i>' + item.waktu_kehadiran + '</span></td>';
            } else if (['rekapTableIjin', 'rekapTableCuti', 'rekapTableSakit', 'rekapTableBerhalangan'].includes(tableId)) {
                row += '<td class="text-center">';
                if (item.alasan) {
                    row += '<span title="' + item.alasan.replace(/"/g, '&quot;') + '">' + 
                        (item.alasan.length > 50 ? item.alasan.substring(0, 50) + '...' : item.alasan) + 
                        '</span>';
                } else {
                    row += '<span class="text-muted">-</span>';
                }
                row += '</td>';
            }
            
            row += '</tr>';
            tbody.append(row);
        });
    }
    
    // Function to update statistics
    function updateStatistics() {
        const agendaId = $('#agenda_id').val();
        
        if (!agendaId) {
            $('#rekapContainer').html('');
            $('#progressContainer').hide();
            return;
        }
        
        $.ajax({
            url: '/rekap-absensi',
            type: 'GET',
            data: { agenda_id: agendaId },
            dataType: 'json',
            success: function(response) {
                if (response.rekap) {
                    const r = response.rekap;
                    const totalHadir = (r.jumlah_hadir || 0) + (r.jumlah_ijin || 0) + (r.jumlah_cuti || 0) + (r.jumlah_sakit || 0) + (r.jumlah_berhalangan || 0);
                    const percentage = r.jumlah_undangan > 0 
                        ? Math.round((totalHadir / r.jumlah_undangan) * 100) 
                        : 0;
                    
                    // Update cards
                    $('#rekapContainer').html(`
                        <div class="row g-3">
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-warning-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Jumlah Undangan</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_undangan !== undefined && r.jumlah_undangan !== null) ? r.jumlah_undangan : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-success-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Hadir</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_hadir !== undefined && r.jumlah_hadir !== null) ? r.jumlah_hadir : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-info-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Ijin</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_ijin !== undefined && r.jumlah_ijin !== null) ? r.jumlah_ijin : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-primary-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Cuti</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_cuti !== undefined && r.jumlah_cuti !== null) ? r.jumlah_cuti : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-warning-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Sakit</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_sakit !== undefined && r.jumlah_sakit !== null) ? r.jumlah_sakit : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-secondary-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Berhalangan</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_berhalangan !== undefined && r.jumlah_berhalangan !== null) ? r.jumlah_berhalangan : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-danger-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Tidak Hadir</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${(r.jumlah_tidak_hadir !== undefined && r.jumlah_tidak_hadir !== null) ? r.jumlah_tidak_hadir : 0} orang</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Update progress bar
                    $('#progressBar').css('width', percentage + '%');
                    $('#progressText').text(percentage + '%');
                    $('#progressContainer').show();
                }
            }
        });
    }
    
    // Load data when agenda is selected
    function loadData() {
        const agendaId = $('#agenda_id').val();
        
        if (!agendaId) {
            allData = [];
            filteredData = [];
            $('#searchInput').val('');
            $('#clearSearch').hide();
            updateTables();
            $('#rekapContainer').html('');
            $('#progressContainer').hide();
            return;
        }
        
        $.ajax({
            url: '/rekap-absensi',
            type: 'GET',
            data: { 
                agenda_id: agendaId,
                type: 'terundang'
            },
            dataType: 'json',
            success: function(response) {
                allData = response.data || [];
                filteredData = allData;
                
                // Reset search
                $('#searchInput').val('');
                $('#clearSearch').hide();
                
                // Tampilkan tombol export jika ada data
                if (allData.length > 0) {
                    $('#btnExportPDF').show();
                }
                
                updateTables();
                updateStatistics();
            },
            error: function() {
                allData = [];
                filteredData = [];
                updateTables();
            }
        });
    }
    
    // Event handlers sudah di-handle di atas saat initialize Select2
    
    // Search input dengan debounce
    $('#searchInput').on('input', function() {
        const hasValue = $(this).val().trim().length > 0;
        $('#clearSearch').toggle(hasValue);
        debouncedFilter();
    });
    
    // Clear search button
    $('#clearSearch').on('click', function() {
        $('#searchInput').val('').focus();
        $(this).hide();
        filterData();
    });
    
    // Check initial state
    if ($('#searchInput').val().trim().length > 0) {
        $('#clearSearch').show();
    }
    
    // Load data on page load if agenda_id is set
    @if(request('agenda_id'))
        const urlParams = new URLSearchParams(window.location.search);
        const agendaIdParam = urlParams.get('agenda_id');
        if (agendaIdParam) {
            // Set rentang tanggal dari agenda yang dipilih (set ke tanggal yang sama untuk start dan end)
            const selectedOption = $('#agenda_id option[value="' + agendaIdParam + '"]');
            if (selectedOption.length) {
                const tanggalAgenda = selectedOption.data('tanggal');
                if (tanggalAgenda) {
                    $('#filterTanggalStart').val(tanggalAgenda);
                    $('#filterTanggalEnd').val(tanggalAgenda);
                    filterAgendaByRentangTanggal();
                }
            }
            loadData();
        }
    @endif
});

// Function to open edit status modal
function editStatus(idAbsensi, nik, nama, status, alasan, agendaId) {
    $('#editStatusModal').modal('show');
    $('#edit_nik').text(nik);
    $('#edit_nama').text(nama);
    $('#edit_status_kehadiran').val(status || 'tidak_hadir');
    $('#edit_alasan').val(alasan || '');
    $('#edit_id_absensi').val(idAbsensi);
    $('#edit_agenda_id').val(agendaId || $('#agenda_id').val());
    $('#edit_nik_value').val(nik);
}

// Function to save status
function saveStatus() {
    const idAbsensi = $('#edit_id_absensi').val();
    const agendaId = $('#edit_agenda_id').val();
    const nik = $('#edit_nik_value').val();
    const status = $('#edit_status_kehadiran').val();
    const alasan = $('#edit_alasan').val();
    
    if (!status) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Status kehadiran harus dipilih!'
        });
        return;
    }
    
    let url, data;
    
    if (idAbsensi && idAbsensi !== 'null') {
        // Update existing
        url = '/absensi-agenda/update-status';
        data = {
            id_absensi: idAbsensi,
            status_kehadiran: status,
            alasan: alasan
        };
    } else {
        // Create new
        url = '/absensi-agenda/create-update';
        data = {
            agenda_id: agendaId,
            nik: nik,
            status_kehadiran: status,
            alasan: alasan
        };
    }
    
    $.ajax({
        url: url,
        type: 'POST',
        data: {
            ...data,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#editStatusModal').modal('hide');
                loadData(); // Reload data
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Terjadi kesalahan saat menyimpan data.'
                });
            }
        },
        error: function(xhr) {
            let errorMessage = 'Terjadi kesalahan saat menyimpan data.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        }
    });
}
</script>

<!-- Modal Edit Status -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStatusModalLabel">Edit Status Kehadiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStatusForm">
                    <input type="hidden" id="edit_id_absensi" value="">
                    <input type="hidden" id="edit_agenda_id" value="">
                    <input type="hidden" id="edit_nik_value" value="">
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>NIK</strong></label>
                        <p id="edit_nik" class="form-control-plaintext"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Nama</strong></label>
                        <p id="edit_nama" class="form-control-plaintext"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status_kehadiran" class="form-label"><strong>Status Kehadiran</strong></label>
                        <select class="form-control" id="edit_status_kehadiran" required>
                            <option value="hadir">Hadir</option>
                            <option value="ijin">Ijin</option>
                            <option value="cuti">Cuti</option>
                            <option value="sakit">Sakit</option>
                            <option value="berhalangan">Berhalangan</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_alasan" class="form-label"><strong>Alasan/Keterangan</strong></label>
                        <textarea class="form-control" id="edit_alasan" rows="3" placeholder="Masukkan alasan atau keterangan (opsional)"></textarea>
                        <small class="text-muted">Wajib diisi untuk status Ijin, Cuti, Sakit, atau Berhalangan</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveStatus()">Simpan</button>
            </div>
        </div>
    </div>
</div>
</script>

<style>
/* Select2 styling untuk konsistensi dengan form */
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
    padding-left: 12px;
    color: #495057;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 10px;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 8px 12px;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Styling untuk custom template result */
.select2-results__option {
    padding: 0.5rem;
}

.select2-results__option .d-flex {
    line-height: 1.6;
}

.select2-results__option strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #212529;
    font-size: 0.95rem;
}

.select2-results__option small {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
}

/* Hover effect */
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] strong,
.select2-container--default .select2-results__option--highlighted[aria-selected] small {
    color: white;
}

/* Styling untuk filter card */
#filterForm .card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #dee2e6 !important;
}

#filterForm .card-title {
    color: #495057;
    font-weight: 600;
}

/* Styling untuk input group dengan icon */
.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

#searchInput {
    border-left: none;
}

#searchInput:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Clear button styling */
#clearSearch {
    display: none;
    transition: all 0.3s ease;
}

#clearSearch:hover {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

/* Search result info styling */
#searchResultInfo {
    border-left: 4px solid #0d6efd;
    background-color: #e7f1ff;
    border-radius: 0.375rem;
}

#searchResultInfo i {
    color: #0d6efd;
}

/* Filter tanggal styling */
#filterTanggalStart, #filterTanggalEnd {
    transition: all 0.3s ease;
}

#filterTanggalStart:focus, #filterTanggalEnd:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #filterForm .col-md-6, #filterForm .col-md-4 {
        margin-bottom: 1rem;
    }
}

/* Highlight search results in table */
.highlight {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}
</style>
@endsection
