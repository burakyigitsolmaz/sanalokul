<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - tek dersin yönetimi.
 *
 * Sekmeler: ogrenciler (not girişi), odevler, icerikler, duyurular
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$hocaId    = (int) $kullanici['id'];
$acilisId  = (int) ($_GET['id'] ?? 0);
$ders      = $acilisId > 0 ? Ogretmen::ders($hocaId, $acilisId) : null;

$sekmeler = [
    'ogrenciler' => ['ad' => 'Öğrenciler & Notlar', 'ikon' => ''],
    'odevler'    => ['ad' => 'Ödevler',             'ikon' => ''],
    'icerikler'  => ['ad' => 'İçerikler',           'ikon' => ''],
    'duyurular'  => ['ad' => 'Duyurular',           'ikon' => ''],
];

$sekme = (string) ($_GET['sekme'] ?? 'ogrenciler');

if (!isset($sekmeler[$sekme])) {
    $sekme = 'ogrenciler';
}

$sayfaBaslik = $ders !== null ? $ders['kod'] . ' · Ders Yönetimi' : 'Ders Bulunamadı';
$aktifMenu   = 'panel';
$panelAktif  = 'dersler';

if ($ders === null) {
    http_response_code(404);
    require dirname(__DIR__) . '/templates/ogretmen_bas.php';
    ?>
    <div class="kart metin-orta" style="padding:36px 26px">
        
        <h1 style="font-size:22px">Ders bulunamadı</h1>
        <p class="metin-soluk">Bu ders size ait değil ya da kayıt silinmiş olabilir.</p>
        <a class="dugme dugme-birincil" href="<?= e(url('ogretmen/dersler.php')) ?>">Derslerime Dön</a>
    </div>
    <?php
    require dirname(__DIR__) . '/templates/ogretmen_son.php';
    exit;
}

// ---------------------------------------------------------------------
// Form işlemleri
// ---------------------------------------------------------------------
$don = static function (string $hedefSekme) use ($acilisId): void {
    header('Location: ' . url('ogretmen/ders.php?id=' . $acilisId . '&sekme=' . $hedefSekme));
    exit;
};

