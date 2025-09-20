<?php
// database/migrations/xxxx_xx_xx_create_badges_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();

            // Index
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
