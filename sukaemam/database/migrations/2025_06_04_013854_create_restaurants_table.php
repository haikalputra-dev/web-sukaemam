<?php
// database/migrations/xxxx_xx_xx_create_restaurants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('address');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->string('image_url')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['latitude', 'longitude']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};