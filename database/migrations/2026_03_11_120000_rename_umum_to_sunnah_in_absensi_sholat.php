<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameUmumToSunnahInAbsensiSholat extends Migration
{
    /**
     * Run the migrations.
     * Ubah nilai jenis_sholat 'umum' menjadi 'sunnah'.
     */
    public function up()
    {
        DB::table('absensi_sholat')
            ->where('jenis_sholat', 'umum')
            ->update(['jenis_sholat' => 'sunnah']);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('absensi_sholat')
            ->where('jenis_sholat', 'sunnah')
            ->update(['jenis_sholat' => 'umum']);
    }
}
