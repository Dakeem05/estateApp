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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->float('amount', 12, 2)->default(0.00);
            $table->float('transfer_fee', 12, 2)->default(0.00);
            $table->boolean('is_retry')->default(false);
            $table->string('state');
            $table->enum('status', ['pending', 'failed','successful']);
            $table->string('ref');
            $table->string('transfer_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
