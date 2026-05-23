<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQontakTrackingToWhatsappLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('qontak_message_id', 50)->nullable()->index()->after('template_id');
            $table->string('delivery_status', 20)->nullable()->index()->after('status'); // todo, sent, delivered, read, failed
            $table->json('delivery_response')->nullable()->after('response');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn(['qontak_message_id', 'delivery_status', 'delivery_response', 'delivered_at', 'read_at']);
        });
    }
}
