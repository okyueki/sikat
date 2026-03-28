<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->nullable()->index();
            $table->string('nama', 100);
            $table->string('phone', 20)->index();
            $table->text('message');
            $table->string('template_id', 50)->nullable();
            $table->enum('status', ['success', 'failed', 'error', 'pending'])->default('pending');
            $table->json('response')->nullable();
            $table->timestamp('sent_at')->index();
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
        Schema::dropIfExists('whatsapp_logs');
    }
}
