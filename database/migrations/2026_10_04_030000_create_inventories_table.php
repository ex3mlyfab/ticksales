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
        Schema::create('inventories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['branch_id', 'product_id']);
            $table->unique(['branch_id', 'product_id', 'variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
