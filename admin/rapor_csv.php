<?php

declare(strict_types=1);

/**
 * Yönetici paneli - dönem raporunu CSV olarak indirir (Faz 6).
 *
 * Çıktı: UTF-8 BOM + noktalı virgül ayırıcı (Türkçe Excel uyumlu).
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$donemId = Rapor::donemSec(isset($_GET['donem']) ? (int) $_GET['donem'] : null);
$donem   = Rapor::donem($donemId);

if ($donem === null) {
    http_response_code(404);
    exit('Dönem bulunamadı.');
}

$csv = Rapor::csvDersRaporu($donemId);

$slug = strtr((string) $donem['ad'], [
    'Ç' => 'c', 'ç' => 'c',
    'Ğ' => 'g', 'ğ' => 'g',
    'İ' => 'i', 'ı' => 'i',
    'Ö' => 'o', 'ö' => 'o',
    'Ş' => 's', 'ş' => 's',
    'Ü' => 'u', 'ü' => 'u',
]);
$slug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug)), '-');
$dosya = 'ders-raporu-' . ($slug === '' ? 'donem' : $slug) . '.csv';

Auth::log('Dönem raporu CSV indirildi: ' . $donem['ad'], 'ders_acilislari', 0);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dosya . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$cikti = fopen('php://output', 'w');

if ($cikti === false) {
    exit;
}

// Excel'in UTF-8'i doğru okuması için BOM.
fwrite($cikti, "\xEF\xBB\xBF");

fputcsv($cikti, ['Dönem', $donem['ad']], ';');
fputcsv($cikti, ['Rapor tarihi', date('d.m.Y H:i')], ';');
fputcsv($cikti, [], ';');
fputcsv($cikti, $csv['basliklar'], ';');

foreach ($csv['satirlar'] as $satir) {
    fputcsv($cikti, $satir, ';');
}

fclose($cikti);
exit;
