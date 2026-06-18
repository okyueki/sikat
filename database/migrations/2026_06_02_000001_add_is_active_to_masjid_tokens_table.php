<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToMasjidTokensTable extends Migration
{
    /**
     * Tambah kolom is_active untuk menandai token QR yang sedang berlaku.
     * Saat admin generate token baru, token lama di-set is_active=0
     * (lebih eksplisit daripada mengandalkan valid_until = now()).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('masjid_tokens', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->index()->after('valid_until');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('masjid_tokens', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
}
