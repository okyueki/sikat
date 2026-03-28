<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsensiSholatTable extends Migration
{
    /**
     * Run the migrations.
     * Connection default (bukan server_74). Satu record per nik + tanggal + jenis_sholat.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('absensi_sholat', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->index();
            $table->timestamp('waktu_absen');
            $table->string('jenis_sholat', 20)->nullable(); // subuh, dzuhur, ashar, maghrib, isya, umum
            $table->string('token_used', 64)->nullable();
            $table->timestamps();

            $table->index(['nik', 'waktu_absen', 'jenis_sholat']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('absensi_sholat');
    }
}
