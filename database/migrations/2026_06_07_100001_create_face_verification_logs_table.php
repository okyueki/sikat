<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaceVerificationLogsTable extends Migration
{
    public function up()
    {
        Schema::create('face_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pegawai_id');
            $table->string('nik', 50);
            $table->string('tipe', 10);
            $table->string('status', 20);
            $table->decimal('score', 5, 4)->nullable();
            $table->dateTime('jam_datang')->nullable();
            $table->string('shift', 50)->nullable();
            $table->text('insightface_response')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('pegawai_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('face_verification_logs');
    }
}
