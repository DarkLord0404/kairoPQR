<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('acceso_pqr')->default(true)->after('role');
            $table->boolean('acceso_reuniones')->default(false)->after('acceso_pqr');
        });

        // Preservar el comportamiento actual: el usuario master ya tenia
        // acceso de facto a todo (incluido Meet); los demas solo a PQR.
        DB::table('users')->where('role', 'master')->update([
            'acceso_pqr' => true,
            'acceso_reuniones' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['acceso_pqr', 'acceso_reuniones']);
        });
    }
};
