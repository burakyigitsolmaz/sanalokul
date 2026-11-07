<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - genel bakış.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$pdo       = Database::get();

$tumDersler   = Ogrenci::dersler((int) $kullanici['id']);
$aktifDersler = array_values(array_filter($tumDersler, static fn (array $d): bool => (int) $d['aktif'] === 1));
$ozet         = Ogrenci::donemOzeti($aktifDersler);
$donem        = $pdo->query('SELECT ad, baslangic, bitis FROM donemler WHERE aktif = 1 LIMIT 1')->fetch() ?: null;

$tumOdevler = Ogrenci::tumOdevler((int) $kullanici['id']);

$bekleyenOdevler = array_values(array_filter(
    $tumOdevler,
    static fn (array $o): bool => Ogrenci::odevDurumu($o) === 'bekliyor'
));
$teslimEdilen = count(array_filter($tumOdevler, static fn (array $o): bool => $o['teslim_id'] !== null));

$program = Ogrenci::haftalikProgram((int) $kullanici['id']);
$bugun   = (int) date('N');
$bugununProgrami = array_values(array_filter($program, static fn (array $p): bool => (int) $p['gun'] === $bugun));

$duyurular = array_slice(Ogrenci::duyurular((int) $kullanici['id'], 4), 0, 4);

$ortalama = $ozet['ortalama'];
$harf = null;

if ($ortalama !== null) {
    $harf = match (true) {
        $ortalama >= 90 => 'AA',
        $ortalama >= 85 => 'BA',
        $ortalama >= 80 => 'BB',
        $ortalama >= 75 => 'CB',
        $ortalama >= 70 => 'CC',
        $ortalama >= 65 => 'DC',
        $ortalama >= 60 => 'DD',
        $ortalama >= 50 => 'FD',
        default         => 'FF',
    };
}

$kayitliDers = count($aktifDersler);
$notluDers   = $ozet['notlu'];

$sayfaBaslik = 'Öğrenci Paneli';
$aktifMenu   = 'panel';
$panelAktif  = 'panel';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Merhaba, <?= e($kullanici['ad']) ?> </h1>
        <p>
            <?= e($kullanici['numara']) ?> · <?= e($kullanici['bolum'] ?? '-') ?>
            <?= !empty($kullanici['sinif']) ? '· ' . e((string) $kullanici['sinif']) . '. sınıf' : '' ?>
            <?= $donem !== null ? '· ' . e($donem['ad']) : '' ?>
        </p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogrenci/notlar.php')) ?>">Notlarım →</a>
</div>

<div class="izgara izgara-4" style="margin-bottom:22px">
    <div class="stat-kart">
        <div class="etiket">Aktif Ders</div>
        <div class="deger"><?= e((string) $kayitliDers) ?></div>
        <div class="alt"><?= e((string) $ozet['kredi']) ?> kredi · <?= e((string) $ozet['akts']) ?> AKTS</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Notu Girilen</div>
        <div class="deger"><?= e((string) $notluDers) ?> / <?= e((string) $kayitliDers) ?></div>
        <div class="alt">ders</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Dönem Ortalaması</div>
        <div class="deger"><?= $ortalama !== null ? e(number_format($ortalama, 2, ',', '.')) : '—' ?></div>
        <div class="alt"><?= $harf !== null ? 'tahmini ' . e($harf) : 'not bekleniyor' ?></div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Bekleyen Ödev</div>
        <div class="deger"><?= e((string) count($bekleyenOdevler)) ?></div>
        <div class="alt"><?= e((string) $teslimEdilen) ?> teslim yapıldı</div>
    </div>
</div>

<div class="izgara izgara-2" style="margin-bottom:22px;align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Bugünün Programı</h3>
            <span class="rozet"><?= e(tarih_tr(date('Y-m-d'))) ?></span>
        </div>

        <?php if ($bugununProgrami === []): ?>
            <div class="bos-durum">
                
                <?= $bugun > 5 ? 'Hafta sonu — bugün dersin yok.' : 'Bugün dersin yok.' ?>
            </div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($bugununProgrami as $ders): ?>
                    <div class="satir">
                        
                        <div class="satir-icerik">
                            <strong><?= e($ders['kod']) ?> · <?= e($ders['ders_adi']) ?></strong>
                            <span><?= e($ders['derslik']) ?> · <?= e($ders['hoca']) ?></span>
                        </div>
                        <div class="satir-sag sayi">
                            <?= e(substr((string) $ders['baslangic_saati'], 0, 5)) ?>–<?= e(substr((string) $ders['bitis_saati'], 0, 5)) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Yaklaşan Ödevler</h3>
            <a class="baglanti metin-kucuk" href="<?= e(url('ogrenci/odevler.php')) ?>">Tümü →</a>
        </div>

        <?php if ($bekleyenOdevler === []): ?>
            <div class="bos-durum">Bekleyen ödevin yok.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach (array_slice($bekleyenOdevler, 0, 3) as $odev): ?>
                    <?php $kalan = Ogrenci::kalanSure((string) $odev['son_teslim']); ?>
                    <div class="satir">
                        
                        <div class="satir-icerik">
                            <strong><?= e($odev['baslik']) ?></strong>
                            <span><?= e($odev['kod']) ?> · son teslim <?= e(tarih_tr((string) $odev['son_teslim'])) ?></span>
                        </div>
                        <span class="rozet <?= e($kalan['sinif']) ?>"><?= e($kalan['metin']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Derslerim</h3>
            <span class="rozet"><?= e((string) $kayitliDers) ?> ders</span>
        </div>

        <?php if ($aktifDersler === []): ?>
            <div class="bos-durum">Bu dönem kayıtlı dersin yok.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($aktifDersler as $ders): ?>
                    <a class="satir satir-baglanti" href="<?= e(url('ogrenci/ders.php?id=' . (int) $ders['kayit_id'])) ?>">
                        
                        <div class="satir-icerik">
                            <strong><?= e($ders['kod']) ?> · <?= e($ders['ad']) ?></strong>
                            <span><?= e($ders['hoca']) ?> · <?= e((string) $ders['kredi']) ?> kredi</span>
                        </div>
                        <?php if ($ders['harf_notu'] !== null): ?>
                            <span class="rozet <?= e(Ogrenci::harfSinifi((string) $ders['harf_notu'])) ?>"><?= e($ders['harf_notu']) ?></span>
                        <?php else: ?>
                            <span class="rozet">—</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Son Duyurular</h3>
            <a class="baglanti metin-kucuk" href="<?= e(url('ogrenci/duyurular.php')) ?>">Tümü →</a>
        </div>

        <?php if ($duyurular === []): ?>
            <div class="bos-durum">Yayında duyuru yok.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($duyurular as $duyuru): ?>
                    <div class="satir" style="align-items:flex-start">
                        
                        <div class="satir-icerik" style="white-space:normal">
                            <strong style="white-space:normal"><?= e($duyuru['baslik']) ?></strong>
                            <span><?= e($duyuru['kapsam']) ?> · <?= e(tarih_tr(substr((string) $duyuru['yayin_tarihi'], 0, 10))) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
