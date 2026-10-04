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
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignUlid('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->foreignUlid('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->boolean('has_variants')->default(false);
            $table->boolean('track_inventory')->default(true);
            $table->unsignedInteger('alert_quantity')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('unit_id');
            $table->index('tax_rate_id');
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
