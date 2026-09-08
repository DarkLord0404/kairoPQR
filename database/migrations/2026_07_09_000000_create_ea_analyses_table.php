<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ea_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->mediumText('caso');
            $table->mediumText('historia')->nullable();
            $table->longText('respuesta_completa')->nullable();
            $table->string('clasificacion')->nullable();
            $table->json('secciones')->nullable();
            $table->integer('tokens_totales')->nullable();
            $table->float('duracion_segundos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ea_analyses');
    }
};
