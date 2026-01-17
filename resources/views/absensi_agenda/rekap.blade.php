@extends('layouts.pages-layouts')

@section('pageTitle', 'Rekap Absensi Agenda')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Rekap Absensi Agenda</h4>
                    @if(request('agenda_id'))
                        <a href="{{ route('acara_show', request('agenda_id')) }}" class="btn btn-info">
                            <i class="fas fa-arrow-left"></i> Kembali ke Detail Agenda
                        </a>
                    @endif
                </div>

                <!-- Filter by Agenda -->
                <form id="filterForm" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agenda_id"><strong>Pilih Agenda</strong></label>
                                <select class="form-control" id="agenda_id" name="agenda_id">
                                    <option value="">Semua Agenda</option>
                                    @foreach ($agendas as $agenda)
                                        @php
                                            $tanggalMulai = \Carbon\Carbon::parse($agenda->mulai)->format('d M Y H:i');
                                            $pimpinanNama = $agenda->pimpinan ? $agenda->pimpinan->nama : '-';
                                            // Gabungkan semua info untuk search
                                            $searchText = $agenda->judul . ' | ' . $pimpinanNama . ' | ' . $tanggalMulai;
                                        @endphp
                                        <option value="{{ $agenda->id }}" {{ request('agenda_id') == $agenda->id ? 'selected' : '' }}
                                            data-pimpinan="{{ $pimpinanNama }}"
                                            data-tanggal="{{ $tanggalMulai }}"
                                            data-search="{{ $searchText }}">
                                            {{ $agenda->judul }} | {{ $pimpinanNama }} | {{ $tanggalMulai }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Cari berdasarkan judul, pimpinan, atau tanggal</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="searchInput"><strong>Cari Pegawai</strong></label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Cari nama, NIK, atau jabatan...">
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
                                        <th width="15%">Waktu Kehadiran</th>
                                        <th width="5%">Status</th>
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
    // Initialize Choices.js for agenda dropdown with custom search
    const agendaSelect = document.getElementById('agenda_id');
    let choicesInstance = null;
    
    if (agendaSelect && typeof Choices !== 'undefined') {
        choicesInstance = new Choices(agendaSelect, {
            searchEnabled: true,
            placeholder: true,
            placeholderValue: 'Pilih Agenda...',
            searchPlaceholderValue: 'Cari agenda, pimpinan, atau tanggal...',
            position: 'bottom',
            shouldSort: false,
            itemSelectText: '',
            allowHTML: true,
            fuseOptions: {
                threshold: 0.3,
                keys: ['label', 'value']
            }
        });
        
        // Custom search untuk mencari di data-search attribute (mencakup judul, pimpinan, tanggal)
        const originalSearch = choicesInstance._searchChoices;
        choicesInstance._searchChoices = function(query) {
            if (!query || query.length < 1) {
                return this._store.choices;
            }
            
            const searchTerm = query.toLowerCase();
            return this._store.choices.filter(choice => {
                const option = choice.element;
                const searchText = option && option.dataset ? (option.dataset.search || '').toLowerCase() : '';
                const label = choice.label.toLowerCase();
                
                return label.includes(searchTerm) || searchText.includes(searchTerm);
            });
        };
        
        // Modifikasi tampilan item setelah Choices.js render dengan MutationObserver
        const observer = new MutationObserver(() => {
            const dropdown = choicesInstance.dropdown.element;
            if (dropdown) {
                const items = dropdown.querySelectorAll('.choices__item--selectable:not(.has-custom-html)');
                items.forEach(item => {
                    const choiceId = item.dataset.id;
                    const choice = choicesInstance._store.choices.find(c => c.id === choiceId);
                    
                    if (choice && choice.element && choice.element.dataset) {
                        const pimpinan = choice.element.dataset.pimpinan || '-';
                        const tanggal = choice.element.dataset.tanggal || '-';
                        const parts = choice.label.split(' | ');
                        const judul = parts[0] || choice.label;
                        
                        item.classList.add('has-custom-html');
                        item.innerHTML = `
                            <div class="d-flex flex-column py-2 px-2">
                                <strong class="mb-1">${judul}</strong>
                                <small class="text-muted mb-1"><i class="fas fa-user me-1"></i>Pimpinan: ${pimpinan}</small>
                                <small class="text-muted"><i class="fas fa-calendar me-1"></i>Tanggal: ${tanggal}</small>
                            </div>
                        `;
                    }
                });
            }
        });
        
        // Observe dropdown container
        if (choicesInstance.dropdown && choicesInstance.dropdown.element) {
            observer.observe(choicesInstance.dropdown.element, { 
                childList: true, 
                subtree: true 
            });
        }
        
        // Update tampilan saat item dipilih
        agendaSelect.addEventListener('addItem', function(event) {
            const option = event.detail.choice.element;
            if (option && option.dataset) {
                const pimpinan = option.dataset.pimpinan || '-';
                const tanggal = option.dataset.tanggal || '-';
                const parts = event.detail.choice.label.split(' | ');
                const judul = parts[0] || event.detail.choice.label;
                
                // Update selected item display
                setTimeout(() => {
                    const selectedItem = choicesInstance.containerOuter.element.querySelector('.choices__item--selectable.is-selected');
                    if (selectedItem) {
                        const label = selectedItem.querySelector('.choices__item--choice');
                        if (label) {
                            label.innerHTML = `
                                <div class="d-flex flex-column">
                                    <strong>${judul}</strong>
                                    <small class="text-muted"><i class="fas fa-user me-1"></i>${pimpinan}</small>
                                    <small class="text-muted"><i class="fas fa-calendar me-1"></i>${tanggal}</small>
                                </div>
                            `;
                        }
                    }
                }, 50);
            }
        });
    }
    
    let allData = [];
    let filteredData = [];
    
    // Function to filter data
    function filterData(searchTerm) {
        if (!searchTerm) {
            filteredData = allData;
        } else {
            const term = searchTerm.toLowerCase();
            filteredData = allData.filter(item => 
                item.nama.toLowerCase().includes(term) ||
                item.nik.toLowerCase().includes(term) ||
                (item.jabatan && item.jabatan.toLowerCase().includes(term)) ||
                (item.departemen && item.departemen.toLowerCase().includes(term))
            );
        }
        updateTables();
    }
    
    // Function to update all tables
    function updateTables() {
        const hadirData = filteredData.filter(item => item.status === 'hadir');
        const tidakHadirData = filteredData.filter(item => item.status === 'tidak_hadir');
        
        // Update counts
        $('#count-all').text(filteredData.length);
        $('#count-hadir').text(hadirData.length);
        $('#count-tidak-hadir').text(tidakHadirData.length);
        
        // Update tables
        updateTable('rekapTableAll', filteredData);
        updateTable('rekapTableHadir', hadirData);
        updateTable('rekapTableTidakHadir', tidakHadirData);
        
        // Update statistics
        updateStatistics();
    }
    
    // Function to update single table
    function updateTable(tableId, data) {
        const tbody = $('#' + tableId + ' tbody');
        tbody.empty();
        
        if (data.length === 0) {
            const colSpan = tableId === 'rekapTableTidakHadir' ? 5 : (tableId === 'rekapTableAll' ? 7 : 6);
            tbody.append('<tr><td colspan="' + colSpan + '" class="text-center text-muted">Tidak ada data</td></tr>');
            return;
        }
        
        data.forEach((item, index) => {
            let row = '<tr>';
            row += '<td>' + (index + 1) + '</td>';
            row += '<td>' + item.nik + '</td>';
            row += '<td><strong>' + item.nama + '</strong></td>';
            row += '<td>' + item.jabatan + '</td>';
            row += '<td>' + item.departemen + '</td>';
            
            if (tableId === 'rekapTableAll') {
                row += '<td>' + (item.waktu_kehadiran !== '-' ? item.waktu_kehadiran : '<span class="text-muted">-</span>') + '</td>';
                row += '<td class="text-center">';
                if (item.status === 'hadir') {
                    row += '<span class="badge bg-success"><i class="fas fa-check"></i> Hadir</span>';
                } else {
                    row += '<span class="badge bg-danger"><i class="fas fa-times"></i> Tidak Hadir</span>';
                }
                row += '</td>';
            } else if (tableId === 'rekapTableHadir') {
                row += '<td><span class="text-success"><i class="fas fa-clock me-1"></i>' + item.waktu_kehadiran + '</span></td>';
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
                    const percentage = r.jumlah_undangan > 0 
                        ? Math.round((r.jumlah_hadir / r.jumlah_undangan) * 100) 
                        : 0;
                    
                    // Update cards
                    $('#rekapContainer').html(`
                        <div class="row g-3">
                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                <div class="card overflow-hidden sales-card bg-warning-gradient">
                                    <div class="px-3 pt-3 pb-2 pt-0">
                                        <div>
                                            <h6 class="mb-3 fs-12 text-fixed-white">Jumlah Undangan</h6>
                                        </div>
                                        <div>
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${r.jumlah_undangan ?? 0} orang</h4>
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
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${r.jumlah_hadir ?? 0} orang</h4>
                                            <small class="text-fixed-white">${percentage}% dari total</small>
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
                                            <h4 class="fs-20 fw-bold mb-1 text-fixed-white">${r.jumlah_tidak_hadir ?? 0} orang</h4>
                                            <small class="text-fixed-white">${100 - percentage}% dari total</small>
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
        const agendaId = choicesInstance ? choicesInstance.getValue(true) : $('#agenda_id').val();
        
        if (!agendaId) {
            allData = [];
            filteredData = [];
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
    
    // Event handlers
    // Handle change event untuk Choices.js
    if (choicesInstance) {
        agendaSelect.addEventListener('change', function() {
            loadData();
        });
    } else {
        $('#agenda_id').on('change', function() {
            loadData();
        });
    }
    
    $('#searchInput').on('keyup', function() {
        filterData($(this).val());
    });
    
    // Load data on page load if agenda_id is set
    @if(request('agenda_id'))
        loadData();
    @endif
});
</script>

<style>
/* Custom styling untuk Choices.js dropdown dengan info lengkap */
.choices__list--dropdown .choices__item {
    padding: 0.5rem;
    white-space: normal;
}

.choices__list--dropdown .choices__item .d-flex {
    line-height: 1.6;
}

.choices__list--dropdown .choices__item strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #212529;
}

.choices__list--dropdown .choices__item small {
    display: block;
    font-size: 0.75rem;
}

.choices__list--single .choices__item--selectable {
    padding: 0.375rem 0.75rem;
}

.choices__list--single .choices__item--selectable .d-flex {
    line-height: 1.5;
}

.choices__list--single .choices__item--selectable strong {
    display: block;
    margin-bottom: 0.125rem;
}

.choices__list--single .choices__item--selectable small {
    display: block;
    font-size: 0.7rem;
    margin-top: 0.125rem;
}

/* Ensure dropdown items are properly styled */
.choices__list--dropdown .choices__item--selectable {
    border-bottom: 1px solid #f0f0f0;
}

.choices__list--dropdown .choices__item--selectable:last-child {
    border-bottom: none;
}
</style>
@endsection
