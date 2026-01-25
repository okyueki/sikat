<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan data agar constraint tidak gagal:
        // - hapus duplikat (nik_pegawai, id_jenis_berkas) → simpan yang id_terbesar
        // - hapus baris orphan (id_jenis_berkas tidak ada di jenis_berkas)
        if (Schema::hasTable('berkas_pegawai') && Schema::hasTable('jenis_berkas')) {
            // Orphan
            DB::table('berkas_pegawai')
                ->whereNotIn('id_jenis_berkas', function ($q) {
                    $q->select('id_jenis_berkas')->from('jenis_berkas');
                })
                ->delete();

            // Duplikat
            $dupes = DB::table('berkas_pegawai')
                ->select('nik_pegawai', 'id_jenis_berkas', DB::raw('MAX(id_berkas_pegawai) as keep_id'))
                ->groupBy('nik_pegawai', 'id_jenis_berkas')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($dupes as $d) {
                DB::table('berkas_pegawai')
                    ->where('nik_pegawai', $d->nik_pegawai)
                    ->where('id_jenis_berkas', $d->id_jenis_berkas)
                    ->where('id_berkas_pegawai', '!=', $d->keep_id)
                    ->delete();
            }
        }

        Schema::table('berkas_pegawai', function (Blueprint $table) {
            // Workflow verifikasi (terpisah dari status_berkas yang bermakna masa berlaku/validitas dokumen)
            if (!Schema::hasColumn('berkas_pegawai', 'verifikasi_status')) {
                $table->enum('verifikasi_status', ['uploaded', 'review', 'approved', 'rejected'])
                    ->default('uploaded')
                    ->after('status_berkas');
            }

            if (!Schema::hasColumn('berkas_pegawai', 'catatan_verifikator')) {
                $table->text('catatan_verifikator')->nullable()->after('verifikasi_status');
            }

            if (!Schema::hasColumn('berkas_pegawai', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('catatan_verifikator');
            }

            if (!Schema::hasColumn('berkas_pegawai', 'verified_by')) {
                $table->string('verified_by', 50)->nullable()->after('verified_at');
            }

            // Metadata file untuk audit & download terarah
            if (!Schema::hasColumn('berkas_pegawai', 'file_disk')) {
                $table->string('file_disk', 20)->nullable()->after('file');
            }

            if (!Schema::hasColumn('berkas_pegawai', 'original_filename')) {
                $table->string('original_filename', 255)->nullable()->after('file_disk');
            }

            if (!Schema::hasColumn('berkas_pegawai', 'mime_type')) {
                $table->string('mime_type', 100)->nullable()->after('original_filename');
            }

            if (!Schema::hasColumn('berkas_pegawai', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            }
        });

        Schema::table('berkas_pegawai', function (Blueprint $table) {
            // Unik: 1 jenis berkas per pegawai
            $table->unique(['nik_pegawai', 'id_jenis_berkas'], 'berkas_pegawai_unique_nik_jenis');

            // FK: jenis_berkas harus ada
            $table->foreign('id_jenis_berkas', 'berkas_pegawai_fk_jenis_berkas')
                ->references('id_jenis_berkas')
                ->on('jenis_berkas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('berkas_pegawai', function (Blueprint $table) {
            // Drop constraints dulu
            $table->dropForeign('berkas_pegawai_fk_jenis_berkas');
            $table->dropUnique('berkas_pegawai_unique_nik_jenis');

            // Drop kolom tambahan
            if (Schema::hasColumn('berkas_pegawai', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::hasColumn('berkas_pegawai', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
            if (Schema::hasColumn('berkas_pegawai', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
            if (Schema::hasColumn('berkas_pegawai', 'file_disk')) {
                $table->dropColumn('file_disk');
            }
            if (Schema::hasColumn('berkas_pegawai', 'verified_by')) {
                $table->dropColumn('verified_by');
            }
            if (Schema::hasColumn('berkas_pegawai', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
            if (Schema::hasColumn('berkas_pegawai', 'catatan_verifikator')) {
                $table->dropColumn('catatan_verifikator');
            }
            if (Schema::hasColumn('berkas_pegawai', 'verifikasi_status')) {
                $table->dropColumn('verifikasi_status');
            }
        });
    }
};

