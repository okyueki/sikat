<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel cache untuk menyimpan hasil agregasi data pegawai per periode.
     * Ini memungkinkan:
     * 1. Query cepat tanpa hitung ulang setiap akses
     * 2. Historical data untuk trend analysis
     * 3. Audit trail data agregasi
     */
    public function up(): void
    {
        Schema::create('pegawai_data_cache', function (Blueprint $table) {
            $table->id();

            // Pegawai info
            $table->string('nik', 32)->index();
            $table->string('nama', 128);
            $table->string('departemen', 64)->nullable()->index();
            $table->string('jabatan', 64)->nullable();

            // Periode
            $table->enum('periode_type', ['harian', 'mingguan', 'bulanan'])->index();
            $table->date('periode_start')->index();
            $table->date('periode_end')->index();

            // Presensi Data
            $table->unsignedSmallInteger('presensi_total_hadir')->default(0);
            $table->unsignedSmallInteger('presensi_tepat_waktu')->default(0);
            $table->unsignedSmallInteger('presensi_terlambat')->default(0);
            $table->unsignedInteger('presensi_total_menit_terlambat')->default(0);
            $table->unsignedSmallInteger('presensi_tidak_hadir')->default(0);
            $table->decimal('presensi_rate_kehadiran', 5, 2)->default(0);
            $table->decimal('presensi_rate_tepat_waktu', 5, 2)->default(0);

            // Absensi Sholat Data
            $table->unsignedSmallInteger('sholat_subuh')->default(0);
            $table->unsignedSmallInteger('sholat_dzuhur')->default(0);
            $table->unsignedSmallInteger('sholat_ashar')->default(0);
            $table->unsignedSmallInteger('sholat_maghrib')->default(0);
            $table->unsignedSmallInteger('sholat_isya')->default(0);
            $table->unsignedSmallInteger('sholat_sunnah')->default(0);
            $table->unsignedSmallInteger('sholat_total')->default(0);
            $table->decimal('sholat_rate', 5, 2)->default(0);

            // Budaya Kerja Data
            $table->unsignedSmallInteger('budaya_total_penilaian')->default(0);
            $table->unsignedSmallInteger('budaya_total_nilai')->default(0);
            $table->decimal('budaya_rata_rata', 4, 2)->default(0);
            $table->unsignedSmallInteger('budaya_nilai_tertinggi')->default(0);
            $table->unsignedSmallInteger('budaya_nilai_terendah')->default(0);
            $table->decimal('budaya_rate', 5, 2)->default(0);

            // Absensi Agenda Data
            $table->unsignedSmallInteger('agenda_diundang')->default(0);
            $table->unsignedSmallInteger('agenda_hadir')->default(0);
            $table->unsignedSmallInteger('agenda_ijin')->default(0);
            $table->unsignedSmallInteger('agenda_cuti')->default(0);
            $table->unsignedSmallInteger('agenda_sakit')->default(0);
            $table->unsignedSmallInteger('agenda_berhalangan')->default(0);
            $table->unsignedSmallInteger('agenda_tidak_hadir')->default(0);
            $table->decimal('agenda_rate_kehadiran', 5, 2)->default(0);

            // Overall Score & Status
            $table->decimal('overall_score', 5, 2)->default(0)->index();
            $table->enum('status_keterlibatan', ['aktif', 'warning', 'tidak_aktif'])->default('aktif')->index();

            // Metadata
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamp('calculated_at')->nullable();

            // Unique constraint: satu cache per nik per periode
            $table->unique(['nik', 'periode_type', 'periode_start'], 'pegawai_cache_unique');

            // Indexes for query optimization
            $table->index(['periode_type', 'periode_start', 'periode_end']);
            $table->index(['status_keterlibatan', 'overall_score']);
            $table->index(['departemen', 'overall_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_data_cache');
    }
};