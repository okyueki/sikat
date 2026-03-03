<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageFieldsToMasterTandaTanganTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('master_tanda_tangan', function (Blueprint $table) {
            $table->enum('type', ['text', 'image'])->default('text')->after('user_id')->comment('Tipe tanda tangan: text (font cursive) atau image (upload gambar)');
            $table->string('file_path')->nullable()->after('color')->comment('Path file gambar tanda tangan (PNG)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('master_tanda_tangan', function (Blueprint $table) {
            $table->dropColumn(['type', 'file_path']);
        });
    }
}
