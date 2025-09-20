<?php
// database/migrations/xxxx_xx_xx_create_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');

            // Ganti 'checkin_id' menjadi 'check_in_id'
            $table->foreignUuid('check_in_id')->constrained('checkins')->onDelete('cascade');

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();

            // Ganti 'checkin_id' menjadi 'check_in_id'
            $table->unique('check_in_id');

            $table->index(['restaurant_id', 'rating']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
