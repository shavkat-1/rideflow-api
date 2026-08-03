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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('passenger_id')
                ->constrained('users');

            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('users');

            $table->enum('status', [
                'pending',
                'accepted',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->enum('pricing_type', [
                'fixed',
                'calculated',
            ]);

            $table->decimal('estimated_price', 10, 2);

            $table->decimal('final_price', 10, 2)
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
