<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel mapping pegawai <-> Telegram user
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->index(); // NIK dari pegawai
            $table->string('nama_pegawai', 150)->nullable();
            $table->string('username', 100)->nullable(); // username telegram opsional
            $table->bigInteger('chat_id')->index(); // chat_id Telegram
            $table->timestamps();
        });

        // Tabel log/antrian notifikasi
        Schema::create('telegram_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')
                ->constrained('telegram_users')
                ->onDelete('cascade'); // kalau user dihapus, lognya ikut hilang

            $table->text('message');
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_notifications');
        Schema::dropIfExists('telegram_users');
    }
};
