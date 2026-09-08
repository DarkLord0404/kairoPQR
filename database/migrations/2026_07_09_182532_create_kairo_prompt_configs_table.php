<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kairo_prompt_configs', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // pqr | ea | meet_fragmento | meet_final
            $table->string('nombre');
            $table->longText('prompt');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kairo_prompt_configs');
    }
};
