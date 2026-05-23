<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spo', function (Blueprint $table) {
            $table->id();
            $table->string('judul_spo');
            $table->string('nomor_dokumen')->nullable();
            $table->date('tanggal');
            $table->text('deskripsi_singkat')->nullable();
            $table->string('nik_penandatangan', 50)->nullable()->comment('NIK pegawai penandatangan SPO');
            $table->string('petugas_upload_nik', 50)->nullable()->comment('NIK petugas uploader');
            $table->string('departemen_upload_id', 50)->nullable()->comment('dep_id departemen petugas uploader');
            $table->json('dep_terkait_ids')->nullable()->comment('Array dep_id departemen terkait');
            $table->string('file_pdf')->nullable()->comment('Path relatif file PDF di storage');
            $table->string('file_pdf_signed')->nullable()->comment('Path PDF yang sudah ditandatangani');
            $table->json('signature_detail')->nullable()->comment('Nama lengkap, inisial, font_style, color, image');
            $table->string('created_by_username', 100)->nullable();
            $table->dateTime('tanggal_ditandatangani')->nullable()->comment('Waktu dokumen SPO sah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spo');
    }
};
