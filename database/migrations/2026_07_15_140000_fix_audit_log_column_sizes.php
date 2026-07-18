<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE face_verification_logs MODIFY score DECIMAL(5,2) NULL');
        DB::statement('ALTER TABLE absensi_agenda_audit MODIFY device_info TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE face_verification_logs MODIFY score DECIMAL(5,4) NULL');
        DB::statement('ALTER TABLE absensi_agenda_audit MODIFY device_info VARCHAR(255) NULL');
    }
};
