<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - genel bakış.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$hocaId    = (int) $kullanici['id'];

$dersler       = Ogretmen::dersler($hocaId);
$aktifDersler  = array_values(array_filter($dersler, static fn (array $d): bool => (int) $d['aktif'] === 1));
$toplamOgrenci = array_sum(array_map(static fn (array $d): int => (int) $d['ogrenci_sayisi'], $aktifDersler));
$notBekleyen   = array_sum(array_map(
    static fn (array $d): int => max(0, (int) $d['ogrenci_sayisi'] - (int) $d['notlu_sayisi']),
    $aktifDersler
));
$bekleyenTeslim = array_sum(array_map(static fn (array $d): int => (int) $d['bekleyen_teslim'], $aktifDersler));

$sonTeslimler = Ogretmen::bekleyenTeslimler($hocaId, 6);
$duyurular    = array_slice(Ogretmen::duyurular($hocaId), 0, 5);

$sayfaBaslik = 'Öğretim Elemanı Paneli';
$aktifMenu   = 'panel';
$panelAktif  = 'panel';

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Merhaba, <?= e(trim(($kullanici['unvan'] ?? '') . ' ' . $kullanici['ad'])) ?> </h1>
        <p><?= e($kullanici['bolum'] ?? '-') ?> · <?= e((string) count($aktifDersler)) ?> aktif ders</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('ogretmen/dersler.php')) ?>">Derslerim</a>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogretmen/odevler.php')) ?>">Ödevler</a>
    </div>
</div>

<div class="izgara izgara-4" style="margin-bottom:22px">
    <div class="stat-kart">
        <div class="etiket">Aktif Ders</div>
        <div class="deger"><?= e((string) count($aktifDersler)) ?></div>
        <div class="alt"><?= e((string) count($dersler)) ?> toplam dönem kaydı</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Öğrenci</div>
        <div class="deger"><?= e((string) $toplamOgrenci) ?></div>
        <div class="alt">aktif kayıt</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Not Bekleyen</div>
        <div class="deger"><?= e((string) $notBekleyen) ?></div>
        <div class="alt">öğrenci</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Değerlendirme</div>
        <div class="deger"><?= e((string) $bekleyenTeslim) ?></div>
        <div class="alt">bekleyen teslim</div>
    </div>
</div>

<div class="izgara izgara-2" style="margin-bottom:22px;align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Verdiğim Dersler</h3>
            <span class="rozet"><?= e((string) count($dersler)) ?> kayıt</span>
        </div>

        <?php if ($dersler === []): ?>
            <div class="bos-durum">Tanımlı dersiniz bulunmuyor.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($dersler as $ders): ?>
                    <a class="satir satir-baglanti" href="<?= e(url('ogretmen/ders.php?id=' . (int) $ders['acilis_id'])) ?>">
                        
                        <div class="satir-icerik">
                            <strong><?= e($ders['kod']) ?> · <?= e($ders['ad']) ?></strong>
                            <span>
                                <?= e($ders['donem']) ?> · <?= e($ders['sube']) ?> şube ·
                                <?= e((string) $ders['ogrenci_sayisi']) ?> öğrenci
                            </span>
                        </div>
                        <?php if ((int) $ders['aktif'] === 1): ?>
                            <span class="rozet rozet-basari">Aktif</span>
                        <?php else: ?>
                            <span class="rozet">Geçmiş</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Değerlendirme Bekleyenler</h3>
            <a class="baglanti metin-kucuk" href="<?= e(url('ogretmen/odevler.php')) ?>">Tümü →</a>
        </div>

        <?php if ($sonTeslimler === []): ?>
            <div class="bos-durum">Bekleyen teslim yok.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($sonTeslimler as $teslim): ?>
                    <a class="satir satir-baglanti"
                       href="<?= e(url('ogretmen/odev.php?id=' . (int) $teslim['odev_id'])) ?>">
                        
                        <div class="satir-icerik">
                            <strong><?= e($teslim['ad_soyad']) ?> · <?= e($teslim['numara']) ?></strong>
                            <span><?= e($teslim['kod']) ?> · <?= e($teslim['odev_baslik']) ?></span>
                        </div>
                        <span class="rozet rozet-uyari">Bekliyor</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Son Duyurularım</h3>
            <a class="baglanti metin-kucuk" href="<?= e(url('ogretmen/duyurular.php')) ?>">Tümü →</a>
        </div>

        <?php if ($duyurular === []): ?>
            <div class="bos-durum">Henüz duyuru yayınlamadınız.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($duyurular as $duyuru): ?>
                    <div class="satir" style="align-items:flex-start">
                        
                        <div class="satir-icerik" style="white-space:normal">
                            <strong style="white-space:normal"><?= e($duyuru['baslik']) ?></strong>
                            <span>
                                <?= e($duyuru['kod']) ?> · <?= e(date('d.m.Y H:i', strtotime((string) $duyuru['yayin_tarihi']))) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik"><h3>Hızlı İşlemler</h3></div>
        <div class="hizli-islem">
            <a class="hizli-kutu" href="<?= e(url('ogretmen/dersler.php')) ?>">
                
                <strong>Not Girişi</strong>
                <span>Ders seç, vize/final gir</span>
            </a>
            <a class="hizli-kutu" href="<?= e(url('ogretmen/odevler.php')) ?>">
                
                <strong>Ödev Değerlendir</strong>
                <span>Puan ve geri bildirim</span>
            </a>
            <a class="hizli-kutu" href="<?= e(url('ogretmen/duyurular.php')) ?>">
                
                <strong>Duyuru Yayınla</strong>
                <span>Dersine duyuru ekle</span>
            </a>
            <a class="hizli-kutu" href="<?= e(url('ogretmen/program.php')) ?>">
                
                <strong>Programım</strong>
                <span>Haftalık ders saatleri</span>
            </a>
        </div>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