$notOku = static function (mixed $deger): ?float {
    $metin = trim((string) $deger);

    if ($metin === '') {
        return null;
    }

    return (float) str_replace(',', '.', $metin);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don($sekme);
    }

    $islem = (string) ($_POST['islem'] ?? '');

    // Not girişi -------------------------------------------------------
    if ($islem === 'not_kaydet') {
        $kayitId = (int) ($_POST['kayit_id'] ?? 0);
        $vize    = $notOku($_POST['vize'] ?? null);
        $final   = $notOku($_POST['final'] ?? null);

        if (($vize !== null && ($vize < 0 || $vize > 100))
            || ($final !== null && ($final < 0 || $final > 100))) {
            Auth::flash('hata', 'Notlar 0 ile 100 arasında olmalıdır.');
            $don('ogrenciler');
        }

        if (!Ogretmen::notKaydet($hocaId, $kayitId, $vize, $final)) {
            Auth::flash('hata', 'Öğrenci kaydı bu derse ait değil.');
            $don('ogrenciler');
        }

        Auth::log('Not kaydedildi (vize: ' . ($vize ?? '-') . ', final: ' . ($final ?? '-') . ')', 'notlar', $kayitId);
        Auth::flash('basari', 'Not kaydedildi. Ortalama ve harf notu otomatik hesaplandı.');
        $don('ogrenciler');
    }

    // Ödev ekle --------------------------------------------------------
    if ($islem === 'odev_ekle') {
        $baslik   = trim((string) ($_POST['baslik'] ?? ''));
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));
        $sonHam   = trim((string) ($_POST['son_teslim'] ?? ''));
        $maks     = (int) ($_POST['maks_puan'] ?? 100);

        $sonZaman = $sonHam !== '' ? strtotime($sonHam) : false;

        if (mb_strlen($baslik, 'UTF-8') < 3 || $sonZaman === false || $aciklama === '' || $maks < 1 || $maks > 100) {
            Auth::flash('hata', 'Ödev için başlık, açıklama, son teslim tarihi ve 1-100 arası puan gerekli.');
            $don('odevler');
        }

        if (!Ogretmen::odevEkle($hocaId, $acilisId, $baslik, $aciklama, date('Y-m-d H:i:s', $sonZaman), $maks)) {
            Auth::flash('hata', 'Ödev eklenemedi.');
            $don('odevler');
        }

        Auth::log('Ödev eklendi: ' . $baslik, 'odevler');
        Auth::flash('basari', 'Ödev eklendi.');
        $don('odevler');
    }

    // Ödev sil ---------------------------------------------------------
    if ($islem === 'odev_sil') {
        $odevId = (int) ($_POST['odev_id'] ?? 0);

        if (!Ogretmen::odevSil($hocaId, $odevId)) {
            Auth::flash('hata', 'Ödev bulunamadı veya size ait değil.');
            $don('odevler');
        }

        Auth::log('Ödev silindi', 'odevler', $odevId);
        Auth::flash('basari', 'Ödev ve teslim dosyaları silindi.');
        $don('odevler');
    }

    // İçerik ekle ------------------------------------------------------
    if ($islem === 'icerik_ekle') {
        $hafta   = (int) ($_POST['hafta'] ?? 0);
        $baslik  = trim((string) ($_POST['baslik'] ?? ''));
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));

        if ($hafta < 1 || $hafta > 16 || $baslik === '') {
            Auth::flash('hata', 'Hafta 1-16 arasında olmalı ve başlık girilmelidir.');
            $don('icerikler');
        }

        Ogretmen::icerikEkle($hocaId, $acilisId, $hafta, $baslik, $aciklama === '' ? null : $aciklama);

        Auth::log('Ders içeriği kaydedildi (hafta ' . $hafta . ')', 'ders_icerikleri');
        Auth::flash('basari', 'Ders içeriği kaydedildi.');
        $don('icerikler');
    }

    // İçerik sil -------------------------------------------------------
    if ($islem === 'icerik_sil') {
        $icerikId = (int) ($_POST['icerik_id'] ?? 0);

        if (!Ogretmen::icerikSil($hocaId, $icerikId)) {
            Auth::flash('hata', 'İçerik bulunamadı veya size ait değil.');
            $don('icerikler');
        }

        Auth::log('Ders içeriği silindi', 'ders_icerikleri', $icerikId);
        Auth::flash('basari', 'Ders içeriği silindi.');
        $don('icerikler');
    }

    // Duyuru ekle ------------------------------------------------------
    if ($islem === 'duyuru_ekle') {
        $baslik  = trim((string) ($_POST['baslik'] ?? ''));
        $icerik  = trim((string) ($_POST['icerik'] ?? ''));
        $onemli  = !empty($_POST['onemli']);
        $bitisHam = trim((string) ($_POST['bitis_tarihi'] ?? ''));

        if ($baslik === '' || $icerik === '') {
            Auth::flash('hata', 'Duyuru başlığı ve içeriği zorunludur.');
            $don('duyurular');
        }

        $bitis = null;

        if ($bitisHam !== '') {
            $bitisZaman = strtotime($bitisHam);

            if ($bitisZaman === false) {
                Auth::flash('hata', 'Geçerlilik tarihi anlaşılamadı.');
                $don('duyurular');
            }

            $bitis = date('Y-m-d 23:59:59', $bitisZaman);
        }

        if (!Ogretmen::duyuruEkle($hocaId, $acilisId, $baslik, $icerik, $onemli, $bitis)) {
            Auth::flash('hata', 'Duyuru eklenemedi.');
            $don('duyurular');
        }

        Auth::log('Ders duyurusu yayınlandı: ' . $baslik, 'duyurular');
        Auth::flash('basari', 'Duyuru yayınlandı.');
        $don('duyurular');
    }

    // Duyuru sil -------------------------------------------------------
    if ($islem === 'duyuru_sil') {
        $duyuruId = (int) ($_POST['duyuru_id'] ?? 0);

        if (!Ogretmen::duyuruSil($hocaId, $duyuruId)) {
            Auth::flash('hata', 'Duyuru bulunamadı veya size ait değil.');
            $don('duyurular');
        }

        Auth::log('Duyuru silindi', 'duyurular', $duyuruId);
        Auth::flash('basari', 'Duyuru silindi.');
        $don('duyurular');
    }

    Auth::flash('hata', 'Bilinmeyen işlem.');
    $don($sekme);
}

