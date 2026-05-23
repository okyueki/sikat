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
        Schema::table('surat_edaran', function (Blueprint $table) {
            $table->string('created_by_username', 100)->nullable()->after('signature_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_edaran', function (Blueprint $table) {
            $table->dropColumn('created_by_username');
        });
    }
};

