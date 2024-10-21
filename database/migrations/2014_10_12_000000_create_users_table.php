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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->enum('role', ['admin', 'agent', 'client'])->default('client');
            $table->string('state')->nullable();
            $table->string('town')->nullable();
            $table->string('lga')->nullable();
            $table->string('bvn')->nullable();
            $table->string('id_number')->nullable();
            $table->string('document')->nullable();
            $table->string('document_type')->nullable();
            $table->integer('offers_declined')->nullable();
            $table->string('password');
            $table->boolean('isVerified')->default(false);
            $table->timestamp('user_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
