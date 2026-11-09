<?php

declare(strict_types=1);

/**
 * Küçük yardımcı fonksiyonlar.
 */

if (!function_exists('config')) {
    /**
     * config/config.php içinden noktalı anahtarla ayar okur.
     * Örnek: config('uygulama.ad'), config('yukleme.maks_bayt')
     */
    function config(?string $anahtar = null, mixed $varsayilan = null): mixed
    {
        static $ayar = null;

        if ($ayar === null) {
            $ayar = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        }

        if ($anahtar === null || $anahtar === '') {
            return $ayar;
        }

        $deger = $ayar;
        foreach (explode('.', $anahtar) as $parca) {
            if (!is_array($deger) || !array_key_exists($parca, $deger)) {
                return $varsayilan;
            }
            $deger = $deger[$parca];
        }

        return $deger;
    }
}

if (!function_exists('e')) {
    /**
     * Ekrana basılan her değer bu fonksiyondan geçer (XSS koruması).
     */
    function e(mixed $deger): string
    {
        return htmlspecialchars((string) ($deger ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Uygulama içi adres üretir: url('assets/css/app.css') => /sanalokul/assets/css/app.css
     */
    function url(string $yol = ''): string
    {
        $taban = rtrim((string) config('uygulama.taban_yol', ''), '/');
        $yol = ltrim($yol, '/');

        return $yol === '' ? $taban . '/' : $taban . '/' . $yol;
    }
}

if (!function_exists('varlik')) {
    /**
     * Statik varlıklara dosya zamanından sürüm ekler; tarayıcı önbelleğini kırar.
     * varlik('assets/css/app.css') => /sanalokul/assets/css/app.css?v=1699999999
     */
    function varlik(string $yol): string
    {
        $adres = url($yol);
        $dosya = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($yol, '/');

        return is_file($dosya) ? $adres . '?v=' . filemtime($dosya) : $adres;
    }
}

if (!function_exists('tarih_tr')) {
    /**
     * 2026-11-06 => 06.11.2026
     */
    function tarih_tr(?string $tarih): string
    {
        if ($tarih === null || $tarih === '') {
            return '-';
        }

        $zaman = strtotime($tarih);

        return $zaman === false ? '-' : date('d.m.Y', $zaman);
    }
}
