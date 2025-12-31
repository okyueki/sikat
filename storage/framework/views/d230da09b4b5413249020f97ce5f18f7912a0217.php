<?php $__env->startSection('pageTitle', isset($pageTitle) ? $pageTitle . $title :  $title); ?>

<?php $__env->startSection('content'); ?>
        <!-- end page title -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#home" role="tab">
                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                    <span class="d-none d-sm-block">Detail Pengajuan Libur</span>    
                                </a>
                             </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab">
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">PDF</span>    
                                </a>
                            </li>
                            
                                           
                        </ul>
                        <!-- Tab panes -->
                        <div class="tab-content p-3 text-muted">
                            <div class="tab-pane active" id="home" role="tabpanel">
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Tanggal Lembur</div>
                                   <?php echo e($pengajuanlembur->tanggal_lembur); ?>

                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Nama Pegawai</div>
                                    <?php echo e($pengajuanlembur->pegawai->nama); ?>

                                    </div>
                                   
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Keterangan</div>
                                    <?php echo e($pengajuanlembur->keterangan); ?>

                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Tanggal Dibuat</div>
                                    <?php echo e($pengajuanlembur->tanggal_dibuat); ?>

                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                    <div class="fw-bold">Status</div>
                                    <span style="font-size: 14px;" class="badge 
                                        <?php if($pengajuanlembur->status == 'Dikirim'): ?> bg-warning 
                                        <?php elseif($pengajuanlembur->status == 'Disetujui'): ?> bg-success 
                                        <?php elseif($pengajuanlembur->status == 'Ditolak'): ?> bg-danger 
                                        <?php endif; ?>">
                                        <?php echo e($pengajuanlembur->status); ?>

                                    </span>
                                    </div>
                                </li>
                            </ol>
                            <?php if($errors->any()): ?>
                                <div class="alert alert-danger">
                                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                                    <ul>
                                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($error); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <form action="<?php echo e(route('verifikasi_pengajuan_lembur.update', $pengajuanlembur->id_pengajuan_lembur)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PUT'); ?>

                                <div class="form-group mb-3">
                                    <label for="status">Status</label>
                                    <select name="status" id="status" class="form-control">
                                    <option value="">-- Select Status --</option>
                                    <?php $__currentLoopData = ['Dikirim', 'Disetujui', 'Ditolak']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($status); ?>" <?php echo e((old('status', $pengajuanlembur->status ?? '') == $status) ? 'selected' : ''); ?>>
                                            <?php echo e($status); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="nama_klasifikasi_surat">Catatan</label>
                                    <textarea name="catatan" id="catatan" class="form-control"><?php echo e($pengajuanlembur->catatan); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-success waves-effect waves-light">Verifikasi</button>
                            </form>
                           
                            </div>
                            <div class="tab-pane" id="profile" role="tabpanel">
                            <object data="<?php echo e($pdfUrl); ?>" type="application/pdf" width="100%" height="600px">
                                <!-- Pesan fallback jika PDF tidak dapat dimuat -->
                                <p>Your browser does not support PDFs. Please download the PDF to view it: <a href="<?php echo e($pdfUrl); ?>">Download PDF</a>.</p>
                            </object>                    
                            </div>
                        </div>                                  
                    </div>
                </div>
            </div>
        </div>

<!-- End Page-content -->
<script>
     document.addEventListener('DOMContentLoaded', function () {
            const element = document.getElementById('status');
            const choices = new Choices(element, {
                placeholderValue: 'Select Status...',
                searchEnabled: true,
                position: 'top', // Menampilkan dropdown di bawah elemen
                shouldSort: false, // Menghindari pengurutan jika tidak diperlukan
            });

        });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.pages-layouts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/sikat.rsaisyiyahsitifatimah.com/resources/views/verifikasi_pengajuan_lembur/detail.blade.php ENDPATH**/ ?>