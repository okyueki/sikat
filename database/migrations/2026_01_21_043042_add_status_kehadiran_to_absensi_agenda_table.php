<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusKehadiranToAbsensiAgendaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('absensi_agenda', function (Blueprint $table) {
            $table->enum('status_kehadiran', ['hadir', 'ijin', 'cuti', 'sakit', 'berhalangan', 'tidak_hadir'])
                  ->default('hadir')
                  ->after('waktu_kehadiran');
            $table->text('alasan')->nullable()->after('status_kehadiran');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('absensi_agenda', function (Blueprint $table) {
            $table->dropColumn(['status_kehadiran', 'alasan']);
        });
    }
}
