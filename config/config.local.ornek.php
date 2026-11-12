<?php

declare(strict_types=1);

/**
 * Yerel veritabanı ayarları - ÖRNEK DOSYA
 *
 * `kur.bat` bu dosyayı `config/config.local.php` olarak kopyalar.
 * Gerçek dosya `.gitignore` içinde olduğu için depoya eklenmez.
 *
 * `database/00_kurulum.sql` içindeki kullanıcı ve şifre ile aynı olmalıdır.
 */
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'sanalokul',
    'user' => 'sanalokul_app',
    'pass' => 'SanalOkul_2026!App',
];
