<?php
// database/migrations/xxxx_xx_xx_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar_url')->nullable();
            $table->integer('level')->default(1);
            $table->integer('total_points')->default(0);
            $table->string('firebase_uid')->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('username');
            $table->index('firebase_uid');
            $table->index('total_points');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};