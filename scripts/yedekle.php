<?php

declare(strict_types=1);

/**
 * Veritabanı yedekleme betiği (Faz 7).
 *
 * Kullanım (komut satırı):
 *   C:\xampp\php\php.exe scripts\yedekle.php
 *   C:\xampp\php\php.exe scripts\yedekle.php --sakla=5
 *
 * Yaptığı iş:
 *   - config/config.local.php içindeki bağlantı bilgileriyle mysqldump çalıştırır
 *   - yedekler/ klasörüne sanalokul-YYYYmmdd-HHMMSS.sql.gz yazar
 *   - eski yedeklerden en yenisini `sakla` kadar tutar (varsayılan 10)
 *
 * Windows Görev Zamanlayıcısı'na haftalık olarak eklenebilir.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu betik yalnızca komut satırından çalıştırılır.\n");
}

$kok = dirname(__DIR__);

$ayarDosyasi = $kok . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.local.php';

if (!is_file($ayarDosyasi)) {
    fwrite(STDERR, "Ayar dosyası bulunamadı: config/config.local.php\n");
    exit(1);
}

/** @var array{host:string,port?:int,name:string,user:string,pass:string} $ayar */
$ayar = require $ayarDosyasi;

$sakla = 10;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--sakla=(\d+)$/', $arg, $eslesme) === 1) {
        $sakla = max(1, (int) $eslesme[1]);
    }
}

$klasor = $kok . DIRECTORY_SEPARATOR . 'yedekler';

if (!is_dir($klasor) && !mkdir($klasor, 0775, true) && !is_dir($klasor)) {
    fwrite(STDERR, "Yedek klasörü oluşturulamadı: {$klasor}\n");
    exit(1);
}

$dosyaAdi = 'sanalokul-' . date('Ymd-His') . '.sql.gz';
$hedef    = $klasor . DIRECTORY_SEPARATOR . $dosyaAdi;
$gecici   = tempnam(sys_get_temp_dir(), 'sanalokul-yedek-');

if ($gecici === false) {
    fwrite(STDERR, "Geçici dosya oluşturulamadı.\n");
    exit(1);
}

$geciciSql = $gecici . '.sql';
rename($gecici, $geciciSql);

// mysqldump yolunu bul.
$adaylar = array_filter([
    getenv('MYSQLDUMP') ?: null,
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
    'mysqldump',
]);

$mysqldump = null;
foreach ($adaylar as $aday) {
    if ($aday === 'mysqldump') {
        // PATH üzerinden dene.
        $deneme = @shell_exec('where mysqldump 2>NUL');
        if (is_string($deneme) && trim($deneme) !== '') {
            $mysqldump = strtok(trim($deneme), "\r\n");
            break;
        }

        continue;
    }

    if (is_file($aday)) {
        $mysqldump = $aday;
        break;
    }
}

if ($mysqldump === null) {
    fwrite(STDERR, "mysqldump bulunamadı. MYSQLDUMP ortam değişkeni ile yol verin.\n");
    @unlink($geciciSql);
    exit(1);
}

// Şifreyi komut satırına yazmamak için geçici ayar dosyası.
$cnf = tempnam(sys_get_temp_dir(), 'sanalokul-cnf-');

if ($cnf === false) {
    fwrite(STDERR, "Geçici ayar dosyası oluşturulamadı.\n");
    @unlink($geciciSql);
    exit(1);
}

$cnfIcerik = "[client]\n"
    . 'host=' . (string) $ayar['host'] . "\n"
    . 'port=' . (string) ($ayar['port'] ?? 3306) . "\n"
    . 'user=' . (string) $ayar['user'] . "\n"
    . 'password=' . (string) $ayar['pass'] . "\n"
    . "default-character-set=utf8mb4\n";

file_put_contents($cnf, $cnfIcerik);

$komut = [
    $mysqldump,
    '--defaults-extra-file=' . $cnf,
    '--single-transaction',
    '--skip-lock-tables',
    '--routines',
    '--triggers',
    '--events',
    '--no-tablespaces',
    '--default-character-set=utf8mb4',
    (string) $ayar['name'],
];

$cikti = fopen($geciciSql, 'wb');

if ($cikti === false) {
    fwrite(STDERR, "Geçici SQL dosyası açılamadı.\n");
    @unlink($cnf);
    @unlink($geciciSql);
    exit(1);
}

$surec = proc_open($komut, [
    0 => ['pipe', 'r'],
    1 => $cikti,
    2 => ['pipe', 'w'],
], $borular);

if (!is_resource($surec)) {
    fwrite(STDERR, "mysqldump başlatılamadı.\n");
    fclose($cikti);
    @unlink($cnf);
    @unlink($geciciSql);
    exit(1);
}

fclose($borular[0]);
$hata = stream_get_contents($borular[2]);
fclose($borular[2]);
fclose($cikti);

$kod = proc_close($surec);
@unlink($cnf);

if ($kod !== 0) {
    fwrite(STDERR, "mysqldump hata verdi (kod {$kod}):\n" . trim((string) $hata) . "\n");
    @unlink($geciciSql);
    exit(1);
}

// SQL dosyasını gzip'e çevir (bellek şişmesin diye parça parça).
$giris = fopen($geciciSql, 'rb');
$gzip  = gzopen($hedef, 'wb9');

if ($giris === false || $gzip === false) {
    fwrite(STDERR, "Sıkıştırma açılamadı.\n");
    if (is_resource($giris)) {
        fclose($giris);
    }
    if ($gzip !== false) {
        gzclose($gzip);
    }
    @unlink($geciciSql);
    @unlink($hedef);
    exit(1);
}

while (!feof($giris)) {
    $parca = fread($giris, 262144);

    if ($parca === false || $parca === '') {
        break;
    }

    gzwrite($gzip, $parca);
}

fclose($giris);
gzclose($gzip);
@unlink($geciciSql);

if (!is_file($hedef) || filesize($hedef) === 0) {
    fwrite(STDERR, "Yedek dosyası boş oluştu: {$hedef}\n");
    @unlink($hedef);
    exit(1);
}

// Eski yedekleri temizle.
$yedekler = glob($klasor . DIRECTORY_SEPARATOR . 'sanalokul-*.sql.gz') ?: [];
rsort($yedekler);

$silinen = 0;
foreach (array_slice($yedekler, $sakla) as $eski) {
    if (@unlink($eski)) {
        $silinen++;
    }
}

printf(
    "Yedek alındı: %s (%.1f KB)%s\n",
    $dosyaAdi,
    filesize($hedef) / 1024,
    $silinen > 0 ? " · {$silinen} eski yedek silindi" : ''
);

exit(0);
