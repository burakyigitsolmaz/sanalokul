<?php
/**
 * Ortak üst çubuk, mobil çekmece menü + ana içerik başlangıcı.
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
        'ad'      => 'Giriş Yap',
        'yol'     => url('giris.php'),
        'anahtar' => 'giris',
        'sinif'   => 'menu-mobil',
    ];
}

$flash = Auth::flashOku();
?>
<a class="gizli-baglanti" href="#icerik" style="position:absolute;left:-9999px">İçeriğe geç</a>

<header class="ust-cubuk">
    <div class="kapsayici ust-ic">
        <a class="marka" href="<?= e(url('')) ?>">
            <span class="marka-logo">
                <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Ankara Üniversitesi Nallıhan Meslek Yüksekokulu">
            </span>
            <span class="marka-metin">
                <strong>Nallıhan MYO</strong>
                <span>Sanal Okul</span>
            </span>
        </a>

        <span class="marka-ayrac" aria-hidden="true"></span>

        <nav class="ust-menu" id="ustMenu" aria-label="Ana menü">
            <?php foreach ($menuOgeleri as $oge): ?>
                <a class="menu-baglanti <?= $aktifMenu === $oge['anahtar'] ? 'aktif' : '' ?> <?= e($oge['sinif'] ?? '') ?>"
                   href="<?= e($oge['yol']) ?>"><?= e($oge['ad']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ust-sag">
            <button class="tema-dugme" id="temaDugme" type="button"
                    title="Tema değiştir" aria-label="Tema değiştir">
                <svg class="ikon-ay" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                </svg>
                <svg class="ikon-gunes" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
            </button>

            <?php if ($kullanici !== null): ?>
                <div class="kullanici-kutu">
                    <span class="avatar" aria-hidden="true"><?= e($kullanici['bas_harf']) ?></span>
                    <span class="kullanici-bilgi">
                        <strong><?= e($kullanici['ad_soyad']) ?></strong>
                        <span><?= e(Auth::rolEtiketi()) ?></span>
                    </span>
                </div>
                <form class="cikis-form" method="post" action="<?= e(url('cikis.php')) ?>">
                    <?= Auth::csrfAlani() ?>
                    <button class="cikis-dugme" type="submit" title="Çıkış yap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                        Çıkış
                    </button>
                </form>
            <?php else: ?>
                <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('giris.php')) ?>">Giriş Yap</a>
            <?php endif; ?>

            <button class="menu-dugme" id="menuDugme" type="button"
                    aria-label="Menüyü aç" aria-expanded="false" aria-controls="cekmece">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>
    </div>
</header>

<div class="cekmece-katman" id="cekmeceKatman" aria-hidden="true"></div>

<aside class="cekmece" id="cekmece" aria-label="Mobil menü" aria-hidden="true">
    <div class="cekmece-ust">
        <span class="cekmece-baslik">Menü</span>
        <button class="cekmece-kapat" id="cekmeceKapat" type="button" aria-label="Menüyü kapat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18"/>
            </svg>
        </button>
    </div>

    <div class="cekmece-govde">
        <?php if ($kullanici !== null): ?>
            <div class="cekmece-kimlik">
                <span class="avatar" aria-hidden="true"><?= e($kullanici['bas_harf']) ?></span>
                <span>
                    <strong><?= e($kullanici['ad_soyad']) ?></strong>
                    <span><?= e(Auth::rolEtiketi()) ?></span>
                </span>
            </div>
        <?php endif; ?>

        <?php foreach ($menuOgeleri as $oge): ?>
            <a class="menu-baglanti <?= $aktifMenu === $oge['anahtar'] ? 'aktif' : '' ?>"
               href="<?= e($oge['yol']) ?>"><?= e($oge['ad']) ?></a>
        <?php endforeach; ?>

        <div class="cekmece-ayrac"></div>

        <div class="cekmece-tema">
            <span>Tema</span>
            <button class="cekmece-tema-dugme" type="button" data-tema-degistir>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                </svg>
                <span data-tema-etiket>Koyu</span>
            </button>
        </div>

        <?php if ($kullanici !== null): ?>
            <form method="post" action="<?= e(url('cikis.php')) ?>">
                <?= Auth::csrfAlani() ?>
                <button class="cekmece-cikis" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <path d="M16 17l5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                    Çıkış yap
                </button>
            </form>
        <?php endif; ?>
    </div>
</aside>

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
