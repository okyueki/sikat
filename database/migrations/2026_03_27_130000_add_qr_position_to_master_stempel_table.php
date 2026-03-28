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
        Schema::table('master_stempel', function (Blueprint $table) {
            $table->string('qr_position', 20)
                ->default('bottom_right')
                ->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_stempel', function (Blueprint $table) {
            $table->dropColumn('qr_position');
        });
    }
};

