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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('tax_inclusive')->default(false);
            $table->ulid('default_tax_rate_id')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('invoice_prefix', 20)->default('INV');
            $table->string('receipt_prefix', 20)->default('REC');
            $table->string('po_prefix', 20)->default('PO');
            $table->unsignedInteger('stock_alert_threshold')->default(10);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('require_customer_on_sale')->default(false);
            $table->boolean('loyalty_enabled')->default(false);
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
