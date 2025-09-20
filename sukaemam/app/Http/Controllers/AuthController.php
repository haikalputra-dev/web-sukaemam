<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function sync(Request $request)
    {
        $u = $request->user(); // sudah di-upsert oleh middleware
        return response()->json([
            'ok' => true,
            'user' => [
                'id'           => $u->id,
                'firebase_uid' => $u->firebase_uid,
                'name'         => $u->name,
                'email'        => $u->email,
                'total_points' => $u->total_points,
                'message' => 'User authenticated and synced successfully.',
            ],
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'id'           => $u->id,
            'firebase_uid' => $u->firebase_uid,
            'name'         => $u->name,
            'email'        => $u->email,
            'total_points' => $u->total_points,
        ]);
    }
}
