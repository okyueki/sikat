<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_internal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_surat')->unique();
            $table->string('nik_penandatangan', 50)->nullable();
            $table->json('signature_detail')->nullable();
            $table->string('file_pdf_signed')->nullable();
            $table->timestamp('tanggal_ditandatangani')->nullable();
            $table->string('created_by_username', 50)->nullable();
            $table->timestamps();

            $table->foreign('id_surat')
                ->references('id_surat')
                ->on('surat')
                ->onDelete('cascade');
        });

        Schema::create('memo_internal_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_internal_id')->constrained('memo_internal')->cascadeOnDelete();
            $table->string('field_type', 32);
            $table->unsignedTinyInteger('page')->default(1);
            $table->decimal('x', 8, 2)->default(0);
            $table->decimal('y', 8, 2)->default(0);
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->text('value')->nullable();
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_internal_placements');
        Schema::dropIfExists('memo_internal');
    }
};
