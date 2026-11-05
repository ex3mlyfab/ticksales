<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->text('storefront_description')->nullable()->after('bank_transfer_instructions');
            $table->string('storefront_email', 255)->nullable()->after('storefront_description');
            $table->string('storefront_phone', 50)->nullable()->after('storefront_email');
            $table->text('storefront_policy')->nullable()->after('storefront_phone');
            $table->boolean('storefront_pickup_enabled')->default(true)->after('storefront_policy');
            $table->boolean('storefront_delivery_enabled')->default(true)->after('storefront_pickup_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn([
                'storefront_description',
                'storefront_email',
                'storefront_phone',
                'storefront_policy',
                'storefront_pickup_enabled',
                'storefront_delivery_enabled',
            ]);
        });
    }
};
