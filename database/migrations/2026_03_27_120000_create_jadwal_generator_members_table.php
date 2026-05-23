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
        Schema::create('jadwal_generator_members', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['petugas', 'dokter']);
            $table->string('source_id', 30);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'jadwal_generator_members_source_unique');
            $table->index(['is_active', 'sort_order'], 'jadwal_generator_members_active_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_generator_members');
    }
};

