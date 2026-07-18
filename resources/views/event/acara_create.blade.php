@extends('layouts.pages-layouts')

@section('pageTitle', 'Tambah Agenda Baru')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <h2 class="mb-4">Tambah Agenda Baru</h2>
                <form action="{{ route('acara_store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    {{-- Preview Nomor Agenda --}}
                    <div class="alert alert-info mb-3">
                        <strong>Nomor Agenda (Preview):</strong>
                        <div id="nomor-agenda-preview" class="mt-2">
                            <code class="text-primary" style="font-size: 14px;">RSASF/---/III.6.AU/---/--/----</code>
                            <small class="text-muted d-block mt-1">Nomor akan otomatis di-generate setelah form disubmit</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Agenda</label>
                        <select name="jenis_agenda" id="jenis_agenda" class="form-control">
                            <option value="umum">Umum</option>
                            <option value="kajian">Kajian</option>
                            <option value="kegiatan_rs">Kegiatan RS</option>
                            <option value="iht">IHT/EHT</option>
                        </select>
                        <small class="text-muted">Pilih jenis agenda untuk memungkinkan sistem menghitung nilai penilaian individu secara otomatis</small>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Mulai</label>
                        <input type="datetime-local" name="mulai" id="mulai" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Akhir</label>
                        <input type="datetime-local" name="akhir" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Tempat</label>
                        <input type="text" name="tempat" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Pimpinan Rapat</label>
                        <select name="pimpinan_rapat" class="form-control" required>
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nik }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Notulen</label>
                        <select name="notulen" class="form-control" required>
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nik }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Yang Terundang</label>
                        <select name="yang_terundang[]" id="yang_terundang" class="form-control" multiple required>
                            <option value="all">Pilih Semua</option> <!-- Opsi Pilih Semua -->
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nik }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kepada (teks undangan PDF)</label>
                        <textarea name="kepada_undangan" class="form-control" rows="4" placeholder="Satu baris = satu baris di surat. Baris pertama tanpa indent, baris berikutnya menjorok.">{{ old('kepada_undangan', \App\Models\Agenda::defaultKepadaUndangan()) }}</textarea>
                        <small class="text-muted">Teks bagian &quot;Kepada Yth.&quot; di PDF undangan. Kosongkan untuk pakai teks standar lampiran.</small>
                    </div>
                    
                    <!--    
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                        <small class="text-muted">Maksimal 2MB. Format: JPG, JPEG, PNG</small>
                        <div id="foto-preview" class="mt-2" style="display: none;">
                            <p>Preview:</p>
                            <img id="foto-preview-img" src="" alt="Preview Foto" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeFotoPreview()">Hapus Preview</button>
                        </div>
                        <div id="foto-error" class="text-danger mt-1" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label>Materi Acara <span class="text-muted">(Opsional - bisa ditambahkan lagi setelah acara dibuat)</span></label>
                        <input type="file" name="materi[]" id="materi" class="form-control" accept=".pdf,.doc,.docx" multiple>
                        <small class="text-muted">Maksimal 2MB per file. Format: PDF, DOC, DOCX. Bisa pilih beberapa file sekaligus.</small>
                        <div id="materi-list" class="mt-2"></div>
                        <div id="materi-error" class="text-danger mt-1" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_realisasi_surat" 
                                   name="is_realisasi_surat" value="1">
                            <label class="form-check-label" for="is_realisasi_surat">
                                <strong>Ini realisasi dari Surat Keluar</strong>
                            </label>
                        </div>
                    -->   
                        <div id="surat_keluar_select" style="display: none;">
                            <label for="id_surat_keluar">Pilih Surat Keluar</label>
                            <select name="id_surat_keluar" id="id_surat_keluar" class="form-control select2">
                                <option value="">-- Pilih Surat Keluar --</option>
                                @foreach($suratKeluar ?? [] as $sk)
                                    <option value="{{ $sk->id_surat }}">
                                        {{ $sk->nomor_surat }} | {{ $sk->perihal }} 
                                        ({{ \Carbon\Carbon::parse($sk->tanggal_surat)->format('d M Y') }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hanya menampilkan surat keluar yang sudah disetujui dan belum ada realisasi</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for both date fields with 24-hour format
        flatpickr('input[name="mulai"]', {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });
        flatpickr('input[name="akhir"]', {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });

        // Preview nomor agenda
        function updateNomorAgendaPreview() {
            const jenisAgenda = document.getElementById('jenis_agenda')?.value || 'umum';
            const mulaiInput = document.getElementById('mulai')?.value;
            const previewElement = document.getElementById('nomor-agenda-preview');
            
            if (!previewElement) return;
            
            if (mulaiInput) {
                const date = new Date(mulaiInput);
                const bulan = String(date.getMonth() + 1).padStart(2, '0');
                const tahun = date.getFullYear();
                
                // Format: RSASF/XXX/III.6.AU/jenis/bulan/tahun
                // Nomor urut akan di-generate di backend, jadi kita tampilkan format dengan ---
                const preview = `RSASF/---/III.6.AU/${jenisAgenda}/${bulan}/${tahun}`;
                previewElement.innerHTML = `
                    <code class="text-primary" style="font-size: 14px;">${preview}</code>
                    <small class="text-muted d-block mt-1">Nomor akan otomatis di-generate setelah form disubmit</small>
                `;
            } else {
                previewElement.innerHTML = `
                    <code class="text-primary" style="font-size: 14px;">RSASF/---/III.6.AU/---/--/----</code>
                    <small class="text-muted d-block mt-1">Isi tanggal mulai dan pilih jenis agenda untuk preview</small>
                `;
            }
        }
        
        // Event listener untuk update preview
        document.getElementById('jenis_agenda')?.addEventListener('change', updateNomorAgendaPreview);
        document.getElementById('mulai')?.addEventListener('change', updateNomorAgendaPreview);
        
        // Initial preview
        updateNomorAgendaPreview();

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
            const materiFiles = Array.from(materiInput.files);

            if (foto && foto.size > maxFotoSize) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ukuran foto terlalu besar! Maksimal 2MB.'
                });
                return false;
            }

            // Validasi semua file materi
            for (let i = 0; i < materiFiles.length; i++) {
                if (materiFiles[i].size > maxMateriSize) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `File "${materiFiles[i].name}" terlalu besar! Maksimal 2MB per file.`
                    });
                    return false;
                }
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
            if (this.checked) {
                // Initialize Select2 when element is shown (with small delay to ensure element is visible)
                setTimeout(function() {
                    $('#id_surat_keluar').select2({
                        placeholder: '-- Pilih Surat Keluar --',
                        allowClear: true,
                        width: '100%',
                        language: {
                            noResults: function() {
                                return "Tidak ada hasil ditemukan";
                            },
                            searching: function() {
                                return "Mencari...";
                            }
                        }
                    });
                }, 100);
            } else {
                // Destroy Select2 and reset value when hidden
                if ($('#id_surat_keluar').hasClass('select2-hidden-accessible')) {
                    $('#id_surat_keluar').val('').trigger('change');
                    $('#id_surat_keluar').select2('destroy');
                } else {
                    $('#id_surat_keluar').val('');
                }
            }
        }
    });
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
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
</style>

@endsection