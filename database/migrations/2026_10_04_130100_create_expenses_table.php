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
        Schema::create('expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('description');
            $table->decimal('amount', 15, 4);
            $table->date('expense_date');
            $table->foreignUlid('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('recorded_by')->references('id')->on('users');
            $table->index('tenant_id');
            $table->index('branch_id');
            $table->index('expense_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
