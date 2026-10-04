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
        Schema::create('branches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
