<script>
    $(document).ready(function() {
        $('#suratTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: @json(route('memo_internal.index')),
            columns: [
                { data: null, searchable: false, orderable: false, render: function (data, type, row, meta) {
                    return meta.row + 1;
                }},
                { data: 'nama_pegawai', name: 'nama_pegawai', orderable: false },
                { data: 'perihal', name: 'perihal' },
                { data: 'tanggal_surat', name: 'tanggal_surat' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[3, 'desc']]
        });

        $('#suratTable').on('click', '.deletesurat', function (e) {
            e.preventDefault();
            const form = $(this).closest('form');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda tidak akan dapat mengembalikan ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
