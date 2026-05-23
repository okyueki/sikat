<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTanggalDitandatanganiToSuratEdaranTable extends Migration
{
    public function up()
    {
        Schema::table('surat_edaran', function (Blueprint $table) {
            $table->dateTime('tanggal_ditandatangani')->nullable()->after('signature_detail')->comment('Waktu simpan posisi tanda tangan = dokumen sah');
        });
    }

    public function down()
    {
        Schema::table('surat_edaran', function (Blueprint $table) {
            $table->dropColumn('tanggal_ditandatangani');
        });
    }
}
