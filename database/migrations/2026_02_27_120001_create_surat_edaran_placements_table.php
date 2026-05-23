<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratEdaranPlacementsTable extends Migration
{
    public function up()
    {
        Schema::create('surat_edaran_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_edaran_id')->constrained('surat_edaran')->onDelete('cascade');
            $table->string('field_type', 32)->comment('signature, inisial, nama, tanggal, teks, stempel');
            $table->unsignedTinyInteger('page')->default(1);
            $table->decimal('x', 8, 2)->default(0)->comment('mm');
            $table->decimal('y', 8, 2)->default(0)->comment('mm');
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->text('value')->nullable()->comment('Teks atau nilai yang ditampilkan');
            $table->json('options')->nullable()->comment('font_style, color, dll');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_edaran_placements');
    }
}
