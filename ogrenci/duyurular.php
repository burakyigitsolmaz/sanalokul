<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - duyurular.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$duyurular = Ogrenci::duyurular((int) $kullanici['id'], 50);

$onemliler = array_values(array_filter($duyurular, static fn (array $d): bool => (int) $d['onemli'] === 1));

$sayfaBaslik = 'Duyurular';
$aktifMenu   = 'panel';
$panelAktif  = 'duyurular';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Duyurular</h1>
        <p><?= e((string) count($duyurular)) ?> duyuru · <?= e((string) count($onemliler)) ?> önemli</p>
    </div>
</div>

<?php if ($duyurular === []): ?>
    <div class="kart bos-durum">Yayında duyuru bulunmuyor.</div>
<?php else: ?>
    <div class="duyuru-listesi">
        <?php foreach ($duyurular as $duyuru): ?>
            <details class="kart duyuru-kart" <?= (int) $duyuru['onemli'] === 1 ? 'open' : '' ?>>
                <summary>
                    
                    <span class="duyuru-baslik">
                        <strong><?= e($duyuru['baslik']) ?></strong>
                        <span class="metin-kucuk metin-silik">
                            <?= e($duyuru['kapsam']) ?> ·
                            <?= e(date('d.m.Y H:i', strtotime((string) $duyuru['yayin_tarihi']))) ?>
                        </span>
                    </span>
                    <?php if ((int) $duyuru['onemli'] === 1): ?>
                        <span class="rozet rozet-uyari">Önemli</span>
                    <?php endif; ?>
                </summary>

                <div class="duyuru-govde">
                    <p><?= nl2br(e($duyuru['icerik'])) ?></p>
                    <div class="metin-kucuk metin-silik">
                        Yayınlayan:
                        <?= e(trim(($duyuru['yazar_unvan'] ?? '') . ' ' . $duyuru['yazar'])) ?>
                        <?php if (!empty($duyuru['bitis_tarihi'])): ?>
                            · Geçerlilik: <?= e(tarih_tr((string) $duyuru['bitis_tarihi'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
