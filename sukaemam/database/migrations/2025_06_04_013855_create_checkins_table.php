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
            $table->uuid('id')->primary();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();

            // Waktu check-in + kunci hari (YYYYMMDD, pakai timezone app di level aplikasi)
            $table->timestamp('checkin_time')->useCurrent();
            $table->unsignedInteger('day_key'); // contoh: 20250817

            // Data QR yang dipindai (untuk audit). TIDAK unik karena QR dicetak.
            $table->string('qr_code_data', 512);

            // Lokasi saat scan (geofence)
            $table->decimal('scan_lat', 10, 7)->nullable();
            $table->decimal('scan_lng', 10, 7)->nullable();
            $table->float('scan_accuracy')->nullable(); // meter (opsional dari client)

            // Poin
            $table->integer('points_earned')->default(0);

            $table->timestamps();

            // ===== Indexes =====
            // 1x per restoran per hari
            $table->unique(['user_id', 'restaurant_id', 'day_key'], 'uniq_user_resto_day');

            // Query cepat untuk limit global 1x/hari (kalau nanti dipakai di logic)
            $table->index(['user_id', 'day_key']);

            // Analitik & listing
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
