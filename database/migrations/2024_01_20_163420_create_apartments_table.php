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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('apartment_type');
            $table->string('service_type');
            $table->string('square_fit');
            $table->string('location');
            $table->string('people_category');
            $table->string('contact_number');
            $table->longText('description');
            $table->integer('rooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->float('price_monthly', 15, 2)->nullable();
            $table->float('price_yearly', 15, 2)->nullable();
            $table->boolean('parking_space')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('daily_views')->default(0);
            $table->integer('total_views')->default(0);
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
