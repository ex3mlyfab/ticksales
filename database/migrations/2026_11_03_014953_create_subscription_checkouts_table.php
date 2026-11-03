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
        Schema::create('subscription_checkouts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('subscription_payment_id')->nullable()->unique()->constrained('subscription_payments')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('pending');
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->text('checkout_url')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_checkouts');
    }
};
