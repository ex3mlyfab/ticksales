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
        Schema::create('storefront_stock_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('storefront_order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUlid('inventory_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['inventory_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_stock_reservations');
    }
};
