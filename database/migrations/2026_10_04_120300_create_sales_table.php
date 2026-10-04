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
        Schema::create('sales', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('register_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_no');
            $table->uuid('idempotency_key')->unique();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('change_amount', 15, 4)->default(0);
            $table->decimal('outstanding_amount', 15, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('sold_by');
            $table->timestamp('sold_at');
            $table->boolean('synced_from_offline')->default(false);
            $table->timestamp('offline_created_at')->nullable();
            $table->timestamps();

            $table->foreign('sold_by')->references('id')->on('users');
            $table->index('tenant_id');
            $table->index('branch_id');
            $table->index(['tenant_id', 'reference_no']);
            $table->index('customer_id');
            $table->index('register_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
