<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - kayıtlı dersler.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$dersler   = Ogrenci::dersler((int) $kullanici['id']);

$aktifDersler  = array_values(array_filter($dersler, static fn (array $d): bool => (int) $d['aktif'] === 1));
$gecmisDersler = array_values(array_filter($dersler, static fn (array $d): bool => (int) $d['aktif'] === 0));

$ozet = Ogrenci::donemOzeti($aktifDersler);

$sayfaBaslik = 'Derslerim';
$aktifMenu   = 'panel';
$panelAktif  = 'dersler';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Derslerim</h1>
        <p>
            Bu dönem <?= e((string) count($aktifDersler)) ?> ders ·
            <?= e((string) $ozet['kredi']) ?> kredi ·
            <?= e((string) $ozet['akts']) ?> AKTS
        </p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogrenci/program.php')) ?>">Haftalık Program</a>
</div>

<div class="bolum-basligi">
    <h2 style="font-size:16px">Bu Dönem</h2>
    <span class="rozet rozet-vurgu"><?= e((string) count($aktifDersler)) ?> ders</span>
</div>

<?php if ($aktifDersler === []): ?>
    <div class="kart bos-durum">Bu dönem kayıtlı dersin bulunmuyor.</div>
<?php else: ?>
    <div class="izgara izgara-2" style="margin-bottom:26px">
        <?php foreach ($aktifDersler as $ders): ?>
            <?php
            $durum = $ders['harf_notu'] !== null ? 'notlandi' : ($ders['vize'] !== null ? 'devam' : 'bekliyor');
            $sinif = Ogrenci::harfSinifi($ders['harf_notu'] !== null ? (string) $ders['harf_notu'] : null);
            ?>
            <a class="ders-kart" href="<?= e(url('ogrenci/ders.php?id=' . (int) $ders['kayit_id'])) ?>">
                <div class="ders-kart-ust">
                    <div>
                        <span class="ders-kod"><?= e($ders['kod']) ?></span>
                        <span class="rozet" style="margin-left:6px"><?= e($ders['sube']) ?> şube</span>
                    </div>
                    <?php if ($ders['harf_notu'] !== null): ?>
                        <span class="rozet <?= e($sinif) ?>"><?= e($ders['harf_notu']) ?></span>
                    <?php else: ?>
                        <span class="rozet <?= $durum === 'devam' ? 'rozet-uyari' : '' ?>">
                            <?= $durum === 'devam' ? 'Not bekliyor' : 'Başlamadı' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h3 class="ders-ad"><?= e($ders['ad']) ?></h3>

                <div class="ders-meta">
                    <span> <?= e($ders['hoca_unvan'] ?? '') ?> <?= e($ders['hoca']) ?></span>
                    <span> <?= e((string) $ders['kredi']) ?> kredi · <?= e((string) $ders['akts']) ?> AKTS</span>
                </div>

                <div class="ders-kart-alt">
                    <span class="rozet"> <?= e((string) $ders['icerik_sayisi']) ?> içerik</span>
                    <span class="rozet"> <?= e((string) $ders['teslim_sayisi']) ?>/<?= e((string) $ders['odev_sayisi']) ?> ödev</span>
                    <span class="rozet sayi">
                        Ort: <?= $ders['ortalama'] !== null ? e(number_format((float) $ders['ortalama'], 1, ',', '.')) : '—' ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($gecmisDersler !== []): ?>
    <div class="bolum-basligi">
        <h2 style="font-size:16px">Geçmiş Dönemler</h2>
        <span class="rozet"><?= e((string) count($gecmisDersler)) ?> ders</span>
    </div>

    <section class="kart">
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Ders</th>
                        <th>Dönem</th>
                        <th class="metin-sag">Vize</th>
                        <th class="metin-sag">Final</th>
                        <th class="metin-sag">Ortalama</th>
                        <th class="metin-sag">Harf</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gecmisDersler as $ders): ?>
                        <tr>
                            <td>
                                <strong><?= e($ders['kod']) ?></strong>
                                <div class="metin-silik metin-kucuk"><?= e($ders['ad']) ?></div>
                            </td>
                            <td class="metin-kucuk"><?= e($ders['donem']) ?></td>
                            <td class="metin-sag sayi"><?= $ders['vize'] !== null ? e(number_format((float) $ders['vize'], 1, ',', '.')) : '—' ?></td>
                            <td class="metin-sag sayi"><?= $ders['final_notu'] !== null ? e(number_format((float) $ders['final_notu'], 1, ',', '.')) : '—' ?></td>
                            <td class="metin-sag sayi"><?= $ders['ortalama'] !== null ? e(number_format((float) $ders['ortalama'], 1, ',', '.')) : '—' ?></td>
                            <td class="metin-sag">
                                <?php if ($ders['harf_notu'] !== null): ?>
                                    <span class="rozet <?= e(Ogrenci::harfSinifi((string) $ders['harf_notu'])) ?>"><?= e($ders['harf_notu']) ?></span>
                                <?php else: ?>
                                    <span class="metin-silik">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
