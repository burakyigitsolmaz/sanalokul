<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - tek ödevin değerlendirilmesi.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$hocaId    = (int) $kullanici['id'];
$odevId    = (int) ($_GET['id'] ?? 0);
$odev      = $odevId > 0 ? Ogretmen::odev($hocaId, $odevId) : null;

$sayfaBaslik = $odev !== null ? $odev['baslik'] : 'Ödev Bulunamadı';
$aktifMenu   = 'panel';
$panelAktif  = 'odevler';

if ($odev === null) {
    http_response_code(404);
    require dirname(__DIR__) . '/templates/ogretmen_bas.php';
    ?>
    <div class="kart metin-orta" style="padding:36px 26px">
        
        <h1 style="font-size:22px">Ödev bulunamadı</h1>
        <p class="metin-soluk">Bu ödev size ait değil ya da silinmiş olabilir.</p>
        <a class="dugme dugme-birincil" href="<?= e(url('ogretmen/odevler.php')) ?>">Ödevlere Dön</a>
    </div>
    <?php
    require dirname(__DIR__) . '/templates/ogretmen_son.php';
    exit;
}

$don = static function () use ($odevId): void {
    header('Location: ' . url('ogretmen/odev.php?id=' . (int) $odevId));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    if ((string) ($_POST['islem'] ?? '') !== 'degerlendir') {
        Auth::flash('hata', 'Bilinmeyen işlem.');
        $don();
    }

    $teslimId    = (int) ($_POST['teslim_id'] ?? 0);
    $puan        = (int) ($_POST['puan'] ?? -1);
    $geriBildirim = trim((string) ($_POST['geri_bildirim'] ?? ''));

    if (!Ogretmen::degerlendir($hocaId, $teslimId, $puan, $geriBildirim === '' ? null : $geriBildirim)) {
        Auth::flash('hata', 'Puan 0 ile ' . (int) $odev['maks_puan'] . ' arasında olmalı ya da teslim size ait değil.');
        $don();
    }

    Auth::log('Ödev değerlendirildi (puan: ' . $puan . ')', 'teslimler', $teslimId);
    Auth::flash('basari', 'Teslim değerlendirildi: ' . $puan . '/' . (int) $odev['maks_puan']);
    $don();
}

// ---------------------------------------------------------------------
// Liste
// ---------------------------------------------------------------------
$teslimler = Ogretmen::teslimler((int) $odev['ders_acilis_id'], $odevId);

$teslimEden = array_values(array_filter($teslimler, static fn (array $t): bool => $t['teslim_id'] !== null));
$bekleyen   = array_values(array_filter($teslimEden, static fn (array $t): bool => $t['puan'] === null));
$puanlanan  = array_values(array_filter($teslimEden, static fn (array $t): bool => $t['puan'] !== null));

$sayfaBaslik = $odev['baslik'];
$kalan       = Ogrenci::kalanSure((string) $odev['son_teslim']);

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<nav class="kirinti" aria-label="Sayfa yolu">
    <a href="<?= e(url('ogretmen/')) ?>">Panelim</a>
    <span>/</span>
    <a href="<?= e(url('ogretmen/odevler.php')) ?>">Ödevler</a>
    <span>/</span>
    <a href="<?= e(url('ogretmen/ders.php?id=' . (int) $odev['ders_acilis_id'] . '&sekme=odevler')) ?>"><?= e($odev['kod']) ?></a>
    <span>/</span>
    <span class="simdiki">Değerlendirme</span>
</nav>

<div class="sayfa-basligi">
    <div>
        <h1><?= e($odev['baslik']) ?></h1>
        <p>
            <?= e($odev['kod']) ?> · <?= e($odev['ders_adi']) ?> · <?= e($odev['donem']) ?>
            · son teslim <?= e(date('d.m.Y H:i', strtotime((string) $odev['son_teslim']))) ?>
            <span class="rozet <?= e($kalan['sinif']) ?>"><?= e($kalan['metin']) ?></span>
        </p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk"
       href="<?= e(url('ogretmen/ders.php?id=' . (int) $odev['ders_acilis_id'] . '&sekme=odevler')) ?>">Ders Sayfası</a>
</div>

<div class="izgara izgara-4" style="margin-bottom:20px">
    <div class="stat-kart">
        <div class="etiket">Teslim</div>
        <div class="deger"><?= e((string) count($teslimEden)) ?>/<?= e((string) $odev['ogrenci_sayisi']) ?></div>
        <div class="alt">öğrenci teslim etti</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Değerlendirme Bekleyen</div>
        <div class="deger"><?= e((string) count($bekleyen)) ?></div>
        <div class="alt">teslim</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Puanlanan</div>
        <div class="deger"><?= e((string) count($puanlanan)) ?></div>
        <div class="alt">teslim</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Ortalama</div>
        <div class="deger"><?= $odev['ortalama_puan'] !== null ? e(number_format((float) $odev['ortalama_puan'], 1, ',', '.')) : '—' ?></div>
        <div class="alt"><?= e((string) $odev['maks_puan']) ?> puan üzerinden</div>
    </div>
