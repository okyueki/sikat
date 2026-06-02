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
        Schema::table('absensi_agenda', function (Blueprint $table) {
            $table->string('device_token', 64)->nullable()->after('alasan');
            $table->string('device_model', 128)->nullable()->after('device_token');
            $table->string('os_version', 32)->nullable()->after('device_model');
            $table->string('browser', 64)->nullable()->after('os_version');
            $table->string('ip_address', 45)->nullable()->after('browser');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_agenda', function (Blueprint $table) {
            $table->dropColumn(['device_token', 'device_model', 'os_version', 'browser', 'ip_address']);
        });
    }
};