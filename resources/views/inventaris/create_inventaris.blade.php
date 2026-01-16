@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Create Inventaris')

@section('content')
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tambah Inventaris</h3>
        </div>
        <div class="card-body">
            <!-- Form utama, pastikan enctype ditambahkan -->
            <form action="{{ route('inventaris.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Include bagian form dari file terpisah -->
                @include('inventaris.form_inventaris')
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inisialisasi Choices.js untuk semua dropdown
        const kodeBarang = new Choices('#kode_barang', {
            searchEnabled: true,
            shouldSort: false,
        });

        const asalBarang = new Choices('#asal_barang', {
            searchEnabled: true,
            shouldSort: false,
        });

        const statusBarang = new Choices('#status_barang', {
            searchEnabled: true,
            shouldSort: false,
        });

        const idRuang = new Choices('#id_ruang', {
            searchEnabled: true,
            shouldSort: false,
        });
    });

    // Mengambil data barang berdasarkan pilihan kode_barang
    $(document).ready(function() {
        $('#kode_barang').change(function() {
            var kode_barang = $(this).val();
            var produsenField = $('#produsen');
            var merkField = $('#merk');
            
            if (kode_barang) {
                // Show loading state
                produsenField.prop('disabled', true).val('Memuat...');
                merkField.prop('disabled', true).val('Memuat...');
                
                $.ajax({
                    url: '/get-barang-info/' + kode_barang,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 10000, // 10 seconds timeout
                    beforeSend: function() {
                        // Additional loading indicator if needed
                    },
                    success: function(data) {
                        if (data && data.produsen !== undefined && data.merk !== undefined) {
                            produsenField.val(data.produsen || 'Tidak Diketahui');
                            merkField.val(data.merk || 'Tidak Diketahui');
                        } else {
                            produsenField.val('Tidak Diketahui');
                            merkField.val('Tidak Diketahui');
                            console.warn('Data tidak lengkap dari server');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading barang info:', error);
                        produsenField.val('Tidak Diketahui');
                        merkField.val('Tidak Diketahui');
                        
                        // Show user-friendly error message
                        if (status === 'timeout') {
                            alert('Waktu koneksi habis. Silakan coba lagi.');
                        } else if (xhr.status === 404) {
                            console.warn('Endpoint tidak ditemukan');
                        } else {
                            alert('Gagal memuat data barang. Silakan coba lagi.');
                        }
                    },
                    complete: function() {
                        // Re-enable fields
                        produsenField.prop('disabled', false);
                        merkField.prop('disabled', false);
                    }
                });
            } else {
                produsenField.val('Tidak Diketahui');
                merkField.val('Tidak Diketahui');
            }
        });
    });
</script>
@endsection