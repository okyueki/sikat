<?php $__env->startSection('pageTitle', 'Detail Ticket - ' . $ticket->no_tiket); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="card custom-card">
        <div class="card-header">
            <h3>Detail Ticket: <?php echo e($ticket->no_tiket); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>NIK:</strong> <?php echo e($ticket->nik); ?></p>
                    <p><strong>No Inventaris:</strong> <?php echo e($ticket->no_inventaris); ?></p>
                    <p><strong>Tanggal:</strong> <?php echo e($ticket->tanggal); ?></p>
                    <p><strong>Prioritas:</strong> 
                        <?php if($ticket->prioritas == 'High'): ?>
                            <span class="badge bg-danger">High</span>
                        <?php elseif($ticket->prioritas == 'Medium'): ?>
                            <span class="badge bg-warning">Medium</span>
                        <?php else: ?>
                            <span class="badge bg-success">Low</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Status:</strong> 
                        <?php if($ticket->status == 'Open'): ?>
                            <span class="badge bg-info"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php elseif($ticket->status == 'In Progress'): ?>
                            <span class="badge bg-warning"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Deadline:</strong> <?php echo e($ticket->deadline); ?></p>
                    <p><strong>Judul:</strong> <?php echo e($ticket->judul); ?></p>
                    <p><strong>Departemen:</strong> <?php echo e($ticket->departemen); ?></p>
                    <p><strong>NIK Teknisi:</strong> <?php echo e($ticket->nik_teknisi); ?></p>
                    <p><strong>No HP:</strong> <?php echo e($ticket->no_hp); ?></p>
                    <p><strong>Jenis Permintaan:</strong> <?php echo e($ticket->jenis_permintaan); ?></p>
                </div>
            </div>
            <hr>
            <p><strong>Deskripsi:</strong></p>
            <p><?php echo e($ticket->deskripsi); ?></p>
            <?php if($ticket->upload): ?>
                <p><strong>Upload:</strong> <a href="<?php echo e(asset('storage/uploads/' . $ticket->upload)); ?>" target="_blank">Lihat Lampiran</a></p>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?php echo e(route('helpdesk.dashboard')); ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.pages-layouts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/sikat.rsaisyiyahsitifatimah.com/resources/views/helpdesk/show_helpdesk.blade.php ENDPATH**/ ?>