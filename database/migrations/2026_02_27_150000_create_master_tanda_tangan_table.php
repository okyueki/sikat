<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterTandaTanganTable extends Migration
{
    public function up()
    {
        Schema::create('master_tanda_tangan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Pemilik, per user');
            $table->string('nama_lengkap');
            $table->string('inisial', 20)->nullable();
            $table->string('font_style', 50)->default('dancing_script')->comment('dancing_script, pacifico, great_vibes, sacramento');
            $table->string('color', 20)->default('#000000');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('master_tanda_tangan');
    }
}
