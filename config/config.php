<?php

declare(strict_types=1);

/**
 * Uygulama genel ayarları.
 * Gizli bilgiler (veritabanı şifresi vb.) burada DEĞİL, config.local.php içinde durur.
 */
return [
    'uygulama' => [
        'ad'           => 'Nallıhan MYO Sanal Okul',
        'kisa_ad'      => 'Sanal Okul',
        'surum'        => '0.1.0',
        // XAMPP'te proje alt klasörde çalıştığı için taban yol tanımlı.
        'taban_yol'    => '/sanalokul',
        'zaman_dilimi' => 'Europe/Istanbul',
        'ortam'        => 'gelistirme', // gelistirme | sunum
    ],

    'oturum' => [
        'ad'   => 'sanalokul_oturum',
        'omur' => 7200, // saniye (2 saat)
    ],

    'guvenlik' => [
        'giris_deneme_limiti' => 5,   // aynı hesap için 5 hatalı denemeden sonra
        'ip_deneme_limiti'    => 25,  // aynı ağdan 25 hatalı denemeden sonra
        'kilit_suresi'        => 900, // 15 dakika bekleme
    ],

    'yukleme' => [
        'maks_bayt'        => 10 * 1024 * 1024, // 10 MB
        'izinli_uzantilar' => ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg', 'jpeg'],
        'izinli_mime'      => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
            'image/png',
            'image/jpeg',
        ],
        'klasor' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads',
    ],
];
