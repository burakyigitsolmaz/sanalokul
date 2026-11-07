<?php

declare(strict_types=1);

/**
 * Ortak başlangıç dosyası.
 * Her sayfanın en üstünde require edilir.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Ogrenci.php';
require_once __DIR__ . '/Ogretmen.php';
require_once __DIR__ . '/Yonetici.php';

date_default_timezone_set((string) config('uygulama.zaman_dilimi', 'Europe/Istanbul'));

Auth::baslat();
