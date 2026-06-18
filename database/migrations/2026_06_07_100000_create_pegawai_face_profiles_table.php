<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePegawaiFaceProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('pegawai_face_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pegawai_id');
            $table->string('nik', 50);
            $table->timestamp('enrolled_at');
            $table->timestamps();

            $table->unique('pegawai_id');
            $table->index('nik');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pegawai_face_profiles');
    }
}
