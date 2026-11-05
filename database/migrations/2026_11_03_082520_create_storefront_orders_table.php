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
        Schema::create('storefront_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_no');
            $table->uuid('idempotency_key');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method');
            $table->string('fulfillment_method');
            $table->decimal('subtotal', 15, 4);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('delivery_fee', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4);
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();
            $table->text('delivery_address')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_reference')->nullable();
            $table->text('cancellation_note')->nullable();
            $table->boolean('refund_handled_externally')->default(false);
            $table->text('refund_note')->nullable();
            $table->string('tracking_token_hash', 64)->unique();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_no']);
            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_orders');
    }
};
