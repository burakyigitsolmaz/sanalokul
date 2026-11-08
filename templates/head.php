<?php
/**
 * Sayfa başı: <head> ve tema başlatma.
 *
 * Beklenen değişkenler (opsiyonel):
 *   $sayfaBaslik  string
 *   $govdeSinifi  string
 */

declare(strict_types=1);

$sayfaBaslik = $sayfaBaslik ?? (string) config('uygulama.ad');
$govdeSinifi = $govdeSinifi ?? '';
$temaSecim   = Auth::tema();
?>
<!DOCTYPE html>
<html lang="tr" data-tema="koyu" data-tema-secim="<?= e($temaSecim) ?>" data-tema-adres="<?= e(url('tema.php')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Ankara Üniversitesi Nallıhan MYO Sanal Okul - ders projesi">
    <title><?= e($sayfaBaslik) ?> · <?= e(config('uygulama.ad')) ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('assets/img/logo.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
    <script nonce="<?= e(Guvenlik::nonce()) ?>">
        /* Tema, sayfa boyanmadan önce uygulanır (flash önleme). */
        (function () {
            try {
                var secim = localStorage.getItem('sanalokul-tema')
                    || document.documentElement.getAttribute('data-tema-secim')
                    || 'sistem';
                var gercek = secim;
                if (secim === 'sistem') {
                    gercek = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                        ? 'koyu'
                        : 'acik';
                }
                document.documentElement.setAttribute('data-tema', gercek);
                document.documentElement.setAttribute('data-tema-secim', secim);
            } catch (e) {
                /* localStorage kapalıysa varsayılan koyu tema kalır */
            }
        })();
    </script>
</head>
<body class="<?= e($govdeSinifi) ?>">
