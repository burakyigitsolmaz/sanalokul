<?php

declare(strict_types=1);

/**
 * Ana sayfa.
 * Giriş yapmamış ziyaretçiye tanıtım, giriş yapmış kullanıcıya kişisel özet gösterir.
 */

require_once __DIR__ . '/src/bootstrap.php';

$kullanici     = Auth::kullanici();
$girisYapmisMi = $kullanici !== null;
$pdo           = null;
$program       = [];
$duyurular     = [];
$yemek         = null;
$yemekBugun    = false;
$hataMesaji    = null;

try {
    $pdo = Database::get();

    // Bugünün ders programı (view aktif dönemi ve bugünü filtreler).
    $program = $pdo->query(
        'SELECT * FROM v_bugunun_programi ORDER BY baslangic_saati LIMIT 12'
    )->fetchAll();

    // Role göre kişisel filtre.
    if ($girisYapmisMi && $kullanici['rol'] === 'ogrenci' && !empty($kullanici['sinif'])) {
        $program = array_values(array_filter(
            $program,
            static fn (array $satir): bool => (int) $satir['sinif'] === (int) $kullanici['sinif']
        ));
    } elseif ($girisYapmisMi && $kullanici['rol'] === 'ogretim_elemani') {
        $program = array_values(array_filter(
            $program,
            static fn (array $satir): bool => $satir['ogretim_elemani'] === $kullanici['ad_soyad']
        ));
    }

    // Son duyurular.
    $duyurular = $pdo->query(
        "SELECT d.id, d.baslik, d.icerik, d.onemli, d.yayin_tarihi,
                CASE WHEN d.ders_acilis_id IS NULL THEN 'Genel'
                     ELSE CONCAT(dr.kod, ' · ', dr.ad) END AS kapsam,
                CONCAT(k.ad, ' ', k.soyad) AS yazar
         FROM duyurular d
             JOIN kullanicilar k ON k.id = d.yazar_id
             LEFT JOIN ders_acilislari da ON da.id = d.ders_acilis_id
             LEFT JOIN dersler dr ON dr.id = da.ders_id
         WHERE d.bitis_tarihi IS NULL OR d.bitis_tarihi >= NOW()
         ORDER BY d.onemli DESC, d.yayin_tarihi DESC
         LIMIT 3"
    )->fetchAll();

    // Yemek menüsü: önce bugün, yoksa en yakın ileri tarihli menü.
    $yemekSorgu = $pdo->prepare('SELECT * FROM yemek_menuleri WHERE tarih = CURDATE() LIMIT 1');
    $yemekSorgu->execute();
    $yemek = $yemekSorgu->fetch() ?: null;

    if ($yemek === null) {
        $sonrakiSorgu = $pdo->prepare(
            'SELECT * FROM yemek_menuleri WHERE tarih >= CURDATE() ORDER BY tarih LIMIT 1'
        );
        $sonrakiSorgu->execute();
        $yemek = $sonrakiSorgu->fetch() ?: null;
    }

    $yemekBugun = $yemek !== null && (string) $yemek['tarih'] === date('Y-m-d');
} catch (Throwable $hata) {
    $hataMesaji = $hata->getMessage();
}

$sayfaBaslik = 'Ana Sayfa';
$aktifMenu   = 'ana';

