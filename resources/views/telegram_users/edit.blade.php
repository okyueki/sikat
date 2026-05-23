@extends('layouts.pages-layouts')

@section('pageTitle', 'Edit Chat ID Telegram')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3>Edit Chat ID Telegram</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('telegram-users.update', $user->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="nik" class="form-label">Pegawai</label>
                                <select class="form-control" id="nik" name="nik" required>
                                    <option value="">-- Pilih Pegawai --</option>
                                    @foreach ($pegawai as $p)
                                        <option value="{{ $p->nik }}" 
                                            {{ $user->nik == $p->nik ? 'selected' : '' }}>
                                            {{ $p->nik }} - {{ $p->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nik')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="chat_id" class="form-label">Chat ID Telegram</label>
                                <input type="text" 
                                       class="form-control @error('chat_id') is-invalid @enderror" 
                                       id="chat_id" 
                                       name="chat_id" 
                                       value="{{ old('chat_id', $user->chat_id) }}" 
                                       required>
                                @error('chat_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
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
        const element = document.getElementById('nik');
        if (element) {
            new Choices(element, {
                searchEnabled: true,
                position: 'auto',
                shouldSort: false,
                allowHTML: true,
            });
        }
    });
</script>
@endsection
