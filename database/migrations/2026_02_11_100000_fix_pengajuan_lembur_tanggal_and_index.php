<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * - Backfill tanggal_awal/tanggal_akhir yang NULL (dari tanggal_dibuat)
 * - Jadikan NOT NULL agar rekap filter periode selalu konsisten
 * - Tambah index untuk rekap lembur (status + tanggal_awal, nik + status)
 */
class FixPengajuanLemburTanggalAndIndex extends Migration
{
    public function up()
    {
        // Backfill: tanggal_awal NULL → pakai tanggal dari tanggal_dibuat
        DB::table('pengajuan_lembur')
            ->whereNull('tanggal_awal')
            ->update(['tanggal_awal' => DB::raw('DATE(tanggal_dibuat)')]);

        // Backfill: tanggal_akhir NULL → sama dengan tanggal_awal
        DB::table('pengajuan_lembur')
            ->whereNull('tanggal_akhir')
            ->update(['tanggal_akhir' => DB::raw('tanggal_awal')]);

        // Ubah kolom jadi NOT NULL (pakai raw SQL agar tidak perlu doctrine/dbal)
        DB::statement('ALTER TABLE pengajuan_lembur MODIFY tanggal_awal DATE NOT NULL');
        DB::statement('ALTER TABLE pengajuan_lembur MODIFY tanggal_akhir DATE NOT NULL');

        Schema::table('pengajuan_lembur', function (Blueprint $table) {
            $table->index(['status', 'tanggal_awal'], 'pengajuan_lembur_rekap_idx');
            $table->index(['nik', 'status'], 'pengajuan_lembur_nik_status_idx');
        });
    }

    public function down()
    {
        Schema::table('pengajuan_lembur', function (Blueprint $table) {
            $table->dropIndex('pengajuan_lembur_rekap_idx');
            $table->dropIndex('pengajuan_lembur_nik_status_idx');
        });

        DB::statement('ALTER TABLE pengajuan_lembur MODIFY tanggal_awal DATE NULL');
        DB::statement('ALTER TABLE pengajuan_lembur MODIFY tanggal_akhir DATE NULL');
    }
}
