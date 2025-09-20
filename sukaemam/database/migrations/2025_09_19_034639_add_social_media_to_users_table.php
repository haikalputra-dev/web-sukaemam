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
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan 3 kolom baru setelah kolom 'avatar_url'
            $table->string('instagram_username')->nullable()->after('avatar_url');
            $table->string('tiktok_username')->nullable()->after('instagram_username');
            $table->string('facebook_profile_url')->nullable()->after('tiktok_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['instagram_username', 'tiktok_username', 'facebook_profile_url']);
        });
    }
};
