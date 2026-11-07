<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

// Çıkış yalnızca POST + CSRF ile yapılır (GET ile oturum düşürülemez).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url(''));
    exit;
}

if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
    Auth::flash('hata', 'Çıkış isteği doğrulanamadı.');
    header('Location: ' . url(''));
    exit;
}

Auth::cikisYap();

header('Location: ' . url('giris.php'));
exit;
