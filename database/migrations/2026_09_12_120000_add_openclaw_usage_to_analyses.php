<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pqr_analyses', 'ea_analyses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('tokens_entrada')->nullable()->after('tokens_totales');
                $table->unsignedBigInteger('tokens_salida')->nullable()->after('tokens_entrada');
                $table->unsignedBigInteger('tokens_cache')->nullable()->after('tokens_salida');
                $table->string('modelo', 100)->nullable()->after('tokens_cache');
                $table->unsignedSmallInteger('llamadas_modelo')->nullable()->after('modelo');
                $table->unsignedSmallInteger('fragmentos')->nullable()->after('llamadas_modelo');
            });
        }
    }

    public function down(): void
    {
        foreach (['pqr_analyses', 'ea_analyses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn([
                    'tokens_entrada', 'tokens_salida', 'tokens_cache', 'modelo',
                    'llamadas_modelo', 'fragmentos',
                ]);
            });
        }
    }
};
