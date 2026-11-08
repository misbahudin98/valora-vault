<?php

declare(strict_types=1);

namespace App\Services;

class JwtService
{
    /**
     * Create a signed JWT token with standard claims and HMAC-SHA256 signature.
     *
     * @param array $payload Custom claims (e.g. user id, name, roles)
     * @param int $ttlSeconds Lifetime in seconds (default 7200 = 120 mins)
     * @return string Compact JWT (header.payload.signature)
     */
    public static function createToken(array $payload, int $ttlSeconds = 7200): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ], JSON_UNESCAPED_SLASHES));

        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;
        $payload['iss'] = config('app.name', 'EnterpriseApp');

        $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', "{$header}.{$encodedPayload}", config('app.key'), true);
        $encodedSignature = self::base64UrlEncode($signature);

        return "{$header}.{$encodedPayload}.{$encodedSignature}";
    }

    /**
     * Verify token signature and expiration. Returns payload if valid, null if tampered or expired.
     */
    public static function verifyToken(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", config('app.key'), true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null; // Signature mismatch -> Tampered cookie!
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);
        if (!is_array($decoded) || !isset($decoded['exp'])) {
            return null;
        }

        if ($decoded['exp'] < time()) {
            return null; // Expired!
        }

        return $decoded;
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
