<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('organizador')->nullable()->after('url_meet');
            $table->timestamp('audio_aviso_enviado_en')->nullable()->after('estado');
            $table->timestamp('audio_eliminado_en')->nullable()->after('audio_aviso_enviado_en');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['organizador', 'audio_aviso_enviado_en', 'audio_eliminado_en']);
        });
    }
};
