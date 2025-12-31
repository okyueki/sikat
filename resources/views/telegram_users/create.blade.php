@extends('layouts.pages-layouts')

@section('pageTitle', 'Tambah Chat ID Telegram')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="mb-0">Tambah Chat ID Telegram</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('telegram-users.store') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="nik" class="form-label">Pilih Pegawai</label>
                                <select class="form-control" id="nik" name="nik" required>
                                    <option value="">-- Pilih Pegawai --</option>
                                    @foreach ($pegawai as $p)
                                        <option value="{{ $p->nik }}">{{ $p->nik }} - {{ $p->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="chat_id" class="form-label">Chat ID Telegram</label>
                                <input type="text" 
                                       class="form-control @error('chat_id') is-invalid @enderror" 
                                       id="chat_id" 
                                       name="chat_id" 
                                       value="{{ old('chat_id') }}" 
                                       required>
                                @error('chat_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan
                                </button>
                                <a href="{{ route('telegram-users.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
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
        const elements = ['nik']; 
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                new Choices(element, {
                    searchEnabled: true,
                    position: 'auto',
                    shouldSort: false,
                    allowHTML: true,
                    placeholderValue: 'Cari pegawai...',
                    searchPlaceholderValue: 'Ketik untuk mencari...'
                });
            }
        });
    });
</script>
@endsection
