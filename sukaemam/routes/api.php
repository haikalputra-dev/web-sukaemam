<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckinController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\LeaderboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/ping', fn() => response()->json(['ok' => true, 'time' => now()->toISOString()]));
Route::get('/debug-middleware', function (Request $request) {
    return response()->json([
        'active_middleware_group' => $request->route()->middleware(),
        'all_active_middleware' => $request->route()->gatherMiddleware(),
    ]);
});
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// == Route Terproteksi (Butuh Login via Firebase) ==
Route::middleware(['firebase'])->group(function () {
    Route::post('/auth/sync', [AuthController::class, 'sync']);
    Route::get('/me', [ProfileController::class, 'show']);

    // Endpoint check-in utama dan semua variasinya untuk tes
    Route::post('/checkin', [CheckinController::class, 'store']);
    Route::post('/checkins/{checkin}/review', [ReviewController::class, 'store']);

    Route::post('/restaurants/{restaurant}/reviews', [ReviewController::class, 'store']);

    Route::get('/test-firebase-auth', function () {
        return response()->json(['message' => 'Middleware Firebase berhasil dilewati!']);
    });
});

