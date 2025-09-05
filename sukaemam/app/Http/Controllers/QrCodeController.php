<?php
namespace App\Http\Controllers;
use App\Models\Restaurant;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class QrCodeController extends Controller
{
    // public function generate($resto_id)
    // {
    //     $resto = Restaurant::findOrFail($resto_id);
    //     $nameSlug = Str::slug($resto->name); // Biar filename rapi, contoh: "nasi-uduk-bu-wati"

    //     $qr = \QrCode::format('svg')->size(300)->generate($resto_id);

    //     return response($qr)
    //         ->header('Content-Type', 'image/svg+xml')
    //         ->header('Content-Disposition', "attachment; filename=qr_{$nameSlug}.svg");
    // }

    public function generate(Request $request, Restaurant $restaurant)
    {
        $secret = env('CHECKIN_HMAC_SECRET', '');
        if ($secret === '') {
            abort(500, 'CHECKIN_HMAC_SECRET belum diset di .env');
        }

        // 1) Build string QR: SE-V1|rid|ts|nonce|sig
        $rid   = (int) $restaurant->id;
        $ts    = time();
        $nonce = $this->b64urlEncode(random_bytes(16));
        $msg   = $this->buildPayload($rid, $ts, $nonce);
        $sig   = $this->sign($msg, $secret);
        $qrStr = "{$msg}|{$sig}";

        // 2) Output options
        $fmt = strtolower($request->query('fmt', 'svg')); // svg|png
        if (!in_array($fmt, ['svg', 'png'], true)) {
            $fmt = 'svg';
        }

        $download = $request->boolean('dl', false); // ?dl=1 untuk download
        $disposition = $download ? 'attachment' : 'inline';

        $nameSlug = Str::slug($restaurant->name);
        $stamp = now()->format('Ymd_His');
        $file = "qr_{$nameSlug}_{$stamp}.{$fmt}";

        // 3) Generate image dari STRING QR (bukan ID)
        if ($fmt === 'png') {
            $img = \QrCode::format('png')->size(512)->margin(1)->generate($qrStr);
            return response($img, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => "{$disposition}; filename=\"{$file}\"",
                'X-QR-RAW'            => $qrStr, // gampang di-copy buat testing
            ]);
        }

        // default: SVG
        $svg = \QrCode::format('svg')->size(300)->generate($qrStr);
        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => "{$disposition}; filename=\"{$file}\"",
            'X-QR-RAW'            => $qrStr,
        ]);
    }

    /** ===== Helpers ===== */

    private function b64urlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function sign(string $message, string $secret): string
    {
        // HMAC-SHA256 (raw) -> base64url
        return $this->b64urlEncode(hash_hmac('sha256', $message, $secret, true));
    }

    private function buildPayload(int $restaurantId, int $timestamp, string $nonce): string
    {
        return "SE-V1|{$restaurantId}|{$timestamp}|{$nonce}";
    }

}
