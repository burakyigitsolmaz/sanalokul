<?php
/**
 * 403 - Yetkisiz erişim.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

http_response_code(403);

$sayfaBaslik = 'Yetkisiz Erişim';
$aktifMenu   = '';

require __DIR__ . '/header.php';
?>
<div class="kart metin-orta" style="max-width:560px;margin:60px auto;padding:38px 30px">
    
    <h1 style="font-size:26px">403 · Yetkisiz Erişim</h1>
    <p class="metin-soluk">
        Bu sayfayı görüntülemek için gerekli yetkiye sahip değilsiniz.
        <?php if (Auth::girisYapmisMi()): ?>
            Şu an <strong><?= e(Auth::rolEtiketi()) ?></strong> hesabıyla giriş yapmış durumdasınız.
        <?php else: ?>
            Lütfen giriş yapın.
        <?php endif; ?>
    </p>
    <div class="mt-3" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <a class="dugme dugme-birincil" href="<?= e(Auth::girisYapmisMi() ? Auth::panelYolu() : url('giris.php')) ?>">
            <?= Auth::girisYapmisMi() ? 'Panelime Dön' : 'Giriş Yap' ?>
        </a>
        <a class="dugme dugme-hayalet" href="<?= e(url('')) ?>">Ana Sayfa</a>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
