<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan FCM device token per user untuk push notification.
     */
    public function up(): void
    {
        Schema::create('fcm_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token', 512)->comment('FCM device token dari client');
            $table->string('platform', 20)->nullable()->comment('android, ios, web');
            $table->string('device_name', 100)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_device_tokens');
    }
};
