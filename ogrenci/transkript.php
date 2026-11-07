<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - transkript.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici  = Auth::kullanici();
$transkript = Ogrenci::transkript((int) $kullanici['id']);

$gano = $transkript['gano'];

$durum = match (true) {
    $gano === null   => 'Henüz oluşmadı',
    $gano >= 3.50    => 'Yüksek onur',
    $gano >= 3.00    => 'Onur',
    $gano >= 2.00    => 'Başarılı',
    default          => 'Riskli',
};

$sayfaBaslik = 'Transkript';
$aktifMenu   = 'panel';
$panelAktif  = 'transkript';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Transkript</h1>
        <p><?= e($kullanici['numara']) ?> · <?= e($kullanici['ad_soyad']) ?> · <?= e($kullanici['bolum'] ?? '-') ?></p>
    </div>
    <button class="dugme dugme-hayalet dugme-kucuk" type="button" onclick="window.print()"> Yazdır</button>
</div>

<div class="izgara izgara-4" style="margin-bottom:22px">
    <div class="stat-kart">
        <div class="etiket">GANO</div>
        <div class="deger"><?= $gano !== null ? e(number_format($gano, 2, ',', '.')) : '—' ?></div>
        <div class="alt"><?= e($durum) ?></div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Toplam Kredi</div>
        <div class="deger"><?= e((string) $transkript['kredi']) ?></div>
        <div class="alt"><?= e((string) $transkript['akts']) ?> AKTS</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Ders</div>
        <div class="deger"><?= e((string) $transkript['ders_sayisi']) ?></div>
        <div class="alt"><?= e((string) $transkript['basarili']) ?> başarılı</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Dönem</div>
        <div class="deger"><?= e((string) $transkript['donem_sayisi']) ?></div>
        <div class="alt">notu oluşmuş</div>
    </div>
</div>

<section class="kart">
    <div class="kart-baslik">
        <h3>Ders Dökümü</h3>
        <span class="rozet"><?= e((string) $transkript['ders_sayisi']) ?> kayıt</span>
    </div>

    <?php if ($transkript['satirlar'] === []): ?>
        <div class="bos-durum">Notu oluşmuş ders bulunmuyor.</div>
    <?php else: ?>
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Dönem</th>
                        <th>Ders</th>
                        <th class="metin-sag">Kredi</th>
                        <th class="metin-sag">AKTS</th>
                        <th class="metin-sag">Ortalama</th>
                        <th class="metin-sag">Harf</th>
                        <th class="metin-sag">Puan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transkript['satirlar'] as $satir): ?>
                        <tr>
                            <td class="metin-kucuk metin-soluk"><?= e($satir['donem']) ?></td>
                            <td>
                                <strong><?= e($satir['kod']) ?></strong>
                                <div class="metin-silik metin-kucuk"><?= e($satir['ad']) ?></div>
                            </td>
                            <td class="metin-sag sayi"><?= e((string) $satir['kredi']) ?></td>
                            <td class="metin-sag sayi"><?= e((string) $satir['akts']) ?></td>
                            <td class="metin-sag sayi"><?= $satir['ortalama'] !== null ? e(number_format((float) $satir['ortalama'], 2, ',', '.')) : '—' ?></td>
                            <td class="metin-sag">
                                <span class="rozet <?= e(Ogrenci::harfSinifi((string) $satir['harf_notu'])) ?>"><?= e($satir['harf_notu']) ?></span>
                            </td>
                            <td class="metin-sag sayi">
                                <strong><?= e(number_format((float) $satir['puan'], 2, ',', '.')) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Genel Ağırlıklı Not Ortalaması</th>
                        <th class="metin-sag sayi"><?= e((string) $transkript['kredi']) ?></th>
                        <th class="metin-sag sayi"><?= e((string) $transkript['akts']) ?></th>
                        <th colspan="2"></th>
                        <th class="metin-sag sayi"><?= $gano !== null ? e(number_format($gano, 2, ',', '.')) : '—' ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="metin-silik metin-kucuk mt-2">
    Bu transkript ders projesi kapsamında üretilmiştir; resmî belge niteliği taşımaz.
</div>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
