<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - verdiği dersler.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$dersler   = Ogretmen::dersler((int) $kullanici['id']);

$aktifDersler  = array_values(array_filter($dersler, static fn (array $d): bool => (int) $d['aktif'] === 1));
$gecmisDersler = array_values(array_filter($dersler, static fn (array $d): bool => (int) $d['aktif'] === 0));

$sayfaBaslik = 'Derslerim';
$aktifMenu   = 'panel';
$panelAktif  = 'dersler';

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Derslerim</h1>
        <p><?= e((string) count($aktifDersler)) ?> aktif ders · <?= e((string) count($gecmisDersler)) ?> geçmiş dönem</p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogretmen/program.php')) ?>">Haftalık Program</a>
</div>

<div class="bolum-basligi">
    <h2 style="font-size:16px">Bu Dönem</h2>
    <span class="rozet rozet-vurgu"><?= e((string) count($aktifDersler)) ?> ders</span>
</div>

<?php if ($aktifDersler === []): ?>
    <div class="kart bos-durum">Bu dönem aktif dersiniz bulunmuyor.</div>
<?php else: ?>
    <div class="izgara izgara-2" style="margin-bottom:26px">
        <?php foreach ($aktifDersler as $ders): ?>
            <a class="ders-kart" href="<?= e(url('ogretmen/ders.php?id=' . (int) $ders['acilis_id'])) ?>">
                <div class="ders-kart-ust">
                    <div>
                        <span class="ders-kod"><?= e($ders['kod']) ?></span>
                        <span class="rozet" style="margin-left:6px"><?= e($ders['sube']) ?> şube</span>
                    </div>
                    <span class="rozet rozet-basari">Aktif</span>
                </div>

                <h3 class="ders-ad"><?= e($ders['ad']) ?></h3>

                <div class="ders-meta">
                    <span> <?= e((string) $ders['kredi']) ?> kredi · <?= e((string) $ders['akts']) ?> AKTS</span>
                    <span> <?= e((string) $ders['ogrenci_sayisi']) ?> öğrenci · <?= e($ders['donem']) ?></span>
                </div>

                <div class="ders-kart-alt">
                    <span class="rozet"> <?= e((string) $ders['icerik_sayisi']) ?> içerik</span>
                    <span class="rozet"> <?= e((string) $ders['odev_sayisi']) ?> ödev</span>
                    <?php if ((int) $ders['bekleyen_teslim'] > 0): ?>
                        <span class="rozet rozet-uyari"> <?= e((string) $ders['bekleyen_teslim']) ?> bekliyor</span>
                    <?php endif; ?>
                    <span class="rozet sayi">
                        Not: <?= e((string) $ders['notlu_sayisi']) ?>/<?= e((string) $ders['ogrenci_sayisi']) ?>
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
                        <th class="metin-sag">Öğrenci</th>
                        <th class="metin-sag">Notu Girilen</th>
                        <th class="metin-sag">Ödev</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gecmisDersler as $ders): ?>
                        <tr>
                            <td>
                                <strong><?= e($ders['kod']) ?></strong>
                                <span class="rozet" style="margin-left:5px"><?= e($ders['sube']) ?></span>
                                <div class="metin-silik metin-kucuk"><?= e($ders['ad']) ?></div>
                            </td>
                            <td class="metin-kucuk"><?= e($ders['donem']) ?></td>
                            <td class="metin-sag sayi"><?= e((string) $ders['ogrenci_sayisi']) ?></td>
                            <td class="metin-sag sayi"><?= e((string) $ders['notlu_sayisi']) ?></td>
                            <td class="metin-sag sayi"><?= e((string) $ders['odev_sayisi']) ?></td>
                            <td class="metin-sag">
                                <a class="baglanti metin-kucuk"
                                   href="<?= e(url('ogretmen/ders.php?id=' . (int) $ders['acilis_id'])) ?>">Aç →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
