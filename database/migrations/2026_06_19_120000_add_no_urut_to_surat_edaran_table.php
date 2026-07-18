<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_edaran', function (Blueprint $table) {
            $table->unsignedInteger('no_urut')->nullable()->after('nomor_surat');
        });
    }

    public function down(): void
    {
        Schema::table('surat_edaran', function (Blueprint $table) {
            $table->dropColumn('no_urut');
        });
    }
};
