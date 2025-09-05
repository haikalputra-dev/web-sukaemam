<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckinController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['firebase'])->group(function () {
    Route::post('/auth/sync', [AuthController::class, 'sync']);
    Route::post('/checkin', [CheckinController::class, 'store']);
    Route::get('/me', [AuthController::class, 'me']);
    // Route::post('/checkin', function (\Illuminate\Http\Request $r) {
    //     $qr = (string) $r->input('qr', '');
    //     if ($qr === '') {
    //         return response()->json(['status' => 'error', 'message' => 'QR missing'], 422);
    //     }
    //     return response()->json(['status' => 'ok', 'points' => 15, 'qr' => $qr]);
    // });
});


// Route::middleware('auth:sanctum')->post('/checkin', [\App\Http\Controllers\Api\CheckinController::class, 'store']);
Route::post('/checkin-coba', [\App\Http\Controllers\Api\CheckinController::class, 'testMethod']);

Route::post('/checkin-test', function () {
    return response()->json(['message' => 'API test OK']);
});

Route::get('/ping', function () {
    return response()->json(['ok' => true, 'time' => now()->toISOString()]);
});
