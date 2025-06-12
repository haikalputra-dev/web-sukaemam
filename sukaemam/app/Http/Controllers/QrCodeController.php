<?php
namespace App\Http\Controllers;
use App\Models\Restaurant;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    public function generate($resto_id)
    {
        $resto = Restaurant::findOrFail($resto_id);
        $nameSlug = Str::slug($resto->name); // Biar filename rapi, contoh: "nasi-uduk-bu-wati"

        $qr = \QrCode::format('svg')->size(300)->generate($resto_id);

        return response($qr)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', "attachment; filename=qr_{$nameSlug}.svg");
    }

}
