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
        Schema::table('pqr_analyses', function (Blueprint $table) {
            $table->unsignedInteger('tokens_totales')->nullable()->after('secciones');
            $table->decimal('duracion_segundos', 6, 2)->nullable()->after('tokens_totales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pqr_analyses', function (Blueprint $table) {
            $table->dropColumn(['tokens_totales', 'duracion_segundos']);
        });
    }
};
