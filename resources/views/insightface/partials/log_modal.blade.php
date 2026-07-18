<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logDetailModalLabel">Detail Log Verifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailLoading" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div> Memuat...
                </div>
                <div id="logDetailContent" class="d-none">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Nama</dt><dd class="col-sm-9" id="ld-nama"></dd>
                        <dt class="col-sm-3">NIK</dt><dd class="col-sm-9" id="ld-nik"></dd>
                        <dt class="col-sm-3">Tipe</dt><dd class="col-sm-9" id="ld-tipe"></dd>
                        <dt class="col-sm-3">Status</dt><dd class="col-sm-9" id="ld-status"></dd>
                        <dt class="col-sm-3">Skor</dt><dd class="col-sm-9" id="ld-score"></dd>
                        <dt class="col-sm-3">Shift</dt><dd class="col-sm-9" id="ld-shift"></dd>
                        <dt class="col-sm-3">Waktu</dt><dd class="col-sm-9" id="ld-created"></dd>
                    </dl>
                    <label class="form-label">Response InsightFace</label>
                    <pre id="ld-raw" class="bg-light p-3 rounded small mb-0" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
