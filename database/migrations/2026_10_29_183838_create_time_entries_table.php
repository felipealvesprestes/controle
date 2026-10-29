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
        // Only what logging needs; the closing snapshot columns arrive with F-08 (RN-11).
        Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->date('month');
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->unique(['client_id', 'month']);
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->string('description')->default('');
            $table->string('reference', 40)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('rate_cents');
            $table->string('source', 10);
            // MySQL has no partial index: this column holds the user id only while the timer runs (RN-14).
            $table->unsignedBigInteger('running_user_id')->nullable()->storedAs('if(ended_at is null, user_id, null)')->unique();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['client_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('billing_periods');
    }
};
