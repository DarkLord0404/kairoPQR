<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla compartida entre pqr.koqoi.com y meet.koqoi.com para permitir
     * pasar de una app a otra sin volver a pedir usuario/contraseña: cada
     * token es de un solo uso y expira a los 60 segundos.
     */
    public function up(): void
    {
        Schema::create('sso_tokens', function (Blueprint $table) {
            $table->string('token', 64)->primary();
            $table->foreignId('user_id')->constrained();
            $table->string('destino', 50);
            $table->timestamp('expira_en');
            $table->timestamp('usado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_tokens');
    }
};
