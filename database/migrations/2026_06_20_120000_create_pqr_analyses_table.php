<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pqr_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('queja');
            $table->text('historia')->nullable();
            $table->longText('respuesta_completa')->nullable();
            $table->string('clasificacion')->nullable();
            $table->boolean('requiere_revision_juridica')->default(false);
            $table->boolean('es_queja_valida')->default(true);
            $table->json('secciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pqr_analyses');
    }
};
