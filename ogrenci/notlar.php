<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - dönem dönem not dökümü.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$donemler  = Ogrenci::notGecmisi((int) $kullanici['id']);

$sayfaBaslik = 'Notlarım';
$aktifMenu   = 'panel';
$panelAktif  = 'notlar';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Notlarım</h1>
        <p>Vize %40 · Final %60 · Harf notu ve ortalama otomatik hesaplanır</p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogrenci/transkript.php')) ?>">Transkript</a>
</div>

<?php if ($donemler === []): ?>
    <div class="kart bos-durum">Henüz not kaydın bulunmuyor.</div>
<?php endif; ?>

<?php foreach ($donemler as $donem): ?>
    <section class="kart" style="margin-bottom:18px">
        <div class="kart-baslik">
            <div>
                <h3 style="margin-bottom:2px"><?= e($donem['donem']) ?></h3>
                <span class="metin-silik metin-kucuk">
                    <?= e((string) count($donem['dersler'])) ?> ders ·
                    <?= e((string) $donem['kredi']) ?> kredi ·
                    <?= e((string) $donem['akts']) ?> AKTS
                </span>
            </div>
            <div style="text-align:right">
                <?php if ((int) $donem['aktif'] === 1): ?>
                    <span class="rozet rozet-basari">Aktif dönem</span>
                <?php endif; ?>
                <div class="donem-ortalama">
                    <?= $donem['ortalama'] !== null ? e(number_format((float) $donem['ortalama'], 2, ',', '.')) : '—' ?>
                    <small>ortalama</small>
                </div>
            </div>
        </div>

        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Ders</th>
                        <th class="metin-sag">Kredi</th>
                        <th class="metin-sag">Vize</th>
                        <th class="metin-sag">Final</th>
                        <th class="metin-sag">Ortalama</th>
                        <th class="metin-sag">Harf</th>
                        <th class="metin-sag">Puan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donem['dersler'] as $ders): ?>
                        <?php $puan = Ogrenci::harfPuani($ders['harf_notu'] !== null ? (string) $ders['harf_notu'] : null); ?>
                        <tr>
                            <td>
                                <strong><?= e($ders['kod']) ?></strong>
                                <div class="metin-silik metin-kucuk"><?= e($ders['ad']) ?></div>
                            </td>
                            <td class="metin-sag sayi"><?= e((string) $ders['kredi']) ?></td>
                            <td class="metin-sag sayi"><?= $ders['vize'] !== null ? e(number_format((float) $ders['vize'], 1, ',', '.')) : '—' ?></td>
                            <td class="metin-sag sayi"><?= $ders['final_notu'] !== null ? e(number_format((float) $ders['final_notu'], 1, ',', '.')) : '—' ?></td>
                            <td class="metin-sag sayi"><strong><?= $ders['ortalama'] !== null ? e(number_format((float) $ders['ortalama'], 2, ',', '.')) : '—' ?></strong></td>
                            <td class="metin-sag">
                                <?php if ($ders['harf_notu'] !== null): ?>
                                    <span class="rozet <?= e(Ogrenci::harfSinifi((string) $ders['harf_notu'])) ?>"><?= e($ders['harf_notu']) ?></span>
                                <?php else: ?>
                                    <span class="metin-silik">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="metin-sag sayi metin-soluk"><?= $puan !== null ? e(number_format($puan, 2, ',', '.')) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endforeach; ?>

<section class="kart">
    <div class="kart-baslik"><h3>Not Sistemi</h3></div>
    <div class="not-anahtar">
        <?php foreach (['AA' => '90+', 'BA' => '85-89', 'BB' => '80-84', 'CB' => '75-79', 'CC' => '70-74', 'DC' => '65-69', 'DD' => '60-64', 'FD' => '50-59', 'FF' => '0-49'] as $harf => $aralik): ?>
            <span class="rozet <?= e(Ogrenci::harfSinifi($harf)) ?>">
                <?= e($harf) ?> <span class="metin-kucuk">· <?= e($aralik) ?></span>
            </span>
        <?php endforeach; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
