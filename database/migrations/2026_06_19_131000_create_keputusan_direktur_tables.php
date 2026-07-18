<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keputusan_direktur', function (Blueprint $table) {
            $table->id();
            $table->string('judul_surat');
            $table->string('nomor_surat')->nullable();
            $table->unsignedInteger('no_urut')->nullable();
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->date('tanggal_mulai_berlaku');
            $table->date('tanggal_berakhir_berlaku');
            $table->string('nik_penandatangan')->nullable();
            $table->string('file_pdf')->nullable();
            $table->string('file_pdf_signed')->nullable();
            $table->json('signature_detail')->nullable();
            $table->string('created_by_username', 100)->nullable();
            $table->dateTime('tanggal_ditandatangani')->nullable();
            $table->timestamps();
        });

        Schema::create('keputusan_direktur_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keputusan_direktur_id')->constrained('keputusan_direktur')->cascadeOnDelete();
            $table->string('field_type');
            $table->unsignedInteger('page')->default(1);
            $table->decimal('x', 10, 2)->default(0);
            $table->decimal('y', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->text('value')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keputusan_direktur_placements');
        Schema::dropIfExists('keputusan_direktur');
    }
};
