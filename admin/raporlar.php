<?php

declare(strict_types=1);

/**
 * Yönetici paneli - raporlar ve istatistikler (Faz 6).
 *
 * Veri kaynakları:
 *   - v_ders_doluluk / v_ders_basari_istatistik  → ders raporu
 *   - v_ogretim_elemani_yuku                     → hoca yükü
 *   - v_donem_ozet                               → üst kartlar
 *   - v_harf_notu_dagilimi                       → harf notu dağılımı
 *   - v_bolum_ozet                               → bölüm özeti
 *   - sp_donem_raporu / sp_riskli_ogrenciler     → prosedürler
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$donemId  = Rapor::donemSec(isset($_GET['donem']) ? (int) $_GET['donem'] : null);
$donemler = Rapor::donemler();
$donem    = Rapor::donem($donemId);
$ozet     = Rapor::ozet($donemId);
$dersler  = Rapor::dersRaporu($donemId);
$hocalar  = Rapor::hocaYuku($donemId);
$harfler  = Rapor::harfDagilimi($donemId);
$bolumler = Rapor::bolumOzeti();
$riskli   = Rapor::riskliOgrenciler($donemId);

$harfToplam = 0;
$harfEnBuyuk = 1;
foreach ($harfler as $h) {
    $harfToplam += (int) $h['adet'];
    $harfEnBuyuk = max($harfEnBuyuk, (int) $h['adet']);
}

$yuzde = static fn (mixed $v): string => $v === null ? '—' : '%' . Rapor::ondalik($v, 1);
$barGenislik = static fn (mixed $v): string => $v === null ? '0' : (string) max(0, min(100, (float) $v));

$sayfaBaslik = 'Raporlar';
$aktifMenu   = 'panel';
$panelAktif  = 'raporlar';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="yazdir-baslik">
    <strong>Nallıhan MYO Sanal Okul — Dönem Raporu</strong>
    <span><?= e($donem['ad'] ?? 'Dönem seçilmedi') ?> · <?= e(date('d.m.Y H:i')) ?></span>
</div>

<div class="sayfa-basligi">
    <div>
        <h1>Raporlar ve İstatistikler</h1>
        <p>
            <?= e($donem['ad'] ?? 'Dönem bulunamadı') ?>
            <?php if ($donem !== null && (int) $donem['aktif'] === 1): ?>
                · <span class="rozet rozet-basari">Aktif dönem</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="sayfa-eylem">
        <a class="dugme dugme-birincil dugme-kucuk"
           href="<?= e(url('admin/rapor_csv.php?donem=' . $donemId)) ?>">CSV indir</a>
        <button class="dugme dugme-hayalet dugme-kucuk" type="button" data-yazdir>Yazdır</button>
    </div>
</div>

<form class="filtre-cubuk" method="get" action="<?= e(url('admin/raporlar.php')) ?>">
    <div class="filtre-alan">
        <label class="form-etiket" for="donem">Dönem</label>
        <select class="form-girdi" id="donem" name="donem">
            <?php foreach ($donemler as $d): ?>
                <option value="<?= e((string) $d['id']) ?>" <?= (int) $d['id'] === $donemId ? 'selected' : '' ?>>
                    <?= e($d['ad']) ?><?= (int) $d['aktif'] === 1 ? ' (aktif)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtre-eylem">
        <button class="dugme dugme-birincil dugme-kucuk" type="submit">Göster</button>
    </div>
    <div class="form-ipucu" style="flex:1 1 100%;margin:0">
        Rapor anlık veriden üretilir. Risk listesi eşiği: ortalama %<?= e(Rapor::ondalik(Rapor::RISK_ESIGI, 0)) ?>
        altı veya en az bir başarısız ders.
    </div>
</form>

<div class="izgara izgara-4" style="margin-bottom:16px">
    <div class="stat-kart">
        <div class="etiket">Kayıtlı Öğrenci</div>
        <div class="deger"><?= e((string) $ozet['kayitli_ogrenci']) ?></div>
        <div class="alt"><?= e((string) $ozet['toplam_kayit']) ?> ders kaydı</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Ders Açılışı</div>
        <div class="deger"><?= e((string) $ozet['ders_acilisi']) ?></div>
        <div class="alt"><?= e((string) $ozet['farkli_ders']) ?> farklı ders</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Not Girme Oranı</div>
        <div class="deger"><?= e($yuzde($ozet['not_girme_orani'])) ?></div>
        <div class="alt"><?= e((string) $ozet['notu_girilen']) ?> / <?= e((string) $ozet['toplam_kayit']) ?> kayıt</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Genel Başarı</div>
        <div class="deger"><?= e($yuzde($ozet['basari_yuzdesi'])) ?></div>
        <div class="alt">
            <?= e((string) $ozet['gecen']) ?> geçen · <?= e((string) $ozet['kalan']) ?> kalan ·
            ortalama <?= e(Rapor::ondalik($ozet['ortalama'], 2)) ?>
        </div>
    </div>
</div>

<section class="kart mb-3">
    <div class="kart-baslik">
        <h3>Ders Bazlı Rapor</h3>
        <span class="rozet"><?= e((string) count($dersler)) ?> ders açılışı</span>
    </div>

    <?php if ($dersler === []): ?>
        <div class="bos-durum">Bu dönem için ders açılışı bulunmuyor.</div>
    <?php else: ?>
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Ders</th>
                        <th>Öğretim Elemanı</th>
                        <th>Kayıt</th>
                        <th class="metin-sag">Not</th>
                        <th class="metin-sag">Ortalama</th>
                        <th class="metin-sag">Geçen / Kalan</th>
                        <th class="metin-sag">Başarı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dersler as $d): ?>
                        <tr>
                            <td>
                                <strong><?= e($d['ders_kodu']) ?></strong>
                                <span class="metin-silik metin-kucuk">· <?= e($d['sube']) ?></span>
                                <div class="metin-silik metin-kucuk"><?= e($d['ders_adi']) ?></div>
                            </td>
                            <td class="metin-kucuk"><?= e($d['ogretim_elemani']) ?></td>
                            <td>
                                <div class="sayi">
                                    <strong><?= e((string) $d['kayitli']) ?></strong>
                                    <span class="metin-silik">/ <?= e($d['kontenjan'] === null ? '∞' : (string) $d['kontenjan']) ?></span>
                                </div>
                                <?php if ($d['doluluk_yuzdesi'] !== null): ?>
                                    <div class="rapor-doluluk" title="<?= e($yuzde($d['doluluk_yuzdesi'])) ?>">
                                        <span style="width:<?= e($barGenislik($d['doluluk_yuzdesi'])) ?>%"></span>
                                    </div>
                                    <span class="metin-silik metin-kucuk"><?= e($yuzde($d['doluluk_yuzdesi'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="metin-sag sayi">
                                <?= e((string) $d['notu_girilen']) ?><span class="metin-silik">/<?= e((string) $d['kayitli']) ?></span>
                            </td>
                            <td class="metin-sag sayi"><?= e(Rapor::ondalik($d['ortalama'], 2)) ?></td>
                            <td class="metin-sag sayi">
                                <?= e((string) $d['gecen']) ?> / <?= e((string) $d['kalan']) ?>
                            </td>
                            <td class="metin-sag">
                                <?php if ($d['basari_yuzdesi'] === null): ?>
                                    <span class="metin-silik">—</span>
                                <?php else: ?>
                                    <span class="<?= e(Rapor::yuzdeSinifi($d['basari_yuzdesi'])) ?>">
                                        <?= e($yuzde($d['basari_yuzdesi'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Harf Notu Dağılımı</h3>
            <span class="rozet"><?= e((string) $harfToplam) ?> not</span>
        </div>

        <?php if ($harfler === []): ?>
            <div class="bos-durum">Bu dönemde girilmiş not yok.</div>
        <?php else: ?>
            <div class="rapor-bar">
                <?php foreach ($harfler as $h): ?>
                    <?php
                        $adet = (int) $h['adet'];
                        $oran = $harfToplam > 0 ? 100 * $adet / $harfToplam : 0;
                        $genislik = 100 * $adet / $harfEnBuyuk;
                    ?>
                    <div class="rapor-bar-satir">
                        <span class="rapor-bar-etiket"><?= e($h['harf_notu']) ?></span>
                        <span class="rapor-bar-izgi">
                            <span class="rapor-bar-doldur" style="width:<?= e(number_format($genislik, 1, '.', '')) ?>%"></span>
                        </span>
                        <span class="rapor-bar-deger sayi"><?= e((string) $adet) ?></span>
                        <span class="rapor-bar-oran metin-silik">%<?= e(Rapor::ondalik($oran, 1)) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Bölüm Özeti</h3>
            <span class="rozet">tüm dönemler</span>
        </div>

        <div class="satir-liste">
            <?php foreach ($bolumler as $b): ?>
                <div class="satir">
                    <div class="satir-icerik">
                        <strong class="kirp"><?= e($b['bolum_kodu']) ?> · <?= e($b['bolum']) ?></strong>
                        <span>
                            <?= e((string) $b['ogrenci']) ?> öğrenci ·
                            <?= e((string) $b['ders_sayisi']) ?> ders ·
                            ort. <?= e(Rapor::ondalik($b['ortalama'], 2)) ?>
                        </span>
                    </div>
                    <div class="satir-sag">
                        <?php if ($b['basari_yuzdesi'] === null): ?>
                            <span class="metin-silik">—</span>
                        <?php else: ?>
                            <span class="<?= e(Rapor::yuzdeSinifi($b['basari_yuzdesi'])) ?>">
                                <?= e($yuzde($b['basari_yuzdesi'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="izgara izgara-2 mt-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Öğretim Elemanı Yükü</h3>
            <span class="rozet"><?= e((string) count($hocalar)) ?> kişi</span>
        </div>

        <?php if ($hocalar === []): ?>
            <div class="bos-durum">Bu dönemde ders veren öğretim elemanı yok.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($hocalar as $h): ?>
                    <div class="satir">
                        <div class="satir-icerik">
                            <strong class="kirp">
                                <?= e(trim(($h['unvan'] ?? '') . ' ' . $h['ogretim_elemani'])) ?>
                            </strong>
                            <span>
                                <?= e((string) $h['ders_acilisi']) ?> ders ·
                                <?= e((string) $h['ogrenci']) ?> öğrenci ·
                                <?= e(number_format((float) $h['haftalik_saat'], 0, ',', '.')) ?> saat/hafta
                            </span>
                        </div>
                        <div class="satir-sag">
                            <span class="<?= e(Rapor::yuzdeSinifi($h['ortalama'])) ?>">
                                <?= e(Rapor::ondalik($h['ortalama'], 2)) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Akademik Risk Listesi</h3>
            <span class="rozet"><?= e((string) count($riskli)) ?> öğrenci</span>
        </div>

        <?php if ($riskli === []): ?>
            <div class="bos-durum">Riskli öğrenci yok. Ortalaması %<?= e(Rapor::ondalik(Rapor::RISK_ESIGI, 0)) ?> altında veya başarısız dersi olan öğrenci bulunmuyor.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($riskli as $r): ?>
                    <div class="satir">
                        <div class="satir-icerik">
                            <strong class="kirp"><?= e($r['ogrenci']) ?></strong>
                            <span>
                                <?= e($r['ogrenci_no']) ?> · <?= e((string) $r['sinif']) ?>. sınıf ·
                                <?= e((string) $r['ders_sayisi']) ?> ders ·
                                <?= e((string) $r['gecen_ders']) ?> geçen / <?= e((string) $r['kalan_ders']) ?> kalan
                            </span>
                        </div>
                        <div class="satir-sag">
                            <span class="<?= e(Rapor::yuzdeSinifi($r['ortalama'])) ?>">
                                <?= e(Rapor::ondalik($r['ortalama'], 2)) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
