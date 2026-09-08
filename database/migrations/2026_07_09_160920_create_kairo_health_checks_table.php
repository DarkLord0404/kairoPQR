<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kairo_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('servicio');
            $table->string('estado'); // ok, fallo, advertencia
            $table->text('mensaje')->nullable();
            $table->integer('duracion_ms')->nullable();
            $table->timestamp('verificado_en')->useCurrent();
        });
    }
    public function down(): void { Schema::dropIfExists('kairo_health_checks'); }
};
