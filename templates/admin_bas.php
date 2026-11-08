<?php
/**
 * Yönetici paneli ortak düzeni: sol menü + içerik başlangıcı.
 *
 * Beklenen değişkenler:
 *   $panelAktif  string  panel | kullanicilar | bolumler | donemler | dersler |
 *                        acilislar | atama | duyurular | loglar | csv
 */

declare(strict_types=1);

$panelAktif = $panelAktif ?? 'panel';

require __DIR__ . '/header.php';

$panelKullanici = Auth::kullanici();

$panelMenu = [
    ['anahtar' => 'panel',       'ad' => 'Panelim',           'yol' => url('admin/')],
    ['anahtar' => 'kullanicilar', 'ad' => 'Kullanıcılar',     'yol' => url('admin/kullanicilar.php')],
    ['anahtar' => 'bolumler',    'ad' => 'Bölümler',          'yol' => url('admin/bolumler.php')],
    ['anahtar' => 'donemler',    'ad' => 'Dönemler',          'yol' => url('admin/donemler.php')],
    ['anahtar' => 'dersler',     'ad' => 'Dersler',           'yol' => url('admin/dersler.php')],
    ['anahtar' => 'acilislar',   'ad' => 'Ders Açılışları',   'yol' => url('admin/acilislar.php')],
    ['anahtar' => 'atama',       'ad' => 'Derse Atama',       'yol' => url('admin/atama.php')],
    ['anahtar' => 'raporlar',    'ad' => 'Raporlar',          'yol' => url('admin/raporlar.php')],
    ['anahtar' => 'duyurular',   'ad' => 'Duyurular',         'yol' => url('admin/duyurular.php')],
    ['anahtar' => 'loglar',      'ad' => 'İşlem Logları',     'yol' => url('admin/loglar.php')],
    ['anahtar' => 'csv',         'ad' => 'CSV Aktarma',       'yol' => url('admin/csv.php')],
    ['anahtar' => 'senkron',     'ad' => 'Veri Senkronu',     'yol' => url('admin/senkron.php')],
];
?>
<div class="panel-duzen">
    <aside class="panel-yan">
        <?php if ($panelKullanici !== null): ?>
            <div class="panel-kimlik">
                <span class="avatar" aria-hidden="true"><?= e($panelKullanici['bas_harf']) ?></span>
                <span class="panel-ad">
                    <strong><?= e($panelKullanici['ad_soyad']) ?></strong>
                    <span>Sistem Yöneticisi</span>
                </span>
            </div>
        <?php endif; ?>

        <nav class="panel-menu" aria-label="Yönetici paneli menüsü">
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
