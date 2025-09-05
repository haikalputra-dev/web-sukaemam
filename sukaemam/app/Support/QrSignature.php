<?php

namespace App\Support;

/**
 * Helper untuk base64url & HMAC-SHA256 signature QR SukaEmam.
 *
 * Format QR (raw):
 *   SE-V1|<restaurant_id>|<timestamp>|<nonce>|<signature>
 *
 * - Signature = base64url( HMAC_SHA256("SE-V1|rid|ts|nonce", CHECKIN_HMAC_SECRET) )
 * - base64url = Base64 standar tapi '+' -> '-', '/' -> '_', tanpa '=' padding.
 */
class QrSignature
{
    /**
     * Encode biner ke base64url (tanpa padding).
     */
    public static function b64urlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /**
     * Decode base64url ke biner.
     */
    public static function b64urlDecode(string $b64url): string
    {
        $b64 = strtr($b64url, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($b64);
    }

    /**
     * Bangun payload tanpa signature: "SE-V1|rid|ts|nonce".
     */
    public static function buildPayload(int $restaurantId, int $timestamp, string $nonce): string
    {
        return "SE-V1|{$restaurantId}|{$timestamp}|{$nonce}";
    }

    /**
     * HMAC-SHA256 -> base64url(signature biner).
     */
    public static function sign(string $message, string $secret): string
    {
        return self::b64urlEncode(hash_hmac('sha256', $message, $secret, true));
    }

    /**
     * Parse raw QR ke array terstruktur. Throw \InvalidArgumentException kalau format salah.
     */
    public static function parse(string $raw): array
    {
        $parts = explode('|', $raw);
        if (count($parts) !== 5 || $parts[0] !== 'SE-V1') {
            throw new \InvalidArgumentException('Format QR tidak valid');
        }
        [$ver, $rid, $ts, $nonce, $sig] = $parts;

        if (!ctype_digit((string)$rid) || !ctype_digit((string)$ts) || $nonce === '' || $sig === '') {
            throw new \InvalidArgumentException('Payload QR tidak valid');
        }

        return [
            'version' => $ver,
            'restaurant_id' => (int) $rid,
            'timestamp' => (int) $ts,
            'nonce' => $nonce,
            'signature' => $sig,
        ];
    }
}
