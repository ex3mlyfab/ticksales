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
            $table->string('carrier_name')->nullable()->after('delivery_address');
            $table->string('tracking_number')->nullable()->after('carrier_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropColumn(['carrier_name', 'tracking_number']);
        });
    }
};
