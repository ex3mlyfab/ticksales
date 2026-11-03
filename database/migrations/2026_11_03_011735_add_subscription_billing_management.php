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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('features');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
        });

        Schema::create('subscription_payment_methods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('instructions');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->foreignUlid('payment_method_id')->nullable()->constrained('subscription_payment_methods')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method_name');
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->timestamp('period_starts_at');
            $table->timestamp('period_ends_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_payment_methods');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('subscription_ends_at');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
