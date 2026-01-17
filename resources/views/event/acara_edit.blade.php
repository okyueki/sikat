@extends('layouts.pages-layouts')

@section('pageTitle', 'Edit Agenda')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <h2 class="mb-4">Edit Agenda</h2>
                <form action="{{ route('acara_update', $agenda->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" value="{{ $agenda->judul }}" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control">{{ $agenda->deskripsi }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Mulai</label>
                        <input type="text" name="mulai" id="mulai" class="form-control" value="{{ \Carbon\Carbon::parse($agenda->mulai)->format('Y-m-d H:i') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Akhir</label>
                        <input type="text" name="akhir" id="akhir" class="form-control" value="{{ $agenda->akhir ? \Carbon\Carbon::parse($agenda->akhir)->format('Y-m-d H:i') : '' }}">
                    </div>
                    <div class="form-group">
                        <label>Tempat</label>
                        <input type="text" name="tempat" class="form-control" value="{{ $agenda->tempat }}">
                    </div>
                    <div class="form-group">
                        <label>Pimpinan Rapat</label>
                        <select name="pimpinan_rapat" id="pimpinan_rapat" class="form-control">
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nik }}" {{ $agenda->pimpinan_rapat == $p->nik ? 'selected' : '' }}>
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notulen</label>
                        <select name="notulen" id="notulen" class="form-control">
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nik }}" {{ $agenda->notulen == $p->nik ? 'selected' : '' }}>
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Yang Terundang</label>
                        <select name="yang_terundang[]" id="yang_terundang" class="form-control" multiple required>
                            <option value="all" {{ isset($isAll) && $isAll ? 'selected' : '' }}>Pilih Semua</option>
                            @php
                                $yangTerundang = is_string($agenda->yang_terundang) ? json_decode($agenda->yang_terundang, true) : $agenda->yang_terundang;
                            @endphp
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nik }}" {{ (isset($isAll) && !$isAll && is_array($yangTerundang) && in_array($p->nik, $yangTerundang)) ? 'selected' : '' }}>
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Foto</label>
                        @if($agenda->foto)
                            <div class="mb-2">
                                <p>Foto saat ini:</p>
                                <img src="{{ asset('storage/' . $agenda->foto) }}" alt="Foto Agenda" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            </div>
                        @endif
                        <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah foto. Maksimal 2MB. Format: JPG, JPEG, PNG</small>
                        <div id="foto-preview" class="mt-2" style="display: none;">
                            <p>Preview Foto Baru:</p>
                            <img id="foto-preview-img" src="" alt="Preview Foto" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeFotoPreview()">Hapus Preview</button>
                        </div>
                        <div id="foto-error" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label>Materi</label>
                        @if($agenda->materi)
                            <div class="mb-2">
                                <p>Materi saat ini:</p>
                                <a href="{{ asset('storage/' . $agenda->materi) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-download"></i> Download Materi
                                </a>
                            </div>
                        @endif
                        <input type="file" name="materi" id="materi" class="form-control" accept=".pdf,.doc,.docx">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah materi. Maksimal 2MB. Format: PDF, DOC, DOCX</small>
                        <div id="materi-error" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control">{{ $agenda->keterangan }}</textarea>
                    </div>

                    <div class="form-group">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_realisasi_surat" 
                                   name="is_realisasi_surat" value="1" {{ $agenda->id_surat_keluar ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_realisasi_surat">
                                <strong>Ini realisasi dari Surat Keluar</strong>
                            </label>
                        </div>
                        
                        <div id="surat_keluar_select" style="display: {{ $agenda->id_surat_keluar ? 'block' : 'none' }};">
                            <label for="id_surat_keluar">Pilih Surat Keluar</label>
                            <select name="id_surat_keluar" id="id_surat_keluar" class="form-control">
                                <option value="">-- Pilih Surat Keluar --</option>
                                @foreach($suratKeluar ?? [] as $sk)
                                    <option value="{{ $sk->id_surat }}" {{ $agenda->id_surat_keluar == $sk->id_surat ? 'selected' : '' }}>
                                        {{ $sk->nomor_surat }} | {{ $sk->perihal }} 
                                        ({{ \Carbon\Carbon::parse($sk->tanggal_surat)->format('d M Y') }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hanya menampilkan surat keluar yang sudah disetujui dan belum ada realisasi</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('backend_acara') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for both date fields
        flatpickr('input[name="mulai"]', {
            enableTime: true,
            dateFormat: "Y-m-d H:i"
        });
        flatpickr('input[name="akhir"]', {
            enableTime: true,
            dateFormat: "Y-m-d H:i"
        });

        const yangTerundangSelect = document.getElementById('yang_terundang');

        yangTerundangSelect.addEventListener('change', function() {
            // Cek apakah opsi "Pilih Semua" dipilih
            const isSelectAll = Array.from(this.selectedOptions).some(option => option.value === 'all');

            if (isSelectAll) {
                // Jika "Pilih Semua" dipilih, pilih semua opsi
                for (let i = 0; i < this.options.length; i++) {
                    this.options[i].selected = true;
                }
            } else {
                // Jika ada opsi lain yang dipilih, pastikan "Pilih Semua" tidak terpilih
                const selectAllOption = Array.from(this.options).find(option => option.value === 'all');
                if (selectAllOption) {
                    selectAllOption.selected = false;
                }
            }
        });

        // Initialize Choices.js for dropdowns
        new Choices('select[name="pimpinan_rapat"]', {
            searchEnabled: true
        });
        new Choices('select[name="notulen"]', {
            searchEnabled: true
        });
        new Choices('select[name="yang_terundang[]"]', {
            searchEnabled: true,
            removeItemButton: true
        });

        // Tanggal Akhir validation
        const mulaiInput = document.querySelector('input[name="mulai"]');
        const akhirInput = document.querySelector('input[name="akhir"]');

        akhirInput.addEventListener('change', function() {
            const mulaiDate = new Date(mulaiInput.value);
            const akhirDate = new Date(akhirInput.value);

            if (akhirDate < mulaiDate) {
                alert('Tanggal Akhir tidak boleh lebih awal dari Tanggal Mulai!');
                akhirInput.value = ''; // Reset input akhir jika invalid
            }
        });

        // Validasi dan preview foto
        const fotoInput = document.getElementById('foto');
        const fotoPreview = document.getElementById('foto-preview');
        const fotoPreviewImg = document.getElementById('foto-preview-img');
        const fotoError = document.getElementById('foto-error');
        const maxFotoSize = 2 * 1024 * 1024; // 2MB

        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            fotoError.style.display = 'none';
            fotoError.textContent = '';

            if (file) {
                // Validasi ukuran file
                if (file.size > maxFotoSize) {
                    fotoError.textContent = 'Ukuran file terlalu besar! Maksimal 2MB.';
                    fotoError.style.display = 'block';
                    fotoInput.value = '';
                    fotoPreview.style.display = 'none';
                    return;
                }

                // Validasi tipe file
                if (!file.type.match('image.*')) {
                    fotoError.textContent = 'File harus berupa gambar!';
                    fotoError.style.display = 'block';
                    fotoInput.value = '';
                    fotoPreview.style.display = 'none';
                    return;
                }

                // Preview gambar
                const reader = new FileReader();
                reader.onload = function(e) {
                    fotoPreviewImg.src = e.target.result;
                    fotoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                fotoPreview.style.display = 'none';
            }
        });

        // Validasi materi
        const materiInput = document.getElementById('materi');
        const materiError = document.getElementById('materi-error');
        const maxMateriSize = 2 * 1024 * 1024; // 2MB

        materiInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            materiError.style.display = 'none';
            materiError.textContent = '';

            if (file) {
                // Validasi ukuran file
                if (file.size > maxMateriSize) {
                    materiError.textContent = 'Ukuran file terlalu besar! Maksimal 2MB.';
                    materiError.style.display = 'block';
                    materiInput.value = '';
                    return;
                }

                // Validasi tipe file
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    materiError.textContent = 'File harus berupa PDF, DOC, atau DOCX!';
                    materiError.style.display = 'block';
                    materiInput.value = '';
                    return;
                }
            }
        });

        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const foto = fotoInput.files[0];
            const materi = materiInput.files[0];

            if (foto && foto.size > maxFotoSize) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ukuran foto terlalu besar! Maksimal 2MB.'
                });
                return false;
            }

            if (materi && materi.size > maxMateriSize) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ukuran materi terlalu besar! Maksimal 2MB.'
                });
                return false;
            }
        });
    });

    function removeFotoPreview() {
        document.getElementById('foto').value = '';
        document.getElementById('foto-preview').style.display = 'none';
        document.getElementById('foto-preview-img').src = '';
    }

    // Toggle dropdown surat keluar
    document.getElementById('is_realisasi_surat')?.addEventListener('change', function() {
        const suratKeluarSelect = document.getElementById('surat_keluar_select');
        if (suratKeluarSelect) {
            suratKeluarSelect.style.display = this.checked ? 'block' : 'none';
            if (!this.checked) {
                document.getElementById('id_surat_keluar').value = '';
            }
        }
    });
</script>
@endsection
