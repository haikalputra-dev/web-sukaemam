<?php
// database/migrations/xxxx_xx_xx_create_user_rewards_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('reward_id')->constrained()->onDelete('cascade');
            $table->timestamp('claimed_at')->useCurrent();
            $table->string('qr_code_data')->nullable(); // QR code for redemption
            $table->boolean('is_redeemed')->default(false);
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'claimed_at']);
            $table->index(['is_redeemed', 'expires_at']);
            $table->index('qr_code_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rewards');
    }
};