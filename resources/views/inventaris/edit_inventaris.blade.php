@extends('layouts.pages-layouts')

@section('pageTitle', 'Edit Inventaris')

@section('breadcrumbs')
    <x-sikat.breadcrumbs :items="[
        ['label' => 'Inventaris', 'url' => route('inventaris.index')],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Inventaris</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('inventaris.update', $inventaris->no_inventaris) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('inventaris.form_inventaris', ['inventaris' => $inventaris])
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        new Choices('#kode_barang', { searchEnabled: true, shouldSort: false });
        new Choices('#asal_barang', { searchEnabled: true, shouldSort: false });
        new Choices('#status_barang', { searchEnabled: true, shouldSort: false });
        new Choices('#id_ruang', { searchEnabled: true, shouldSort: false });
    });

    @include('inventaris.partials.barang_info_loader')

    $(document).ready(function() {
        $('#kode_barang').on('change', function() {
            loadInventarisBarangInfo($(this).val());
        });
    });
</script>
@endsection