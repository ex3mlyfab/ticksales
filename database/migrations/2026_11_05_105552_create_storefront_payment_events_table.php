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
        Schema::create('storefront_payment_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('storefront_order_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_event_id');
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_event_id'], 'storefront_payment_event_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_payment_events');
    }
};
