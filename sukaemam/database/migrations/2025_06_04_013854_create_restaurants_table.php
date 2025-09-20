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
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->text('description')->nullable();
            $table->string('price_info')->nullable();
            $table->double('latitude', 17, 15);
            $table->double('longitude', 18, 15);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->boolean('is_recommended')->default(false);
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
