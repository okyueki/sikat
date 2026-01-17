<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusKesimpulanSuratToAgendasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->enum('status_acara', ['draft', 'akan_datang', 'sedang_berlangsung', 'selesai'])
                ->default('draft')->after('akhir');
            $table->text('kesimpulan_notulen')->nullable()->after('keterangan');
            $table->datetime('tanggal_selesai_notulen')->nullable()->after('kesimpulan_notulen');
            $table->string('created_by', 20)->nullable()->after('tanggal_selesai_notulen');
            $table->unsignedBigInteger('id_surat_keluar')->nullable()->after('created_by');
            $table->enum('status_realisasi', ['belum', 'sedang', 'selesai'])
                ->default('belum')->after('id_surat_keluar');
            
            $table->foreign('id_surat_keluar')
                ->references('id_surat')->on('surat')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropForeign(['id_surat_keluar']);
            $table->dropColumn([
                'status_acara',
                'kesimpulan_notulen',
                'tanggal_selesai_notulen',
                'created_by',
                'id_surat_keluar',
                'status_realisasi'
            ]);
        });
    }
}
