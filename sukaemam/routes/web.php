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


Route::get('/', function () {
    // Cek apakah user sudah login di Filament (admin)
    if (Filament::auth()->check()) {
        // Jika sudah login, redirect ke dashboard Filament
        return redirect()->route('filament.admin.pages.dashboard');
    }

    // Jika belum login, redirect ke halaman login Filament
    return redirect()->route('filament.admin.auth.login');
});

Route::get('/generate-qr/{resto_id}', [QrCodeController::class, 'generate']);


