<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Menampilkan data profil pengguna yang terautentikasi.
     */
    public function show(Request $request)
    {
        // Ambil data pengguna yang sedang login
        $user = $request->user();

        // Load relasi check-ins beserta data restorannya
        $user->load(['checkIns.restaurant']);

        // Kembalikan data dalam format yang rapi menggunakan UserResource
        return new UserResource($user);
    }
}
