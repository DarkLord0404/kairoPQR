<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::table('meetings', function (Blueprint $table): void {
            $table->timestamp('cumple_synced_at')->nullable();
            $table->string('cumple_synced_hash', 64)->nullable();
            $table->text('cumple_sync_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meetings', fn (Blueprint $table) => $table->dropColumn(['cumple_synced_at', 'cumple_synced_hash', 'cumple_sync_error']));
        Schema::dropIfExists('app_settings');
    }
};
