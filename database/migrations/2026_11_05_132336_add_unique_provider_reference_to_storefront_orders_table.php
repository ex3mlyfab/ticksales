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
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->unique('provider_reference', 'storefront_orders_provider_reference_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropUnique('storefront_orders_provider_reference_unique');
        });
    }
};
