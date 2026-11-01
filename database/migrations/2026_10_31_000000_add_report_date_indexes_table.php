<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for the multi-tenant date-range report queries.
     *
     * The reports filter on `tenant_id` + a date column (sold_at, expense_date,
     * returned_at) with whereBetween; a leading tenant_id index alone cannot
     * narrow the date range efficiently.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['tenant_id', 'sold_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['tenant_id', 'expense_date']);
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->index(['tenant_id', 'returned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'sold_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'expense_date']);
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'returned_at']);
        });
    }
};
