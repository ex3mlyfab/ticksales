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
        Schema::create('tenant_branding', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->char('primary_color', 7)->nullable();
            $table->char('secondary_color', 7)->nullable();
            $table->char('accent_color', 7)->nullable();
            $table->string('sidebar_style')->default('default');
            $table->string('border_radius')->default('0.625rem');
            $table->string('theme_preset')->default('neutral');
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_branding');
    }
};
