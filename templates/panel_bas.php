<?php
/**
 * Öğrenci paneli ortak düzeni: sol menü + içerik başlangıcı.
 *
 * Beklenen değişkenler:
 *   $panelAktif  string  panel | dersler | program | notlar | transkript | odevler | duyurular
 *
 * Kullanım:
 *   require __DIR__ . '/../templates/panel_bas.php';
 *   ... sayfa içeriği ...
 *   require __DIR__ . '/../templates/panel_son.php';
 */

declare(strict_types=1);

$panelAktif = $panelAktif ?? 'panel';

require __DIR__ . '/header.php';

$panelKullanici = Auth::kullanici();

$panelMenu = [
    ['anahtar' => 'panel',      'ad' => 'Panelim',        'yol' => url('ogrenci/')],
    ['anahtar' => 'dersler',    'ad' => 'Derslerim',      'yol' => url('ogrenci/dersler.php')],
    ['anahtar' => 'program',    'ad' => 'Ders Programı',  'yol' => url('ogrenci/program.php')],
    ['anahtar' => 'notlar',     'ad' => 'Notlarım',       'yol' => url('ogrenci/notlar.php')],
    ['anahtar' => 'transkript', 'ad' => 'Transkript',     'yol' => url('ogrenci/transkript.php')],
    ['anahtar' => 'odevler',    'ad' => 'Ödevler',        'yol' => url('ogrenci/odevler.php')],
    ['anahtar' => 'duyurular',  'ad' => 'Duyurular',      'yol' => url('ogrenci/duyurular.php')],
];
?>
<div class="panel-duzen">
    <aside class="panel-yan">
        <?php if ($panelKullanici !== null): ?>
            <div class="panel-kimlik">
                <span class="avatar" aria-hidden="true"><?= e($panelKullanici['bas_harf']) ?></span>
                <span class="panel-ad">
                    <strong><?= e($panelKullanici['ad_soyad']) ?></strong>
                    <span>
                        <?= e($panelKullanici['numara']) ?>
                        <?php if (!empty($panelKullanici['sinif'])): ?>
                            · <?= e((string) $panelKullanici['sinif']) ?>. sınıf
                        <?php endif; ?>
                    </span>
                </span>
            </div>
        <?php endif; ?>

        <button class="panel-menu-dugme" type="button" data-panel-menu
                aria-expanded="false" aria-controls="panelMenu">
            <span>Bölümler</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </button>

        <nav class="panel-menu" id="panelMenu" aria-label="Öğrenci paneli menüsü">
            <?php foreach ($panelMenu as $oge): ?>
                <a class="<?= $panelAktif === $oge['anahtar'] ? 'aktif' : '' ?>"
                   href="<?= e($oge['yol']) ?>"
                   <?= $panelAktif === $oge['anahtar'] ? 'aria-current="page"' : '' ?>>
                    
                    <?= e($oge['ad']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="panel-alt">
            <a class="panel-alt-link" href="<?= e(url('')) ?>">← Ana Sayfa</a>
        </div>
    </aside>

    <div class="panel-icerik">
