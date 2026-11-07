<?php

declare(strict_types=1);

/**
 * 404 - Sayfa bulunamadı.
 */

require_once __DIR__ . '/src/bootstrap.php';

http_response_code(404);

$sayfaBaslik = 'Sayfa Bulunamadı';
$aktifMenu   = '';

require __DIR__ . '/templates/header.php';
?>
<div class="kart metin-orta" style="max-width:560px;margin:60px auto;padding:38px 30px">
    
    <h1 style="font-size:26px">404 · Sayfa Bulunamadı</h1>
    <p class="metin-soluk">
        Aradığınız sayfa taşınmış veya hiç var olmamış olabilir.
        Adresi kontrol edin ya da ana sayfaya dönün.
    </p>
    <div class="mt-3" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <a class="dugme dugme-birincil" href="<?= e(url('')) ?>">Ana Sayfa</a>
        <a class="dugme dugme-hayalet" href="<?= e(Auth::girisYapmisMi() ? Auth::panelYolu() : url('giris.php')) ?>">
            <?= Auth::girisYapmisMi() ? 'Panelime Dön' : 'Giriş Yap' ?>
        </a>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
