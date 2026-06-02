<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('absensi_agenda_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('absensi_id')->nullable()->index();
            $table->foreignId('agenda_id')->constrained('agendas')->onDelete('cascade');
            $table->string('nik', 32)->index();
            $table->enum('aksi', ['create', 'update_status', 'manual_create'])->default('create');
            $table->string('status_lama', 32)->nullable();
            $table->string('status_baru', 32);
            $table->text('alasan_perubahan')->nullable();
            $table->string('perubahan_oleh', 32);
            $table->timestamp('perubahan_pada')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_token', 64)->nullable();
            $table->string('device_info', 255)->nullable();

            // Indexes
            $table->index(['agenda_id', 'nik']);
            $table->index('perubahan_pada');

            // Foreign key to absensi_agenda
            $table->foreign('absensi_id')
                  ->references('id_absensi_agenda')
                  ->on('absensi_agenda')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_agenda_audit');
    }
};