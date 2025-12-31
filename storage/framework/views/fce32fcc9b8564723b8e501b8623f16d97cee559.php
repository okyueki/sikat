<?php $__env->startSection('pageTitle', 'Edit Jadwal Budaya Kerja'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-body">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Edit Jadwal Budaya Kerja</h3>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo e(route('jadwalbudayakerja.update', $jadwal->id_jadwal_budaya_kerja)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>

                            <div class="form-group mb-3">
                                <label for="nik">Petugas</label>
                                <select name="nik" id="nik" class="form-control">
                                    <option value="">-- Select Pegawai --</option>
                                    <?php $__currentLoopData = $petugas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($p->nip); ?>" <?php echo e($p->nip == $jadwal->nik ? 'selected' : ''); ?>>
                                            <?php echo e($p->nama); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_bertugas" class="form-label">Tanggal Bertugas</label>
                                <input type="date" class="form-control" id="tanggal_bertugas" name="tanggal_bertugas" value="<?php echo e($jadwal->tanggal_bertugas); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="shift" class="form-label">Shift</label>
                                <select class="form-control" id="shift" name="shift" required>
                                    <option value="pagi" <?php echo e($jadwal->shift == 'Pagi' ? 'selected' : ''); ?>>Pagi</option>
                                    <option value="sore" <?php echo e($jadwal->shift == 'Sore' ? 'selected' : ''); ?>>Sore</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 text-end">
                                <button type="submit" class="btn btn-primary">Simpan</button>
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
    // Inisialisasi flatpickr untuk tanggal
    flatpickr('#tanggal_bertugas', {
        dateFormat: "Y-m-d",
    });

    // Inisialisasi Choices.js untuk elemen select
    const elements = ['nik', 'shift', 'hari']; // Daftar ID untuk select fields
    elements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            new Choices(element, {
                searchEnabled: true,  // Mengaktifkan fitur pencarian
                position: 'auto',     // Menampilkan dropdown di bawah elemen
                shouldSort: false,    // Menonaktifkan pengurutan otomatis
                allowHTML: true,      // Menambahkan dukungan HTML di dalam opsi
            });
        }
    });
});
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.pages-layouts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/sikat.rsaisyiyahsitifatimah.com/resources/views/jadwal_budaya_kerja/edit.blade.php ENDPATH**/ ?>