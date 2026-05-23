<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasjidTokensTable extends Migration
{
    /**
     * Run the migrations.
     * Satu token statis untuk QR masjid; masa berlaku ditentukan admin/takmir.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('masjid_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->dateTime('valid_until');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('masjid_tokens');
    }
}
