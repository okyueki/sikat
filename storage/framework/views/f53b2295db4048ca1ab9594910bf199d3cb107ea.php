<?php $__env->startSection('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title); ?>

<?php $__env->startSection('content'); ?>
<style>
.flatpickr-time {
    min-width: 130px !important;
    padding: 18px 10px !important;
    text-align: center;
        max-height: 75px !important;
}
</style>
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('pengajuan_lembur.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="jumlah_hari" id="jumlah_hari" class="form-control" value="<?php echo e(old('jumlah_hari')); ?>">

                    <!-- Input Tanggal Awal -->
                    <div class="form-group mb-3">
                        <label for="tanggal_awal">Tanggal Awal: </label>
                        <div class="input-group">
                            <input type="text" name="tanggal_awal" id="tanggal_awal" class="form-control flatpickr" value="<?php echo e(old('tanggal_awal')); ?>">
                            <div class="input-group-text"><i class="fas fa-calendar-alt"></i></div>
                        </div>
                    </div>

                    <!-- Input Tanggal Akhir -->
                    <div class="form-group mb-3">
                        <label for="tanggal_akhir">Tanggal Akhir: </label>
                        <div class="input-group">
                            <input type="text" name="tanggal_akhir" id="tanggal_akhir" class="form-control flatpickr" value="<?php echo e(old('tanggal_akhir')); ?>">
                            <div class="input-group-text"><i class="fas fa-calendar-alt"></i></div>
                        </div>
                    </div>

                    <!-- Input Jam Lembur -->
                    <div class="form-group mb-3">
                        <label for="jam_lembur">Jam Lembur: </label>
                        <div class="row">
                            <div class="col-5 mb-3">
                                <div class="input-group">
                                    <input type="text" name="jam_awal" id="jam_awal" class="form-control flatpickr-time" value="<?php echo e(old('jam_awal')); ?>">
                                    <div class="input-group-text"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                            <div class="col-5 mb-3">
                                <div class="input-group">
                                    <input type="text" name="jam_akhir" id="jam_akhir" class="form-control flatpickr-time" value="<?php echo e(old('jam_akhir')); ?>">
                                    <div class="input-group-text"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                            <div class="col-2 mb-3">
                                <span id="jumlah_jam_badge" style="font-size: 24px;" class="badge bg-success"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Keterangan -->
                    <div class="form-group mb-3">
                        <label for="keterangan">Keterangan:</label>
                        <input type="text" name="keterangan" id="keterangan" class="form-control" value="<?php echo e(old('keterangan')); ?>">
                    </div>

                    <!-- Pilihan Atasan Langsung -->
                    <div class="form-group mb-3">
                        <label for="nik_atasan_langsung">Atasan Langsung:</label>
                        <select name="nik_atasan_langsung" id="nik_atasan_langsung" class="form-control">
                            <option value="">-- Select Pegawai --</option>
                            <?php $__currentLoopData = $pegawai; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($p->nik); ?>"><?php echo e($p->nama); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success waves-effect waves-light">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize flatpickr for tanggal_awal dan tanggal_akhir
    flatpickr('.flatpickr', {
        dateFormat: "Y-m-d",
        onChange: function () {
            calculateTime();
        }
    });

    // Initialize flatpickr for jam_awal dan jam_akhir (hanya waktu)
    flatpickr('.flatpickr-time', {
        enableTime: true,
        noCalendar: true,
        time_24hr: true,
        dateFormat: "H:i",
        onChange: function () {
            calculateTime();
        }
    });

    function calculateTime() {
        const tanggalAwal = document.getElementById('tanggal_awal').value;
        const tanggalAkhir = document.getElementById('tanggal_akhir').value;
        const jamAwal = document.getElementById('jam_awal').value;
        const jamAkhir = document.getElementById('jam_akhir').value;

        if (tanggalAwal && tanggalAkhir && jamAwal && jamAkhir) {
            const start = new Date(`${tanggalAwal}T${jamAwal}:00`);
            const end = new Date(`${tanggalAkhir}T${jamAkhir}:00`);

            if (end >= start) {
                const timeDiff = end.getTime() - start.getTime();
                const hours = Math.floor(timeDiff / (1000 * 3600));
                const minutes = Math.floor((timeDiff % (1000 * 3600)) / (1000 * 60));

                document.getElementById('jumlah_jam_badge').innerHTML = `${hours} Jam ${minutes} Menit`;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Waktu Salah',
                    text: 'Tanggal Akhir & Jam Akhir harus lebih besar dari Tanggal Awal & Jam Awal!',
                });
                document.getElementById('jam_akhir').value = '';
                document.getElementById('jumlah_jam_badge').innerHTML = '';
            }
        }
    }

    // Choice.js untuk dropdown atasan langsung
    const pegawaiSelect = document.getElementById('nik_atasan_langsung');
    const choices = new Choices(pegawaiSelect, {
        searchEnabled: true,
        itemSelectText: '',
        placeholderValue: 'Select Pegawai',
    });
});
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.pages-layouts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/sikat.rsaisyiyahsitifatimah.com/resources/views/pengajuan_lembur/create.blade.php ENDPATH**/ ?>