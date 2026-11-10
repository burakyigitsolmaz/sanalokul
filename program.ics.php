<?php

declare(strict_types=1);

/**
 * Haftalık ders programını iCalendar (.ics) dosyası olarak indirir.
 *
 * Her oturum, aktif dönemin başlangıcından bitişine kadar haftalık tekrar
 * eden bir etkinlik olarak yazılır. Saatler Europe/Istanbul'dan UTC'ye
 * çevrilir; böylece Google, Apple ve Outlook takvimlerinde doğru görünür.
 */

require_once __DIR__ . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci', 'ogretim_elemani']);

$kullanici = Auth::kullanici();
$rol       = (string) Auth::rol();

$program = $rol === 'ogrenci'
    ? Ogrenci::haftalikProgram((int) $kullanici['id'])
    : Ogretmen::program((int) $kullanici['id']);

$donem = Database::get()
    ->query('SELECT ad, baslangic, bitis FROM donemler WHERE aktif = 1 LIMIT 1')
    ->fetch();

if ($donem === false || $program === []) {
    http_response_code(404);
    exit('Aktif dönem için ders programı bulunamadı.');
}

$dilim = new DateTimeZone((string) config('uygulama.zaman_dilimi', 'Europe/Istanbul'));
$utc   = new DateTimeZone('UTC');

$baslangic = new DateTimeImmutable((string) $donem['baslangic'], $dilim);
$bitis     = new DateTimeImmutable((string) $donem['bitis'] . ' 23:59:59', $dilim);

$until = $bitis->setTimezone($utc)->format('Ymd\THis\Z');
$stamp = (new DateTimeImmutable('now', $utc))->format('Ymd\THis\Z');

/** iCalendar metin kaçışı (RFC 5545). */
$kacis = static function (string $metin): string {
    return str_replace(
        ["\\", ';', ',', "\r\n", "\n", "\r"],
        ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
        $metin
    );
};

$satirlar = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Nallihan MYO Sanal Okul//Ders Programi//TR',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'X-WR-CALNAME:' . $kacis('Haftalık Ders Programı'),
    'X-WR-TIMEZONE:' . $dilim->getName(),
];

foreach ($program as $sira => $oturum) {
    $gun  = (int) $oturum['gun'];
    $fark = ($gun - (int) $baslangic->format('N') + 7) % 7;
    $tarih = $baslangic->modify('+' . $fark . ' days')->format('Y-m-d');

    $bas = new DateTimeImmutable($tarih . ' ' . $oturum['baslangic_saati'], $dilim);
    $bit = new DateTimeImmutable($tarih . ' ' . $oturum['bitis_saati'], $dilim);

    $ozet     = (string) $oturum['kod'] . ' - ' . (string) $oturum['ders_adi'];
    $aciklama = $rol === 'ogrenci'
        ? 'Öğretim elemanı: ' . (string) $oturum['hoca']
        : 'Şube: ' . (string) $oturum['sube'];

    $uid = sprintf(
        'sanalokul-%s-%d-%d-%s@nallihan.edu.tr',
        $rol,
        (int) $kullanici['id'],
        $sira,
        $bas->format('Ymd\THis')
    );

    $satirlar[] = 'BEGIN:VEVENT';
    $satirlar[] = 'UID:' . $uid;
    $satirlar[] = 'DTSTAMP:' . $stamp;
    $satirlar[] = 'DTSTART:' . $bas->setTimezone($utc)->format('Ymd\THis\Z');
    $satirlar[] = 'DTEND:' . $bit->setTimezone($utc)->format('Ymd\THis\Z');
    $satirlar[] = 'RRULE:FREQ=WEEKLY;UNTIL=' . $until;
    $satirlar[] = 'SUMMARY:' . $kacis($ozet);
    $satirlar[] = 'LOCATION:' . $kacis((string) $oturum['derslik']);
    $satirlar[] = 'DESCRIPTION:' . $kacis($aciklama);
    $satirlar[] = 'END:VEVENT';
}

$satirlar[] = 'END:VCALENDAR';

$dosya = 'haftalik-program-' . $rol . '.ics';

Auth::log('Haftalık ders programı takvime eklendi (ICS)', 'ders_programi', 0);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dosya . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo implode("\r\n", $satirlar) . "\r\n";
exit;
