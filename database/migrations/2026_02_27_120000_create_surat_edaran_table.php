<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratEdaranTable extends Migration
{
    public function up()
    {
        Schema::create('surat_edaran', function (Blueprint $table) {
            $table->id();
            $table->string('judul_surat');
            $table->string('nomor_surat')->nullable();
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->string('nik_penandatangan')->nullable()->comment('NIK pegawai yang menyetujui / tanda tangan');
            $table->string('file_pdf')->nullable()->comment('Path relatif file PDF di storage');
            $table->string('file_pdf_signed')->nullable()->comment('Path PDF yang sudah ditandatangani');
            $table->json('signature_detail')->nullable()->comment('Nama lengkap, inisial, font_style, color untuk tampilan tanda tangan');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_edaran');
    }
}
