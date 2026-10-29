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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('color', 20);
            $table->string('rounding_policy', 20)->nullable();
            $table->string('legal_name')->default('');
            $table->string('document', 14)->nullable();
            $table->string('email')->default('');
            $table->boolean('withholds_iss')->default(false);
            $table->string('address_street')->default('');
            $table->string('address_number', 20)->default('');
            $table->string('address_complement', 100)->default('');
            $table->string('address_district', 100)->default('');
            $table->string('address_city', 100)->default('');
            $table->char('address_state', 2)->default('');
            $table->char('address_zip_code', 8)->default('');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('client_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rate_cents');
            $table->date('valid_from');
            $table->timestamps();

            $table->unique(['client_id', 'valid_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_rates');
        Schema::dropIfExists('clients');
    }
};
