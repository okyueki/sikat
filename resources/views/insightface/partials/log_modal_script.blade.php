<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('logDetailModal');
    if (!modalEl) return;

    var urlTemplate = @json(route('insightface.log_detail', ['logId' => '__ID__']));

    document.querySelectorAll('.log-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var logId = this.getAttribute('data-log-id');
            var loading = document.getElementById('logDetailLoading');
            var content = document.getElementById('logDetailContent');
            loading.classList.remove('d-none');
            content.classList.add('d-none');

            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            fetch(urlTemplate.replace('__ID__', logId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                loading.classList.add('d-none');
                content.classList.remove('d-none');
                if (!json.success || !json.data) return;
                var d = json.data;
                document.getElementById('ld-nama').textContent = d.nama || '-';
                document.getElementById('ld-nik').textContent = d.nik || '-';
                document.getElementById('ld-tipe').textContent = d.tipe || '-';
                document.getElementById('ld-status').textContent = d.status || '-';
                document.getElementById('ld-score').textContent = d.score != null ? d.score : '-';
                document.getElementById('ld-shift').textContent = d.shift || '-';
                document.getElementById('ld-created').textContent = d.created_at || '-';
                document.getElementById('ld-raw').textContent = d.insightface_response || '(kosong)';
            })
            .catch(function () {
                loading.classList.add('d-none');
                content.classList.remove('d-none');
                document.getElementById('ld-raw').textContent = 'Gagal memuat detail log.';
            });
        });
    });
});
</script>
