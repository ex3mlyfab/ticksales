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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('sale_id');
            $table->ulid('tenant_id');
            $table->ulid('branch_id');
            $table->string('reference_no');
            $table->text('reason')->nullable();
            $table->decimal('refund_amount', 15, 4)->default(0);
            $table->ulid('refund_method_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('returned_by');
            $table->timestamp('returned_at');
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('refund_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('returned_by')->references('id')->on('users');

            $table->index('sale_id');
            $table->index('tenant_id');
            $table->index('branch_id');
            $table->unique(['tenant_id', 'reference_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
