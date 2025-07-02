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
        \App\Models\User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@sukaemam.com',
            'password' => bcrypt('admin123'), // Change this password as needed
            'level' => 99, // optional: set admin level
            // Add other fields if required by your User model
        ]);
    }
}
