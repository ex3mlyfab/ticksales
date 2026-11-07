<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('app_name')->default('TickSales');
            $table->char('currency', 3)->default('NGN');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        DB::table('application_settings')->insert([
            'id' => (string) Str::ulid(),
            'app_name' => 'Sokora',
            'app_name' => 'Sokora',
            'currency' => 'NGN',
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tenants')->where(function ($query): void {
            $query->whereNull('currency')->orWhere('currency', 'USD');
        })->update(['currency' => 'NGN']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
