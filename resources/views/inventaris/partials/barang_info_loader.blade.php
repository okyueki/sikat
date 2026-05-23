var inventarisBarangInfoUrl = @json(route('inventaris.barang-info'));

function loadInventarisBarangInfo(kodeBarang) {
    var produsenField = $('#produsen');
    var merkField = $('#merk');
    if (!kodeBarang) {
        produsenField.val('—');
        merkField.val('—');
        return;
    }
    produsenField.prop('disabled', true).val('Memuat...');
    merkField.prop('disabled', true).val('Memuat...');
    $.ajax({
        url: inventarisBarangInfoUrl,
        type: 'GET',
        data: { kode_barang: kodeBarang },
        dataType: 'json',
        timeout: 10000,
        success: function(data) {
            if (data && data.produsen !== undefined && data.merk !== undefined) {
                produsenField.val(data.produsen || 'Tidak Diketahui');
                merkField.val(data.merk || 'Tidak Diketahui');
            } else {
                produsenField.val('Tidak Diketahui');
                merkField.val('Tidak Diketahui');
            }
        },
        error: function(xhr, status) {
            produsenField.val('Tidak Diketahui');
            merkField.val('Tidak Diketahui');
            if (status === 'timeout') {
                alert('Waktu koneksi habis. Silakan coba lagi.');
            } else if (xhr.status !== 404) {
                alert('Gagal memuat data barang. Silakan coba lagi.');
            }
        },
        complete: function() {
            produsenField.prop('disabled', false);
            merkField.prop('disabled', false);
        }
    });
}
