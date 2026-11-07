<?php

declare(strict_types=1);

/**
 * Öğretim elemanı - öğrenci teslim dosyasını indirme.
 *
 * Dosyalar uploads/ klasöründe doğrudan erişime kapalıdır; indirme yalnızca
 * dersin hocası için bu uç üzerinden yapılır.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$teslimId  = (int) ($_GET['id'] ?? 0);

$teslim = $teslimId > 0 ? Ogretmen::teslim((int) $kullanici['id'], $teslimId) : null;

if ($teslim === null) {
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    exit;
}

$goreli = (string) $teslim['dosya_yolu'];

if (!str_starts_with($goreli, 'uploads/') || str_contains($goreli, '..')) {
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    exit;
}

// "uploads/demo/x.pdf" -> uploads klasörü + "demo/x.pdf"
$kok  = (string) realpath((string) config('yukleme.klasor', ''));
$kisa = substr($goreli, strlen('uploads/'));
$tam  = (string) realpath($kok . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $kisa));

if ($kok === '' || $tam === '' || !is_file($tam) || !str_starts_with($tam, $kok . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    exit;
}

$uzanti = strtolower((string) pathinfo($tam, PATHINFO_EXTENSION));
$guvenliAd = preg_replace('/[^A-Za-z0-9._-]/', '_', $teslim['kod'] . '_' . $teslim['numara']) . '_' . $teslimId;

if ($uzanti !== '') {
    $guvenliAd .= '.' . preg_replace('/[^a-z0-9]/', '', $uzanti);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $guvenliAd . '"');
header('Content-Length: ' . (string) filesize($tam));
header('Cache-Control: private, no-store, max-age=0');

Auth::log('Teslim dosyası indirildi', 'teslimler', $teslimId);

readfile($tam);
exit;
