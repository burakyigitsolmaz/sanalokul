<?php
/**
 * Ortak üst çubuk + ana içerik başlangıcı.
 *
 * Beklenen değişkenler (opsiyonel):
 *   $sayfaBaslik  string
 *   $aktifMenu    string  ana | panel
 */

declare(strict_types=1);

$aktifMenu = $aktifMenu ?? '';

require __DIR__ . '/head.php';

$kullanici = Auth::kullanici();

$menuOgeleri = [
    ['ad' => 'Ana Sayfa', 'yol' => url(''), 'anahtar' => 'ana'],
];

if ($kullanici !== null) {
    $menuOgeleri[] = [
        'ad'      => $kullanici['rol'] === 'ogrenci' ? 'Panelim' : 'Kontrol Paneli',
        'yol'     => Auth::panelYolu(),
        'anahtar' => 'panel',
    ];
} else {
    $menuOgeleri[] = [
        'ad'     => 'Giriş Yap',
        'yol'    => url('giris.php'),
        'anahtar' => 'giris',
        'sinif'  => 'menu-mobil',
    ];
}

$flash = Auth::flashOku();
?>
<a class="gizli-baglanti" href="#icerik" style="position:absolute;left:-9999px">İçeriğe geç</a>

<header class="ust-cubuk">
    <div class="kapsayici ust-ic">
        <a class="marka" href="<?= e(url('')) ?>">
            <span class="marka-logo">
                <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Ankara Üniversitesi Nallıhan MYO">
            </span>
            <span class="marka-metin">
                <strong><?= e(config('uygulama.kisa_ad')) ?></strong>
                <span>Nallıhan Meslek Yüksekokulu</span>
            </span>        </a>

        <nav class="ust-menu" id="ustMenu" aria-label="Ana menü">
            <?php foreach ($menuOgeleri as $oge): ?>
                <a class="menu-baglanti <?= $aktifMenu === $oge['anahtar'] ? 'aktif' : '' ?> <?= e($oge['sinif'] ?? '') ?>"
                   href="<?= e($oge['yol']) ?>"><?= e($oge['ad']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ust-sag">
            <button class="tema-dugme" id="temaDugme" type="button" title="Tema değiştir" aria-label="Tema değiştir">◐</button>

            <?php if ($kullanici !== null): ?>
                <div class="kullanici-kutu">
                    <span class="avatar" aria-hidden="true"><?= e($kullanici['bas_harf']) ?></span>
                    <span class="kullanici-bilgi">
                        <strong><?= e($kullanici['ad_soyad']) ?></strong>
                        <span><?= e(Auth::rolEtiketi()) ?></span>
                    </span>
                    <form class="cikis-form" method="post" action="<?= e(url('cikis.php')) ?>">
                        <?= Auth::csrfAlani() ?>
                        <button class="cikis-dugme" type="submit" title="Çıkış yap" aria-label="Çıkış yap"></button>
                    </form>
                </div>
            <?php else: ?>
                <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('giris.php')) ?>">Giriş Yap</a>
            <?php endif; ?>

            <button class="menu-dugme" id="menuDugme" type="button"
                    aria-label="Menüyü aç" aria-expanded="false" aria-controls="ustMenu">☰</button>
        </div>
    </div>
</header>

<main class="ana-icerik" id="icerik">
    <div class="kapsayici">
        <?php if ($flash !== null): ?>
            <?php
            $tipSinifi = match ($flash['tip']) {
                'basari' => 'uyari-basari',
                'hata'   => 'uyari-hata',
                'uyari'  => 'uyari-uyari',
                default  => 'uyari-bilgi',
            };
            $ikon = match ($flash['tip']) {
                'basari' => '',
                'hata'   => '✕',
                'uyari'  => '!',
                default  => 'i',
            };
            ?>
            <div class="uyari <?= e($tipSinifi) ?>" data-kapanir role="status">
                <span class="uyari-ikon"><?= e($ikon) ?></span>
                <span class="esnek"><?= e($flash['mesaj']) ?></span>
                <button class="uyari-kapat" type="button" aria-label="Kapat">✕</button>
            </div>
        <?php endif; ?>
