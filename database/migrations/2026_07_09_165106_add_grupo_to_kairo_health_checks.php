<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('kairo_health_checks', function (Blueprint $table) {
            $table->string('grupo')->default('General')->after('id');
        });
    }
    public function down(): void {
        Schema::table('kairo_health_checks', function (Blueprint $table) {
            $table->dropColumn('grupo');
        });
    }
};