/** @var array<int, array{ad:string, aciklama:string, ikon:string, durum:string, yol:?string}> $ozellikler */
$ozellikler = [
    [
        'ad'       => 'Ders Programı',
        'aciklama' => 'Haftalık ders programını ve derslik bilgilerini görüntüle.',
        'durum'    => 'hazir',
        'yol'      => $girisYapmisMi ? Auth::panelYolu() : url('giris.php'),
    ],
    [
        'ad'       => 'Notlar ve Başarı',
        'aciklama' => 'Vize, final, ortalama ve harf notlarını tek ekranda takip et.',
        'durum'    => 'hazir',
        'yol'      => $girisYapmisMi ? Auth::panelYolu() : url('giris.php'),
    ],
    [
        'ad'       => 'Duyurular',
        'aciklama' => 'Okul ve ders duyurularını kaçırmadan takip et.',
        'durum'    => 'hazir',
        'yol'      => null,
    ],
    [
        'ad'       => 'Yemekhane',
        'aciklama' => 'Günlük yemek menüsünü ve kalori bilgilerini gör.',
        'durum'    => $yemek !== null ? 'hazir' : 'yakinda',
        'yol'      => null,
    ],
    [
        'ad'       => 'Ödevler',
        'aciklama' => 'Ödevleri görüntüle, dosya teslim et ve geri bildirim al.',
        'durum'    => 'hazir',
        'yol'      => $girisYapmisMi ? Auth::panelYolu() : url('giris.php'),
    ],
    [
        'ad'       => 'Kampüs Haberleri',
        'aciklama' => 'Nallıhan MYO’nun resmî sitesinden son haberler.',
        'durum'    => 'yakinda',
        'yol'      => null,
    ],
];

require __DIR__ . '/templates/header.php';
?>

<?php if (!$girisYapmisMi): ?>
    <section class="hero">
        <img class="hero-gorsel" src="<?= e(url('assets/img/kampus.jpg')) ?>" alt="Nallıhan MYO kampüsü">
        <div class="hero-katman"></div>
        <div class="hero-icerik">
            <span class="hero-etiket">Ankara Üniversitesi · Nallıhan MYO</span>
            <h1>Derslerin, notların ve duyuruların tek yerde.</h1>
            <p>
                Bilgisayar Programcılığı öğrencileri için hazırlanmış sanal okul uygulaması.
                Ders programını gör, notlarını takip et, yemek menüsünü ve duyuruları kaçırma.
            </p>
            <div class="hero-dugme-grubu">
                <a class="dugme dugme-birincil" href="<?= e(url('giris.php')) ?>">Giriş Yap</a>
                <a class="dugme dugme-hayalet" href="#ozellikler" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.22);color:#eaf2ff">
                    Neler var?
                </a>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="kart" style="margin-bottom:22px;background:linear-gradient(135deg,var(--vurgu-zemin),transparent 65%)">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:18px;justify-content:space-between">
            <div>
                <span class="rozet rozet-vurgu"><?= e(Auth::rolEtiketi()) ?></span>
                <h1 style="font-size:24px;margin:10px 0 4px">Hoş geldin, <?= e($kullanici['ad']) ?>.</h1>
                <p class="metin-soluk mb-0" style="font-size:13.8px">
                    <?= e($kullanici['numara']) ?>
                    <?php if (!empty($kullanici['bolum'])): ?> · <?= e($kullanici['bolum']) ?><?php endif; ?>
                    <?php if (!empty($kullanici['sinif'])): ?> · <?= e((string) $kullanici['sinif']) ?>. sınıf<?php endif; ?>
                    <?php if (!empty($kullanici['unvan'])): ?> · <?= e($kullanici['unvan']) ?><?php endif; ?>
                </p>
            </div>
            <a class="dugme dugme-birincil" href="<?= e(Auth::panelYolu()) ?>">Panelime Git →</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($hataMesaji !== null): ?>
    <div class="uyari uyari-hata">
        <span class="uyari-ikon">✕</span>
        <span>Veritabanı bağlantısı kurulamadı: <?= e($hataMesaji) ?></span>
    </div>
