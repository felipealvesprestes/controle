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
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('default_rate_cents');
            $table->string('default_rounding_policy', 20);
            $table->string('legal_name')->default('');
            $table->string('trade_name')->default('');
            $table->char('cnpj', 14)->nullable();
            $table->string('municipal_registration', 30)->default('');
            $table->string('tax_regime', 30);
            $table->string('address_street')->default('');
            $table->string('address_number', 20)->default('');
            $table->string('address_complement', 100)->default('');
            $table->string('address_district', 100)->default('');
            $table->string('address_city', 100)->default('');
            $table->char('address_state', 2)->default('');
            $table->char('address_zip_code', 8)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
