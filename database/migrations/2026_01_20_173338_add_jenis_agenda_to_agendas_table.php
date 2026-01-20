<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJenisAgendaToAgendasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Cek dulu apakah kolom sudah ada, jika belum baru tambahkan
            if (!Schema::hasColumn('agendas', 'jenis_agenda')) {
                $table->enum('jenis_agenda', ['umum', 'kajian', 'kegiatan_rs', 'iht'])->default('umum')->after('judul');
            }
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
            // Cek dulu apakah kolom ada sebelum dihapus
            if (Schema::hasColumn('agendas', 'jenis_agenda')) {
                $table->dropColumn('jenis_agenda');
            }
        });
    }
}
