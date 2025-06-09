<?php
// database/migrations/xxxx_xx_xx_create_checkins_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->timestamp('checkin_time')->useCurrent();
            $table->string('qr_code_data');
            $table->integer('points_earned')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['restaurant_id', 'created_at']);
            $table->index('checkin_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};