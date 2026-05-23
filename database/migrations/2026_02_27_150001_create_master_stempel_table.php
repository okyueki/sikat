<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterStempelTable extends Migration
{
    public function up()
    {
        Schema::create('master_stempel', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable()->comment('Nama stempel perusahaan');
            $table->string('file_path')->nullable()->comment('Path gambar stempel (PNG/SVG)');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('master_stempel');
    }
}
