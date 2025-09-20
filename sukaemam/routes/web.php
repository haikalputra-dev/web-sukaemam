<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;
use App\Http\Controllers\QrCodeController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/test-credentials', function () {
    // 1. Baca path file kredensial dari file .env
    $path = env('FIREBASE_CREDENTIALS');

    if (!$path) {
        return 'Variabel FIREBASE_CREDENTIALS tidak ditemukan di file .env';
    }

    // 2. Cek apakah file benar-benar ada di path tersebut
    if (!File::exists($path)) {
        return "File kredensial tidak ditemukan di path: " . $path;
    }

    // 3. Baca isi file dan ambil project_id-nya
    try {
        $credentialsJson = File::get($path);
        $credentialsArray = json_decode($credentialsJson, true);
        $projectId = $credentialsArray['project_id'] ?? 'project_id tidak ditemukan di dalam file JSON';

        return "Project ID yang sedang digunakan oleh Backend Laravel adalah: <strong>" . $projectId . "</strong>";

    } catch (\Exception $e) {
        return "Gagal membaca atau mem-parsing file JSON: " . $e->getMessage();
    }
});


Route::get('/', function () {
    // Cek apakah user sudah login di Filament (admin)
    if (Filament::auth()->check()) {
        // Jika sudah login, redirect ke dashboard Filament
        return redirect()->route('filament.admin.pages.dashboard');
    }

    // Jika belum login, redirect ke halaman login Filament
    return redirect()->route('filament.admin.auth.login');
});

// Route::get('/generate-qr/{resto_id}', [QrCodeController::class, 'generate']);

Route::get('/generate-qr/{restaurant}', [QrCodeController::class, 'generate'])
    ->name('qr.generate');
