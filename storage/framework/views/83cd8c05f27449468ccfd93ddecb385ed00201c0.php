<?php $__env->startSection('pageTitle', 'Respon Ticket - ' . $ticket->no_tiket); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <h4><?php echo e(isset($responKerja) ? 'Update Respon untuk' : 'Tambah Respon untuk'); ?> Ticket: <?php echo e($ticket->no_tiket); ?></h4>
                </div>
            </div>
                <div class="card-body">
                <!-- Tambahkan navigasi tab -->
                <ul class="nav nav-tabs" id="responTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="respon-tab" data-bs-toggle="tab" href="#respon" role="tab" aria-controls="respon" aria-selected="true">Respon</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="komentar-tab" data-bs-toggle="tab" href="#komentar" role="tab" aria-controls="komentar" aria-selected="false">Komentar</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="teknisi-tab" data-bs-toggle="tab" href="#teknisi" role="tab" aria-controls="teknisi" aria-selected="false">Teknisi</a>
                    </li>
                </ul>
                <div class="tab-content" id="responTabContent">
                    <!-- Tab Respon -->
                    <div class="tab-content" id="responTabContent">
                        <!-- Tab Respon -->
                        <div class="tab-pane fade show active" id="respon" role="tabpanel" aria-labelledby="respon-tab">
                            <form action="<?php echo e(isset($responKerja) ? route('responKerja.update', $responKerja->id) : route('responKerja.store', $ticket->id)); ?>" method="POST" enctype="multipart/form-data">
                                <?php echo csrf_field(); ?>
                                <?php if(isset($responKerja)): ?>
                                    <?php echo method_field('PUT'); ?> <!-- Tambahkan method PUT untuk update -->
                                <?php endif; ?>
                    
                                <!-- Teknisi Dropdown -->
                                <div class="mb-3">
                                    <label for="teknisi_id" class="form-label">Teknisi</label>
                                    <select name="teknisi_id" class="form-select" required>
                                        <option value="" disabled <?php echo e(!isset($responKerja) ? 'selected' : ''); ?>>Pilih Teknisi</option>
                                        <?php $__currentLoopData = $pegawai; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teknisi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($teknisi->nik); ?>" <?php echo e(isset($responKerja) && $responKerja->teknisi_id == $teknisi->nik ? 'selected' : ''); ?>>
                                                <?php echo e($teknisi->nama); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                    
                                <!-- Deskripsi Hasil -->
                                <div class="mb-3">
                                    <label for="deskripsi_hasil" class="form-label">Deskripsi Hasil</label>
                                    <textarea name="deskripsi_hasil" class="form-control" rows="5" required><?php echo e($responKerja->deskripsi_hasil ?? ''); ?></textarea>
                                </div>
                    
                                <!-- Status Akhir Respon -->
                                <div class="mb-3">
                                    <label for="status_akhir" class="form-label">Status Akhir</label>
                                    <select name="status_akhir" class="form-select" required>
                                        <option value="selesai" <?php echo e(isset($responKerja) && $responKerja->status_akhir == 'selesai' ? 'selected' : ''); ?>>Selesai</option>
                                        <option value="minta bantuan pihak ketiga" <?php echo e(isset($responKerja) && $responKerja->status_akhir == 'minta bantuan pihak ketiga' ? 'selected' : ''); ?>>Minta Bantuan Pihak Ketiga</option>
                                        <option value="lanjut" <?php echo e(isset($responKerja) && $responKerja->status_akhir == 'lanjut' ? 'selected' : ''); ?>>Lanjut</option>
                                    </select>
                                </div>
                    
                                <!-- Tingkat Kesulitan -->
                                <div class="mb-3">
                                    <label for="tingkat_kesulitan" class="form-label">Tingkat Kesulitan</label>
                                    <select name="tingkat_kesulitan" class="form-select" required>
                                        <option value="mudah" <?php echo e(isset($responKerja) && $responKerja->tingkat_kesulitan == 'mudah' ? 'selected' : ''); ?>>Mudah</option>
                                        <option value="sedang" <?php echo e(isset($responKerja) && $responKerja->tingkat_kesulitan == 'sedang' ? 'selected' : ''); ?>>Sedang</option>
                                        <option value="sulit" <?php echo e(isset($responKerja) && $responKerja->tingkat_kesulitan == 'sulit' ? 'selected' : ''); ?>>Sulit</option>
                                    </select>
                                </div>
                    
                                <!-- Biaya -->
                                <div class="mb-3">
                                    <label for="biaya" class="form-label">Biaya (Opsional)</label>
                                    <input type="number" name="biaya" step="0.01" class="form-control" value="<?php echo e($responKerja->biaya ?? ''); ?>">
                                </div>
                    
                                <!-- Petunjuk Penyelesaian -->
                                <div class="mb-3">
                                    <label for="petunjuk_penyelesaian" class="form-label">Petunjuk Penyelesaian (Opsional)</label>
                                    <textarea name="petunjuk_penyelesaian" class="form-control" rows="3"><?php echo e($responKerja->petunjuk_penyelesaian ?? ''); ?></textarea>
                                </div>
                    
                                <!-- Foto Hasil -->
                                <div class="mb-3">
                                    <label for="foto_hasil" class="form-label">Upload Foto Hasil (Opsional)</label>
                                    <input type="file" name="foto_hasil" class="form-control">
                                </div>
                    
                                <!-- Status Tiket -->
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status Tiket</label>
                                    <select name="status" class="form-select" required>
                                        <option value="open" <?php echo e($ticket->status == 'open' ? 'selected' : ''); ?>>Open</option>
                                        <option value="in progress" <?php echo e($ticket->status == 'in progress' ? 'selected' : ''); ?>>In Progress</option>
                                        <option value="in review" <?php echo e($ticket->status == 'in review' ? 'selected' : ''); ?>>In Review</option>
                                        <option value="close" <?php echo e($ticket->status == 'close' ? 'selected' : ''); ?>>Close</option>
                                        <option value="pending" <?php echo e($ticket->status == 'pending' ? 'selected' : ''); ?>>Pending</option>
                                        <option value="di jadwalkan" <?php echo e($ticket->status == 'di jadwalkan' ? 'selected' : ''); ?>>Di Jadwalkan</option>
                                    </select>
                                </div>
                    
                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-success"><?php echo e(isset($responKerja) ? 'Perbarui Respon' : 'Simpan Respon'); ?></button>
                            </form>
                        </div>
                    </div>
                    <!-- Tab Komentar -->
                    <div class="tab-pane fade" id="komentar" role="tabpanel" aria-labelledby="komentar-tab">
                        <!-- Form untuk menambahkan komentar -->
                        <form action="<?php echo e(route('komentar.store', $ticket->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="komentar" class="form-label">Komentar</label>
                                <textarea name="komentar" class="form-control" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Tambahkan Komentar</button>
                        </form>
    
                        <hr>
    
                        <!-- Menampilkan daftar komentar yang ada -->
                        <h5>Daftar Komentar</h5>
                        <?php if($ticket->komentar->isEmpty()): ?>
                            <p>Belum ada komentar untuk tiket ini.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php $__currentLoopData = $ticket->komentar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $komentar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="list-group-item">
                                        <strong><?php echo e($komentar->email); ?>:</strong> <br>
                                        <?php echo e($komentar->komentar); ?>

                                        <br>
                                        <small class="text-muted">Ditambahkan pada: <?php echo e($komentar->created_at->format('d-m-Y H:i')); ?></small>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="teknisi" role="tabpanel" aria-labelledby="teknisi-tab">
                        <!-- Form untuk menambahkan teknisi tambahan -->
                        <form action="<?php echo e(route('teknisi.store', $ticket->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="teknisi_id" class="form-label">Pilih Teknisi</label>
                                <select name="teknisi_id" class="form-select" required>
                                    <option value="" disabled selected>Pilih Teknisi</option>
                                    <?php $__currentLoopData = $pegawai; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teknisi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($teknisi->nik); ?>"><?php echo e($teknisi->nama); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Tambahkan Teknisi</button>
                        </form>
    
                        <hr>
    
                        <!-- Menampilkan daftar teknisi tambahan yang ada -->
                        <h5>Daftar Teknisi Tambahan</h5>
                        <?php if($ticket->teknisiMenangani->isEmpty()): ?>
                            <p>Belum ada teknisi tambahan untuk tiket ini.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php $__currentLoopData = $ticket->teknisiMenangani; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teknisi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="list-group-item">
                                        <strong><?php echo e($teknisi->pengguna->nama); ?>:</strong>
                                        <small class="text-muted">Ditambahkan pada: <?php echo e($teknisi->created_at->format('d-m-Y H:i')); ?></small>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo e(route('helpdesk.dashboard')); ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
    <div class="card custom-card collapse-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                Ticket Details
            </div>
            <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#ticketDetails" aria-expanded="false" aria-controls="ticketDetails">
                <i class="ri-arrow-down-s-line fs-18 collapse-open"></i>
                <i class="ri-arrow-up-s-line collapse-close fs-18"></i>
            </a>
        </div>
        <div class="collapse show" id="ticketDetails">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>No Tiket:</strong> <?php echo e($ticket->no_tiket); ?></p>
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
                        <p class="mb-2">
                        <strong>Status:</strong>
                        <?php if($ticket->status == 'open'): ?>
                            <span class="badge bg-danger"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php elseif($ticket->status == 'in progress'): ?>
                            <span class="badge bg-warning text-dark"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php elseif($ticket->status == 'in review'): ?>
                            <span class="badge bg-primary"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php elseif($ticket->status == 'close'): ?>
                            <span class="badge bg-light text-dark"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php elseif($ticket->status == 'pending'): ?>
                            <span class="badge bg-secondary"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php elseif($ticket->status == 'di jadwalkan'): ?>
                            <span class="badge bg-dark"><?php echo e(ucfirst($ticket->status)); ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Unknown</span>
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
                    <hr>
                    <p><strong>Upload:</strong> 
                        <a href="<?php echo e(asset('storage/uploads/' . $ticket->upload)); ?>" target="_blank">Lihat Lampiran</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

                
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.pages-layouts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /www/wwwroot/sikat.rsaisyiyahsitifatimah.com/resources/views/helpdesk/respon_helpdesk.blade.php ENDPATH**/ ?>