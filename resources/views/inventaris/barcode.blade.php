@extends('layouts.pages-layouts')

@section('pageTitle', 'Barcode Inventaris')

@section('content')
<style>
    /* Styling untuk memastikan ukuran 7x4 cm */
    .barcode-page {
        font-family: Arial, sans-serif;
        background-color: #f7f7f7;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .barcode-container {
        width: 7cm;
        height: 4cm;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fff;
        box-sizing: border-box;
        padding: 5px;
    }

    .barcode-content {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 100%;
    }

    .barcode-image-container {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50%;
    }

    .barcode-image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .barcode-text-container {
        flex: 1;
        padding-left: 5px;
        text-align: left;
        font-size: 10px;
        line-height: 1.2;
    }

    .barcode-text-container .text-label {
        font-size: 16px;
        margin-bottom: 5px;
    }

    @media print {
        .barcode-page {
            background-color: #fff;
            padding: 0;
        }
        
        .barcode-container {
            width: 7cm;
            height: 4cm;
            border: none;
            box-shadow: none;
        }

        .barcode-image-container img {
            max-width: 100%;
            max-height: 100%;
        }
    }
</style>

<div class="barcode-page">
    <div class="barcode-container">
        <div class="barcode-content">
            <!-- Menampilkan Barcode -->
            <div class="barcode-image-container">
                <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode">
            </div>

            <!-- Menampilkan Tulisan di Sebelah Kanan -->
            <div class="barcode-text-container">
                <div class="text-label">
                    <strong>No Inventaris:</strong><br>
                    {{ $no_inventaris }}
                </div>
                <div class="text-label">
                    <strong>Nama Barang:</strong><br>
                    {{ $inventaris->barang->nama_barang }}
                </div>
                <div class="text-label">
                    <strong>Lokasi:</strong><br>
                    {{ $inventaris->ruang->nama_ruang }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
