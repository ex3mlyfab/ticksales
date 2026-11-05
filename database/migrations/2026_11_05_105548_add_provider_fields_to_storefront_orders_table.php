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
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('provider_reference')->nullable()->after('payment_reference');
            $table->string('provider_event_id')->nullable()->after('provider_reference');
            $table->index(['tenant_id', 'provider_reference'], 'storefront_orders_provider_reference_index');
            $table->index(['provider_event_id'], 'storefront_orders_provider_event_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropIndex('storefront_orders_provider_reference_index');
            $table->dropIndex('storefront_orders_provider_event_index');
            $table->dropColumn(['payment_provider', 'provider_reference', 'provider_event_id']);
        });
    }
};
