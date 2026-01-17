@extends('layouts.pages-layouts')

@section('pageTitle', 'Daftar Agenda')

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-body"> {{-- Ganti container jadi card-body --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0">Daftar Agenda</h1>
                        <a href="{{ route('acara_create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i> Tambah Agenda
                        </a>
                    </div>

                    {{-- Filter Form --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <form id="filterForm" class="row g-3">
                                <div class="col-md-3">
                                    <label for="filter_tahun" class="form-label"><strong>Filter Tahun</strong></label>
                                    <select name="filter_tahun" id="filter_tahun" class="form-control">
                                        <option value="">Semua Tahun</option>
                                        @for($year = date('Y'); $year >= 2020; $year--)
                                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter_bulan" class="form-label"><strong>Filter Bulan (Opsional)</strong></label>
                                    <select name="filter_bulan" id="filter_bulan" class="form-control">
                                        <option value="">Semua Bulan</option>
                                        <option value="01">Januari</option>
                                        <option value="02">Februari</option>
                                        <option value="03">Maret</option>
                                        <option value="04">April</option>
                                        <option value="05">Mei</option>
                                        <option value="06">Juni</option>
                                        <option value="07">Juli</option>
                                        <option value="08">Agustus</option>
                                        <option value="09">September</option>
                                        <option value="10">Oktober</option>
                                        <option value="11">November</option>
                                        <option value="12">Desember</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter_status_realisasi" class="form-label"><strong>Status Realisasi</strong></label>
                                    <select name="filter_status_realisasi" id="filter_status_realisasi" class="form-control">
                                        <option value="">Semua</option>
                                        <option value="belum">Belum</option>
                                        <option value="sedang">Sedang</option>
                                        <option value="selesai">Selesai</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" id="resetFilter" class="btn btn-secondary me-2">
                                        <i class="fas fa-redo me-1"></i> Reset
                                    </button>
                                    <button type="button" id="applyFilter" class="btn btn-primary">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if ($message = Session::get('success'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: '{{ $message }}',
                                    confirmButtonText: 'OK'
                                });
                            });
                        </script>
                    @endif
                
                    <table class="table table-bordered" id="agendaTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Mulai</th>
                                <th>Akhir</th>
                                <th>Tempat</th>
                                <th>Pimpinan Rapat</th>
                                <th>Notulen</th>
                                <th>Jumlah Terundang</th>
                                <th>Status Realisasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#agendaTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/backend-acara',
                    data: function(d) {
                        d.filter_tahun = $('#filter_tahun').val();
                        d.filter_bulan = $('#filter_bulan').val();
                        d.filter_status_realisasi = $('#filter_status_realisasi').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'judul', name: 'judul' },
                    { data: 'mulai', name: 'mulai' },
                    { data: 'akhir', name: 'akhir' },
                    { data: 'tempat', name: 'tempat' },
                    { data: 'pimpinan_nama', name: 'pimpinan.nama' },
                    { data: 'notulen_nama', name: 'notulenPegawai.nama' },
                    { data: 'jumlah_terundang', name: 'jumlah_terundang', searchable: false },
                    { data: 'status_realisasi', name: 'status_realisasi' },
                    { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
                ],
                order: [[2, 'desc']] // Sort by mulai date descending
            });

            // Apply filter button
            $('#applyFilter').on('click', function() {
                table.ajax.reload();
            });

            // Reset filter button
            $('#resetFilter').on('click', function() {
                $('#filter_tahun').val('{{ date('Y') }}');
                $('#filter_bulan').val('');
                $('#filter_status_realisasi').val('');
                table.ajax.reload();
            });
        });
    </script>
@endsection