// ---------------------------------------------------------------------
// Sekme verileri
// ---------------------------------------------------------------------
$ogrenciler = Ogretmen::ogrenciler($acilisId);
$dersOdevleri = Ogretmen::dersOdevleri($acilisId);
$icerikler = Ogretmen::icerikler($acilisId);

$dersDuyurulari = array_values(array_filter(
    Ogretmen::duyurular($hocaId),
    static fn (array $d): bool => (int) $d['ders_acilis_id'] === $acilisId
));

$notluOgrenci = count(array_filter($ogrenciler, static fn (array $o): bool => $o['ortalama'] !== null));
$ortalamaToplam = 0.0;
$ortalamaAdet = 0;

foreach ($ogrenciler as $ogrenci) {
    if ($ogrenci['ortalama'] !== null) {
        $ortalamaToplam += (float) $ogrenci['ortalama'];
        $ortalamaAdet++;
    }
}

$sinifOrtalamasi = $ortalamaAdet > 0 ? round($ortalamaToplam / $ortalamaAdet, 2) : null;

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<nav class="kirinti" aria-label="Sayfa yolu">
    <a href="<?= e(url('ogretmen/')) ?>">Panelim</a>
    <span>/</span>
    <a href="<?= e(url('ogretmen/dersler.php')) ?>">Derslerim</a>
    <span>/</span>
    <span class="simdiki"><?= e($ders['kod']) ?></span>
</nav>

<div class="sayfa-basligi">
    <div>
        <h1><?= e($ders['ad']) ?></h1>
        <p>
            <?= e($ders['kod']) ?> · <?= e($ders['donem']) ?> · <?= e($ders['sube']) ?> şube
            <?php if ((int) $ders['aktif'] === 1): ?>· <span class="rozet rozet-basari">Aktif</span><?php endif; ?>
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogretmen/program.php')) ?>">Programım</a>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogretmen/dersler.php')) ?>">Derslerim</a>
    </div>
</div>

<div class="izgara izgara-4" style="margin-bottom:20px">
    <div class="stat-kart">
        <div class="etiket">Öğrenci</div>
        <div class="deger"><?= e((string) count($ogrenciler)) ?></div>
        <div class="alt">aktif kayıt</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Notu Girilen</div>
        <div class="deger"><?= e((string) $notluOgrenci) ?> / <?= e((string) count($ogrenciler)) ?></div>
        <div class="alt"><?= e((string) max(0, count($ogrenciler) - $notluOgrenci)) ?> bekliyor</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Sınıf Ortalaması</div>
        <div class="deger"><?= $sinifOrtalamasi !== null ? e(number_format($sinifOrtalamasi, 2, ',', '.')) : '—' ?></div>
        <div class="alt"><?= $sinifOrtalamasi !== null ? 'notlu öğrenciler' : 'not bekleniyor' ?></div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Ödev</div>
        <div class="deger"><?= e((string) count($dersOdevleri)) ?></div>
        <div class="alt"><?= e((string) $ders['bekleyen_teslim']) ?> değerlendirme bekliyor</div>
    </div>
</div>

