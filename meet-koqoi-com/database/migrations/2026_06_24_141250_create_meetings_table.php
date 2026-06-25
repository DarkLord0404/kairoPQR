<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('base_path')->unique();
            $table->string('titulo')->default('Reunion');
            $table->string('url_meet')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();
            $table->decimal('duracion_segundos', 10, 2)->nullable();
            $table->unsignedInteger('num_segmentos')->default(1);
            $table->string('transcripcion_path')->nullable();
            $table->string('acta_path')->nullable();
            $table->string('estado')->default('pendiente');
            $table->boolean('diarizada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
