<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Daftarkan 'singleton' untuk Kreait\Firebase\Auth
        $this->app->singleton(Auth::class, function ($app) {
            // Ambil path ke file kredensial dari file config/firebase.php
            $credentialsPath = config('firebase.credentials.file');

            // Jika path tidak ada atau file tidak ditemukan, hentikan dengan error yang jelas
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                throw new \InvalidArgumentException('File kredensial Firebase tidak ditemukan. Periksa config/firebase.php');
            }

            // Buat factory menggunakan file kredensial
            $factory = (new Factory)->withServiceAccount($credentialsPath);

            // Buat dan kembalikan objek Auth
            return $factory->createAuth();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