</div>

<section class="kart" style="margin-bottom:18px">
    <div class="kart-baslik"><h3>Ödev Açıklaması</h3></div>
    <p class="metin-soluk" style="font-size:13.5px"><?= nl2br(e($odev['aciklama'])) ?></p>
</section>

<section class="kart">
    <div class="kart-baslik">
        <h3>Teslimler</h3>
        <span class="rozet"><?= e((string) count($teslimler)) ?> öğrenci</span>
    </div>

    <?php if ($teslimler === []): ?>
        <div class="bos-durum">Bu derse kayıtlı öğrenci yok.</div>
    <?php else: ?>
        <div class="tablo-sarmal">
            <table class="tablo degerlendirme-tablo">
                <thead>
                    <tr>
                        <th>Öğrenci</th>
                        <th>Teslim</th>
                        <th>Durum</th>
                        <th class="metin-sag">Puan</th>
                        <th>Geri Bildirim</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teslimler as $teslim): ?>
                        <?php
                        $formId = 'teslim-' . (int) $teslim['ogrenci_id'];
                        $durum  = Ogretmen::teslimDurumu(
                            $teslim['teslim_zamani'] !== null ? (string) $teslim['teslim_zamani'] : null,
                            (string) $odev['son_teslim']
                        );
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($teslim['ad_soyad']) ?></strong>
                                <div class="metin-silik metin-kucuk">
                                    <?= e($teslim['numara']) ?>
                                    <?= !empty($teslim['sinif']) ? '· ' . e((string) $teslim['sinif']) . '. sınıf' : '' ?>
                                </div>
                            </td>
                            <td class="metin-kucuk">
                                <?php if ($teslim['teslim_zamani'] !== null): ?>
                                    <?= e(date('d.m.Y H:i', strtotime((string) $teslim['teslim_zamani']))) ?>
                                    <div style="margin-top:3px">
                                        <a class="baglanti metin-kucuk"
                                           href="<?= e(url('ogretmen/indir.php?id=' . (int) $teslim['teslim_id'])) ?>"> Dosyayı indir</a>
                                    </div>
                                <?php else: ?>
                                    <span class="metin-silik">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rozet <?= e($durum['sinif']) ?>"><?= e($durum['metin']) ?></span>
                                <?php if ($teslim['puan'] !== null): ?>
                                    <div class="metin-kucuk metin-silik" style="margin-top:4px">Değerlendirildi</div>
                                <?php endif; ?>
                            </td>
                            <?php if ($teslim['teslim_id'] === null): ?>
                                <td class="metin-sag metin-silik">—</td>
                                <td class="metin-silik metin-kucuk">Teslim yok</td>
                                <td></td>
                            <?php else: ?>
                                <td class="metin-sag">
                                    <input class="form-girdi not-girdi" type="number" name="puan"
                                           form="<?= e($formId) ?>" min="0" max="<?= e((string) $odev['maks_puan']) ?>"
                                           step="1" required aria-label="Puan"
                                           value="<?= $teslim['puan'] !== null ? e((string) $teslim['puan']) : '' ?>">
                                </td>
                                <td style="min-width:220px">
                                    <textarea class="form-girdi geri-girdi" name="geri_bildirim"
                                              form="<?= e($formId) ?>" rows="2" maxlength="2000"
                                              aria-label="Geri bildirim"
                                              placeholder="Kısa geri bildirim..."><?= e((string) ($teslim['geri_bildirim'] ?? '')) ?></textarea>
                                </td>
                                <td class="metin-sag">
                                    <form id="<?= e($formId) ?>" method="post"
                                          action="<?= e(url('ogretmen/odev.php?id=' . $odevId)) ?>">
                                        <?= Auth::csrfAlani() ?>
                                        <input type="hidden" name="islem" value="degerlendir">
                                        <input type="hidden" name="teslim_id" value="<?= e((string) $teslim['teslim_id']) ?>">
                                    </form>
                                    <button class="dugme dugme-birincil dugme-kucuk" type="submit" form="<?= e($formId) ?>">
                                        Kaydet
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php if ($teslim['teslim_id'] !== null && !empty($teslim['teslim_aciklamasi'])): ?>
                            <tr class="teslim-not-satiri">
                                <td colspan="6">
                                    <span class="metin-kucuk metin-silik"> Öğrenci notu:</span>
                                    <span class="metin-kucuk"><?= e($teslim['teslim_aciklamasi']) ?></span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="metin-silik metin-kucuk mt-2">
            Puan kaydedildiğinde öğrencinin Ödevler sayfasında anında görünür.
            Geri bildirim isteğe bağlıdır.
        </p>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
