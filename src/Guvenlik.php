<?php

declare(strict_types=1);

/**
 * Güvenlik başlıkları ve CSP nonce üretimi (Faz 7).
 *
 * Amaç:
 *   - Tarayıcıya MIME koklama, çerçeveleme, referrer ve izin politikalarını bildirmek
 *   - Satır içi betikleri nonce ile sınırlamak (inline onclick/onsubmit kullanılmaz)
 *   - HTTPS varsa HSTS göndermek
 */
final class Guvenlik
{
    private static string $nonce = '';
    private static bool $gonderildi = false;

    private function __construct()
    {
    }

    /**
     * Bu istek için CSP nonce değeri. Aynı istekte hep aynı değeri döner.
     */
    public static function nonce(): string
    {
        if (self::$nonce === '') {
            self::$nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        }

        return self::$nonce;
    }

    /**
     * Güvenlik başlıklarını gönderir. CLI betiklerinde ve başlıklar
     * gönderildikten sonra sessizce atlanır.
     */
    public static function basliklar(): void
    {
        if (self::$gonderildi || PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        self::$gonderildi = true;

        $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';

        header_remove('X-Powered-By');

        // X-Content-Type-Options, X-Frame-Options, Referrer-Policy ve
        // Permissions-Policy kök .htaccess içinde tanımlıdır (statik
        // dosyalar için de geçerli olsun diye). Burada yalnızca istek
        // başına değişen başlıklar gönderilir.

        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');

        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'nonce-" . self::nonce() . "'",
            "font-src 'self'",
            "connect-src 'self'",
        ]);

        header('Content-Security-Policy: ' . $csp);
    }
}
