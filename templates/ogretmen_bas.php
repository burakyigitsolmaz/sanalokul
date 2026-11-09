<?php
/**
 * Öğretim elemanı paneli ortak düzeni: sol menü + içerik başlangıcı.
 *
 * Beklenen değişkenler:
 *   $panelAktif  string  panel | dersler | odevler | duyurular | program
 */

declare(strict_types=1);

$panelAktif = $panelAktif ?? 'panel';

require __DIR__ . '/header.php';

$panelKullanici = Auth::kullanici();

$panelMenu = [
    ['anahtar' => 'panel',     'ad' => 'Panelim',       'yol' => url('ogretmen/')],
    ['anahtar' => 'dersler',   'ad' => 'Derslerim',     'yol' => url('ogretmen/dersler.php')],
    ['anahtar' => 'odevler',   'ad' => 'Ödevler',       'yol' => url('ogretmen/odevler.php')],
    ['anahtar' => 'duyurular', 'ad' => 'Duyurular',     'yol' => url('ogretmen/duyurular.php')],
    ['anahtar' => 'program',   'ad' => 'Ders Programı', 'yol' => url('ogretmen/program.php')],
];
?>
<div class="panel-duzen">
    <aside class="panel-yan">
        <?php if ($panelKullanici !== null): ?>
            <div class="panel-kimlik">
                <span class="avatar" aria-hidden="true"><?= e($panelKullanici['bas_harf']) ?></span>
                <span class="panel-ad">
                    <strong><?= e(trim(($panelKullanici['unvan'] ?? '') . ' ' . $panelKullanici['ad_soyad'])) ?></strong>
                    <span>Öğretim Elemanı<?= !empty($panelKullanici['bolum']) ? ' · ' . e($panelKullanici['bolum']) : '' ?></span>
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

        <nav class="panel-menu" id="panelMenu" aria-label="Öğretim elemanı paneli menüsü">
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
