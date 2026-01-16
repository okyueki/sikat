@extends('layouts.pages-layouts')

@section('pageTitle', 'Detail Inventaris')

@section('content')
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detail Inventaris</h3>
            <a href="{{ route('inventaris.index') }}" class="btn btn-secondary float-right">Kembali</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>No Inventaris</th>
                    <td>{{ $inventaris->no_inventaris }}</td>
                </tr>
                <tr>
                    <th>Kode Barang</th>
                    <td>{{ $inventaris->kode_barang }}</td>
                </tr>
                <tr>
                    <th>Nama Barang</th>
                    <td>{{ $inventaris->barang->nama_barang ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Produsen</th>
                    <td>{{ optional($inventaris->barang->produsen)->nama_produsen ?? 'Tidak Diketahui' }}</td>
                </tr>
                <tr>
                    <th>Merk</th>
                    <td>{{ optional($inventaris->barang->merk)->nama_merk ?? 'Tidak Diketahui' }}</td>
                </tr>
                <tr>
                    <th>Nama Ruang</th>
                    <td>{{ optional($inventaris->ruang)->nama_ruang ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Asal Barang</th>
                    <td>{{ $inventaris->asal_barang }}</td>
                </tr>
                <tr>
                    <th>Tanggal Pengadaan</th>
                    <td>{{ $inventaris->tgl_pengadaan }}</td>
                </tr>
                <tr>
                    <th>Harga</th>
                    <td>
                        @php
                            if (!function_exists('formatRupiah')) {
                                $formatPath = app_path('Helpers/FormatHelper.php');
                                if (file_exists($formatPath)) {
                                    require_once $formatPath;
                                }
                            }
                        @endphp
                        {{ function_exists('formatRupiah') ? formatRupiah($inventaris->harga, true) : 'Rp ' . number_format($inventaris->harga, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <th>Status Barang</th>
                    <td>{{ $inventaris->status_barang }}</td>
                </tr>
                <tr>
                    <th>ID Ruang</th>
                    <td>{{ $inventaris->id_ruang }}</td>
                </tr>
                <tr>
                    <th>No Rak</th>
                    <td>{{ $inventaris->no_rak }}</td>
                </tr>
                <tr>
                    <th>No Box</th>
                    <td>{{ $inventaris->no_box }}</td>
                </tr>
            </table>

            <h4>Gambar Inventaris</h4>
            @php
                // Load helper function if not loaded
                if (!function_exists('getInventarisImageBase64')) {
                    $helperPath = app_path('Helpers/InventarisHelper.php');
                    if (file_exists($helperPath)) {
                        require_once $helperPath;
                    }
                }
                
                $hasGambar = $inventaris->gambar && $inventaris->gambar->count() > 0;
            @endphp
            
            @if($hasGambar)
                <div class="row">
                    @foreach ($inventaris->gambar as $gambar)
                        @if(!empty($gambar->photo))
                            <div class="col-md-3 mb-3">
                                @php
                                    $base64Image = function_exists('getInventarisImageBase64') ? getInventarisImageBase64($gambar->photo) : null;
                                @endphp
                                @if($base64Image)
                                    <img src="{{ $base64Image }}" class="img-fluid rounded shadow-sm" alt="Gambar Inventaris" style="max-height: 200px; object-fit: cover; cursor: pointer;" onclick="window.open(this.src, '_blank')">
                                @else
                                    <div class="alert alert-warning small mb-0">
                                        <i class="fa fa-exclamation-triangle me-1"></i>Gambar tidak dapat dimuat
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle me-2"></i>Tidak ada gambar untuk inventaris ini.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
