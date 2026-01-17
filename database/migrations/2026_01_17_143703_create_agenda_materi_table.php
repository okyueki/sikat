<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgendaMateriTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agenda_materi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('agenda_id');
            $table->string('nama_file', 255);
            $table->string('path_file', 255);
            $table->integer('ukuran_file');
            $table->string('tipe_file', 50);
            $table->string('diupload_oleh', 20);
            $table->datetime('diupload_pada');
            $table->text('keterangan')->nullable();
            $table->enum('jenis', ['materi', 'dokumentasi'])->default('materi');
            $table->timestamps();

            $table->foreign('agenda_id')
                ->references('id')->on('agendas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agenda_materi');
    }
}
