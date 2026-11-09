<?php

declare(strict_types=1);

/**
 * Tema tercihini kaydeder (AJAX).
 * Giriş yapılmamışsa sadece başarılı yanıt döner; tercih tarayıcıda tutulur.
 */

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mesaj' => 'Yalnızca POST destekleniyor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tema = (string) ($_POST['tema'] ?? '');

if (!in_array($tema, ['acik', 'koyu'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz tema değeri.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$kaydedildi = Auth::temaKaydet($tema);

echo json_encode([
    'ok'       => $kaydedildi,
    'tema'     => $tema,
    'girisli'  => Auth::girisYapmisMi(),
], JSON_UNESCAPED_UNICODE);