<nav class="sekme-cubugu" aria-label="Ders yönetimi sekmeleri">
    <?php foreach ($sekmeler as $anahtar => $bilgi): ?>
        <a class="sekme <?= $sekme === $anahtar ? 'aktif' : '' ?>"
           href="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=' . $anahtar)) ?>">
            
            <?= e($bilgi['ad']) ?>
            <?php if ($anahtar === 'odevler'): ?>
                <span class="sekme-sayi"><?= e((string) count($dersOdevleri)) ?></span>
            <?php elseif ($anahtar === 'icerikler'): ?>
                <span class="sekme-sayi"><?= e((string) count($icerikler)) ?></span>
            <?php elseif ($anahtar === 'duyurular'): ?>
                <span class="sekme-sayi"><?= e((string) count($dersDuyurulari)) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($sekme === 'ogrenciler'): ?>
    <section class="kart">
        <div class="kart-baslik">
            <h3>Öğrenciler ve Not Girişi</h3>
            <span class="rozet">Vize %40 · Final %60</span>
        </div>

        <?php if ($ogrenciler === []): ?>
            <div class="bos-durum">Bu derse kayıtlı öğrenci yok.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo not-tablo">
                    <thead>
                        <tr>
                            <th>Öğrenci</th>
                            <th class="metin-sag">Vize</th>
                            <th class="metin-sag">Final</th>
                            <th class="metin-sag">Ortalama</th>
                            <th class="metin-sag">Harf</th>
                            <th class="metin-sag gizle-mobil">Ödev</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ogrenciler as $ogrenci): ?>
                            <?php $formId = 'not-' . (int) $ogrenci['kayit_id']; ?>
                            <tr>
                                <td>
                                    <strong><?= e($ogrenci['ad_soyad']) ?></strong>
                                    <div class="metin-silik metin-kucuk">
                                        <?= e($ogrenci['numara']) ?>
                                        <?= !empty($ogrenci['sinif']) ? '· ' . e((string) $ogrenci['sinif']) . '. sınıf' : '' ?>
                                    </div>
                                </td>
                                <td class="metin-sag">
                                    <input class="form-girdi not-girdi" type="number" step="0.01" min="0" max="100"
                                           name="vize" form="<?= e($formId) ?>" aria-label="Vize notu"
                                           value="<?= $ogrenci['vize'] !== null ? e((string) $ogrenci['vize']) : '' ?>">
                                </td>
                                <td class="metin-sag">
                                    <input class="form-girdi not-girdi" type="number" step="0.01" min="0" max="100"
                                           name="final" form="<?= e($formId) ?>" aria-label="Final notu"
                                           value="<?= $ogrenci['final_notu'] !== null ? e((string) $ogrenci['final_notu']) : '' ?>">
                                </td>
                                <td class="metin-sag sayi">
                                    <strong><?= $ogrenci['ortalama'] !== null ? e(number_format((float) $ogrenci['ortalama'], 2, ',', '.')) : '—' ?></strong>
                                </td>
                                <td class="metin-sag">
                                    <?php if ($ogrenci['harf_notu'] !== null): ?>
                                        <span class="rozet <?= e(Ogrenci::harfSinifi((string) $ogrenci['harf_notu'])) ?>">
                                            <?= e($ogrenci['harf_notu']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="metin-silik">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="metin-sag sayi metin-kucuk gizle-mobil">
                                    <?= e((string) $ogrenci['teslim_sayisi']) ?>/<?= e((string) $ogrenci['odev_sayisi']) ?>
                                </td>
                                <td class="metin-sag">
                                    <form id="<?= e($formId) ?>" method="post"
                                          action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=ogrenciler')) ?>">
                                        <?= Auth::csrfAlani() ?>
                                        <input type="hidden" name="islem" value="not_kaydet">
                                        <input type="hidden" name="kayit_id" value="<?= e((string) $ogrenci['kayit_id']) ?>">
                                    </form>
                                    <button class="dugme dugme-birincil dugme-kucuk" type="submit" form="<?= e($formId) ?>">
                                        Kaydet
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="metin-silik metin-kucuk mt-2">
                Boş bırakılan not silinir. Ortalama ve harf notu kaydettiğinizde veritabanı trigger'ı
                tarafından otomatik hesaplanır.
            </p>
        <?php endif; ?>
    </section>

<?php elseif ($sekme === 'odevler'): ?>
    <div class="sekme-ust">
        <h2 style="font-size:17px">Ödevler</h2>
        <details class="ekle-kutu">
            <summary class="dugme dugme-birincil dugme-kucuk">＋ Yeni Ödev</summary>
            <form method="post" action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=odevler')) ?>" class="kart mt-2">
                <?= Auth::csrfAlani() ?>
                <input type="hidden" name="islem" value="odev_ekle">

                <div class="form-grup">
                    <label class="form-etiket" for="odev-baslik">Başlık</label>
                    <input class="form-girdi" id="odev-baslik" name="baslik" required maxlength="160">
                </div>

                <div class="form-grup">
                    <label class="form-etiket" for="odev-aciklama">Açıklama</label>
                    <textarea class="form-girdi" id="odev-aciklama" name="aciklama" rows="3" required></textarea>
                </div>

                <div class="form-izgara">
                    <div class="form-grup">
                        <label class="form-etiket" for="odev-son">Son teslim</label>
                        <input class="form-girdi" id="odev-son" type="datetime-local" name="son_teslim" required>
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket" for="odev-maks">Maksimum puan</label>
                        <input class="form-girdi" id="odev-maks" type="number" name="maks_puan" min="1" max="100" value="100" required>
                    </div>
                </div>

                <button class="dugme dugme-birincil" type="submit">Ödevi Ekle</button>
            </form>
        </details>
    </div>

    <?php if ($dersOdevleri === []): ?>
        <div class="kart bos-durum">Bu derse henüz ödev eklenmemiş.</div>
    <?php else: ?>
        <div class="odev-listesi">
            <?php foreach ($dersOdevleri as $odev): ?>
                <?php $kalan = Ogrenci::kalanSure((string) $odev['son_teslim']); ?>
                <article class="kart odev-kart">
                    <div class="odev-ust">
                        <div style="min-width:0">
                            <div class="odev-ders"><?= e($ders['kod']) ?> · <?= e($ders['ad']) ?></div>
                            <h3><?= e($odev['baslik']) ?></h3>
                        </div>
                        <span class="rozet <?= (int) $odev['bekleyen'] > 0 ? 'rozet-uyari' : 'rozet-basari' ?>">
                            <?php if ((int) $odev['teslim_sayisi'] === 0): ?>
                                Teslim yok
                            <?php elseif ((int) $odev['bekleyen'] > 0): ?>
                                <?= e((string) $odev['bekleyen']) ?> bekliyor
                            <?php else: ?>
                                Tamamlandı
                            <?php endif; ?>
                        </span>
                    </div>

                    <p class="odev-aciklama"><?= e($odev['aciklama']) ?></p>

                    <div class="odev-meta">
                        <span> Son teslim: <strong><?= e(date('d.m.Y H:i', strtotime((string) $odev['son_teslim']))) ?></strong></span>
                        <span> <?= e((string) $odev['maks_puan']) ?> puan</span>
                        <span> <?= e((string) $odev['teslim_sayisi']) ?>/<?= e((string) $odev['ogrenci_sayisi']) ?> teslim</span>
                        <?php if ($odev['ortalama_puan'] !== null): ?>
                            <span> Ort. <?= e(number_format((float) $odev['ortalama_puan'], 1, ',', '.')) ?></span>
                        <?php endif; ?>
                        <span class="rozet <?= e($kalan['sinif']) ?>"><?= e($kalan['metin']) ?></span>
                    </div>

                    <div class="kart-eylem">
                        <a class="dugme dugme-birincil dugme-kucuk"
                           href="<?= e(url('ogretmen/odev.php?id=' . (int) $odev['id'])) ?>">Değerlendir →</a>

                        <form method="post" action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=odevler')) ?>"
                              data-onay="Ödev ve tüm teslim dosyaları silinecek. Emin misiniz?">
                            <?= Auth::csrfAlani() ?>
                            <input type="hidden" name="islem" value="odev_sil">
                            <input type="hidden" name="odev_id" value="<?= e((string) $odev['id']) ?>">
                            <button class="dugme dugme-tehlike dugme-kucuk" type="submit">Sil</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($sekme === 'icerikler'): ?>
    <div class="sekme-ust">
        <h2 style="font-size:17px">Haftalık İçerikler</h2>
        <details class="ekle-kutu">
            <summary class="dugme dugme-birincil dugme-kucuk">＋ İçerik Ekle</summary>
            <form method="post" action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=icerikler')) ?>" class="kart mt-2">
                <?= Auth::csrfAlani() ?>
                <input type="hidden" name="islem" value="icerik_ekle">

                <div class="form-izgara">
                    <div class="form-grup">
                        <label class="form-etiket" for="icerik-hafta">Hafta</label>
                        <input class="form-girdi" id="icerik-hafta" type="number" name="hafta" min="1" max="16" required>
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket" for="icerik-baslik">Başlık</label>
                        <input class="form-girdi" id="icerik-baslik" name="baslik" required maxlength="160">
                    </div>
                </div>

                <div class="form-grup">
                    <label class="form-etiket" for="icerik-aciklama">Açıklama (isteğe bağlı)</label>
                    <textarea class="form-girdi" id="icerik-aciklama" name="aciklama" rows="2"></textarea>
                </div>

                <button class="dugme dugme-birincil" type="submit">Kaydet</button>
                <p class="form-ipucu">Aynı hafta için tekrar kaydederseniz var olan içerik güncellenir.</p>
            </form>
        </details>
    </div>

    <?php if ($icerikler === []): ?>
        <div class="kart bos-durum">Henüz içerik eklenmemiş.</div>
    <?php else: ?>
        <section class="kart">
            <div class="zaman-cizelgesi">
                <?php foreach ($icerikler as $icerik): ?>
                    <div class="zaman-satir">
                        <span class="zaman-no"><?= e((string) $icerik['hafta']) ?></span>
                        <div class="zaman-icerik">
                            <strong><?= e($icerik['baslik']) ?></strong>
                            <?php if (!empty($icerik['aciklama'])): ?>
                                <p><?= e($icerik['aciklama']) ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=icerikler')) ?>"
                              data-onay="Bu içerik silinsin mi?">
                            <?= Auth::csrfAlani() ?>
                            <input type="hidden" name="islem" value="icerik_sil">
                            <input type="hidden" name="icerik_id" value="<?= e((string) $icerik['id']) ?>">
                            <button class="sil-ikon" type="submit" title="Sil" aria-label="İçeriği sil">✕</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

<?php else: ?>
    <div class="sekme-ust">
        <h2 style="font-size:17px">Ders Duyuruları</h2>
        <details class="ekle-kutu">
            <summary class="dugme dugme-birincil dugme-kucuk">＋ Yeni Duyuru</summary>
            <form method="post" action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=duyurular')) ?>" class="kart mt-2">
                <?= Auth::csrfAlani() ?>
                <input type="hidden" name="islem" value="duyuru_ekle">

                <div class="form-grup">
                    <label class="form-etiket" for="duyuru-baslik">Başlık</label>
                    <input class="form-girdi" id="duyuru-baslik" name="baslik" required maxlength="160">
                </div>

                <div class="form-grup">
                    <label class="form-etiket" for="duyuru-icerik">İçerik</label>
                    <textarea class="form-girdi" id="duyuru-icerik" name="icerik" rows="4" required></textarea>
                </div>

                <div class="form-izgara">
                    <div class="form-grup">
                        <label class="form-etiket" for="duyuru-bitis">Geçerlilik tarihi (isteğe bağlı)</label>
                        <input class="form-girdi" id="duyuru-bitis" type="date" name="bitis_tarihi">
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket">Önem</label>
                        <label class="onay-kutu">
                            <input type="checkbox" name="onemli" value="1">
                            <span>Önemli duyuru olarak işaretle</span>
                        </label>
                    </div>
                </div>

                <button class="dugme dugme-birincil" type="submit">Yayınla</button>
            </form>
        </details>
    </div>

    <?php if ($dersDuyurulari === []): ?>
        <div class="kart bos-durum">Bu derse ait duyuru yok.</div>
    <?php else: ?>
        <div class="duyuru-listesi">
            <?php foreach ($dersDuyurulari as $duyuru): ?>
                <details class="kart duyuru-kart" <?= (int) $duyuru['onemli'] === 1 ? 'open' : '' ?>>
                    <summary>
                        
                        <span class="duyuru-baslik">
                            <strong><?= e($duyuru['baslik']) ?></strong>
                            <span class="metin-kucuk metin-silik">
                                <?= e(date('d.m.Y H:i', strtotime((string) $duyuru['yayin_tarihi']))) ?>
                                <?php if (!empty($duyuru['bitis_tarihi'])): ?>
                                    · <?= e(tarih_tr((string) $duyuru['bitis_tarihi'])) ?>'e kadar
                                <?php endif; ?>
                            </span>
                        </span>
                        <?php if ((int) $duyuru['onemli'] === 1): ?>
                            <span class="rozet rozet-uyari">Önemli</span>
                        <?php endif; ?>
                    </summary>

                    <div class="duyuru-govde">
                        <p><?= nl2br(e($duyuru['icerik'])) ?></p>
                        <form method="post" action="<?= e(url('ogretmen/ders.php?id=' . $acilisId . '&sekme=duyurular')) ?>"
                              data-onay="Duyuru silinsin mi?">
                            <?= Auth::csrfAlani() ?>
                            <input type="hidden" name="islem" value="duyuru_sil">
                            <input type="hidden" name="duyuru_id" value="<?= e((string) $duyuru['id']) ?>">
                            <button class="dugme dugme-tehlike dugme-kucuk" type="submit">Duyuruyu Sil</button>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