<?php endif; ?>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Bugünün Programı</h3>
            <span class="rozet"><?= e(tarih_tr(date('Y-m-d'))) ?></span>
        </div>

        <?php if ($program === []): ?>
            <div class="bos-durum">
                
                <?= $girisYapmisMi ? 'Bugün planlanmış dersin yok. İyi dinlenmeler!' : 'Bugün için planlanmış ders bulunmuyor.' ?>
            </div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($program as $ders): ?>
                    <div class="satir">
                        
                        <div class="satir-icerik">
                            <strong><?= e($ders['ders_kodu']) ?> · <?= e($ders['ders_adi']) ?></strong>
                            <span><?= e($ders['ogretim_elemani']) ?> · <?= e($ders['derslik']) ?></span>
                        </div>
                        <div class="satir-sag sayi">
                            <?= e(substr((string) $ders['baslangic_saati'], 0, 5)) ?>
                            –<?= e(substr((string) $ders['bitis_saati'], 0, 5)) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Son Duyurular</h3>
            <span class="rozet"><?= e((string) count($duyurular)) ?> kayıt</span>
        </div>

        <?php if ($duyurular === []): ?>
            <div class="bos-durum">
                
                Yayında olan duyuru yok.
            </div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($duyurular as $duyuru): ?>
                    <div class="satir" style="align-items:flex-start">
                        
                        <div class="satir-icerik" style="white-space:normal">
                            <strong style="white-space:normal"><?= e($duyuru['baslik']) ?></strong>
                            <span>
                                <?= e($duyuru['kapsam']) ?> · <?= e(tarih_tr(substr((string) $duyuru['yayin_tarihi'], 0, 10))) ?>
                            </span>
                        </div>
                        <?php if ($duyuru['onemli']): ?>
                            <span class="rozet rozet-uyari">Önemli</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php if ($yemek !== null): ?>
    <section class="kart mt-2">
        <div class="kart-baslik">
            <h3><?= $yemekBugun ? 'Bugünün Yemek Menüsü' : 'Sonraki Yemek Menüsü' ?></h3>
            <span class="rozet rozet-vurgu">
                <?= e(date('d.m.Y', strtotime((string) $yemek['tarih']))) ?>
                <?php if (!empty($yemek['toplam_kcal'])): ?>
                    · <?= e((string) $yemek['toplam_kcal']) ?> kcal
                <?php endif; ?>
            </span>
        </div>
        <div class="izgara izgara-4" style="gap:12px">
            <?php
            $menuler = [
                ['Çorba', $yemek['corba'], $yemek['corba_kcal']],
                ['Ana Yemek', $yemek['ana_yemek'], $yemek['ana_kcal']],
                ['Yardımcı Yemek', $yemek['yardimci_yemek'], $yemek['yardimci_kcal']],
                ['Tatlı / Salata', $yemek['tatli_salata'], $yemek['ek_kcal']],
            ];
            ?>
            <?php foreach ($menuler as [$etiket, $ad, $kcal]): ?>
                <?php if ($ad === null || $ad === ''): ?><?php continue; ?><?php endif; ?>
                <div style="background:var(--yuzey-2);border:1px solid var(--kenar-yumusak);border-radius:12px;padding:13px 15px">
                    <div class="metin-silik metin-kucuk" style="text-transform:uppercase;letter-spacing:.05em;font-weight:600"><?= e($etiket) ?></div>
                    <div style="font-weight:600;font-size:14px;margin-top:3px"><?= e($ad) ?></div>
                    <?php if ($kcal !== null): ?>
                        <div class="metin-silik metin-kucuk"><?= e((string) $kcal) ?> kcal</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="mt-3" id="ozellikler">
    <div class="bolum-basligi">
        <h2>Sanal Okulda neler var?</h2>
        <span class="rozet">Ders projesi</span>
    </div>

    <div class="izgara izgara-otomatik">
        <?php foreach ($ozellikler as $ozellik): ?>
            <?php if ($ozellik['yol'] !== null): ?>
                <a class="ozellik" href="<?= e($ozellik['yol']) ?>">
                    
                    <h3><?= e($ozellik['ad']) ?></h3>
                    <p><?= e($ozellik['aciklama']) ?></p>
                </a>
            <?php else: ?>
                <div class="ozellik" style="cursor:default">
                    
                    <h3>
                        <?= e($ozellik['ad']) ?>
                        <?php if ($ozellik['durum'] === 'yakinda'): ?>
                            <span class="rozet" style="margin-left:6px">Yakında</span>
                        <?php endif; ?>
                    </h3>
                    <p><?= e($ozellik['aciklama']) ?></p>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
