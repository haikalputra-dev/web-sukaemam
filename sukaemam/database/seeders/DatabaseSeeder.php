<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        \App\Models\User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@sukaemam.com',
            'password' => bcrypt('admin123'),
            'firebase_uid'  => 'debug-uid-001',
            'level' => 99,
            // Add other fields if required by your User model
        ]);


        \App\Models\Restaurant::factory()->create([
            'name'            => 'Warung Sate Pak Joko',
            'description'     => 'Sate ayam & kambing, bumbu kacang.',
            'address'         => 'Jl. Kemang Raya No. 12, Jakarta Selatan',
            'latitude'        => -6.260713,   // Jakarta
            'longitude'       => 106.810905,
            'average_rating'  => 4.3,
            'created_at'      => $now, 'updated_at' => $now,
        ]);

        \App\Models\Restaurant::factory()->create([
            'name'            => 'Mie Ayam Mang Oya',
            'description'     => 'Mie ayam pangsit khas Bandung.',
            'address'         => 'Jl. Riau No. 21, Bandung',
            'latitude'        => -6.914744,   // Bandung
            'longitude'       => 107.609810,
            'average_rating'  => 4.2,
            'created_at'      => $now, 'updated_at' => $now,
        ]);
    }
}
