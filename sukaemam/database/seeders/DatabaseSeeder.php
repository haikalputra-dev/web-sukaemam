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
            'name'            => 'Sukabumi Coffee Center',
            'description'     => 'Kopi enak dengan suasana nyaman.',
            'price_info'      => 'Harga mulai dari Rp12k/cup',
            'address'         => 'Jl. Mayawati Capitol Plaza No.76 Lt. 1',
            'latitude'        => -6.921483582140359,   // Dekat Alun alun Kota dan Citimall
            'longitude'       => 106.92687650757894,
            'average_rating'  => 4.5,
            'is_recommended'  => true,
            'created_at'      => $now, 'updated_at' => $now,
        ]);

        \App\Models\Restaurant::factory()->create([
            'name'            => 'Mie Gacoan Sukabumi',
            'description'     => 'Mie Seuhah bos!',
            'price_info'      => 'Harga mulai dari Rp10k/porsi',
            'address'         => 'Jl. Otto Iskandardinata No.42, Sukabumi',
            'latitude'        => -6.924885184186435,   // Dekat Station Kereta Api
            'longitude'       => 106.93367669077107,
            'average_rating'  => 4.2,
            'is_recommended'  => true,
            'created_at'      => $now, 'updated_at' => $now,
        ]);

        \App\Models\Restaurant::factory()->create([
            'name'            => '`de`ket rumah gwe`',
            'description'     => 'Gada apapa, deket rumah gwe',
            'price_info'      => 'Harga mulai dari Rp10k/porsi',
            'address'         => 'Bondes Berada, Sukabumi',
            'latitude'        => -6.953967362489661,
            'longitude'       => 106.96225952377053,
            'average_rating'  => 5.0,
            'is_recommended'  => true,
            'created_at'      => $now, 'updated_at' => $now,
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 1,
            'name' => 'Level Badge Tier 1',
            'description' => 'Dicapai saat mencapai Level',
            'image_url' => 'badges/level_tier_1.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 2,
            'name' => 'Level Badge Tier 2',
            'description' => 'Dicapai saat mencapai Level',
            'image_url' => 'badges/level_tier_2.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 3,
            'name' => 'Level Badge Tier 3',
            'description' => 'Dicapai saat mencapai Level',
            'image_url' => 'badges/level_tier_3.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 4,
            'name' => 'Level Badge Tier 4',
            'description' => 'Dicapai saat mencapai Level',
            'image_url' => 'badges/level_tier_4.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 5,
            'name' => 'Level Badge Tier 5',
            'description' => 'Dicapai saat mencapai Level',
            'image_url' => 'badges/level_tier_5.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 6,
            'name' => 'Level Badge Tier 6',
            'description' => 'Dicapai saat mencapai Level',
            'image_url' => 'badges/level_tier_6.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 7,
            'name' => 'First Bite',
            'description' => 'Memberikan review pertamamu.',
            'image_url' => 'badges/achievement_first_bite.png'
        ]);

        \App\Models\Badge::factory()->create([
            'id' => 8,
            'name' => 'Photo Enthusiast',
            'description' => 'Memberikan 5 review dengan foto.',
            'image_url' => 'badges/achievement_photo_enthusiast.png'
        ]);
    }
}
