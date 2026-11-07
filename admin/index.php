<?php

declare(strict_types=1);

/**
 * Yönetici paneli - genel bakış.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$kullanici = Auth::kullanici();
$pdo       = Database::get();

$sayilar = Yonetici::sayilar();

$roller = $pdo->query(
    'SELECT r.ad AS rol, COUNT(k.id) AS adet
     FROM roller r
         LEFT JOIN kullanicilar k ON k.rol_id = r.id AND k.aktif = 1
     GROUP BY r.id, r.ad
     ORDER BY r.ad'
)->fetchAll();

$dersOzet = $pdo->query(
    'SELECT ders_kodu, ders_adi, donem, ogretim_elemani, kayitli_ogrenci,
            notu_girilen, ortalama, basari_yuzdesi
     FROM v_ders_basari_istatistik
     WHERE donem = (SELECT ad FROM donemler WHERE aktif = 1 LIMIT 1)
     ORDER BY ders_kodu'
)->fetchAll();

[$loglar] = Yonetici::loglar('', '', 8, 0);

$sayfaBaslik = 'Yönetici Paneli';
$aktifMenu   = 'panel';
$panelAktif  = 'panel';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Yönetim Paneli</h1>
        <p>Sistem Yöneticisi · <?= e($kullanici['numara']) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('admin/kullanicilar.php')) ?>">Kullanıcılar</a>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/atama.php')) ?>">Derse Atama</a>
    </div>
</div>

<div class="izgara izgara-4" style="margin-bottom:16px">
    <div class="stat-kart">
        <div class="etiket">Kullanıcı</div>
        <div class="deger"><?= e((string) $sayilar['kullanici']) ?></div>
        <div class="alt"><?= e((string) $sayilar['ogrenci']) ?> öğrenci · <?= e((string) $sayilar['hoca']) ?> hoca</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Ders</div>
        <div class="deger"><?= e((string) $sayilar['ders']) ?></div>
        <div class="alt"><?= e((string) $sayilar['acilis']) ?> ders açılışı</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Aktif Kayıt</div>
        <div class="deger"><?= e((string) $sayilar['kayit']) ?></div>
        <div class="alt">ders kaydı</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Duyuru</div>
        <div class="deger"><?= e((string) $sayilar['duyuru']) ?></div>
        <div class="alt">genel + ders duyurusu</div>
    </div>
</div>

<div class="izgara izgara-3" style="margin-bottom:22px">
    <?php foreach ($roller as $rol): ?>
        <div class="stat-kart">
            <div class="etiket"><?= e(Auth::rolEtiketi($rol['rol'])) ?></div>
            <div class="deger"><?= e((string) $rol['adet']) ?></div>
            <div class="alt"><?= e($rol['rol']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<section class="kart mb-3">
    <div class="kart-baslik">
        <h3>Aktif Dönem Ders Başarısı</h3>
        <span class="rozet"><?= e((string) count($dersOzet)) ?> ders</span>
    </div>

    <?php if ($dersOzet === []): ?>
        <div class="bos-durum">Bu dönem için istatistik yok.</div>
    <?php else: ?>
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Ders</th>
                        <th>Öğretim Elemanı</th>
                        <th class="metin-sag">Kayıtlı</th>
                        <th class="metin-sag gizle-mobil">Notu Girilen</th>
                        <th class="metin-sag">Ortalama</th>
                        <th class="metin-sag">Başarı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dersOzet as $ders): ?>
                        <tr>
                            <td>
                                <strong><?= e($ders['ders_kodu']) ?></strong>
                                <div class="metin-silik metin-kucuk"><?= e($ders['ders_adi']) ?></div>
                            </td>
                            <td class="metin-kucuk"><?= e($ders['ogretim_elemani']) ?></td>
                            <td class="metin-sag sayi"><?= e((string) $ders['kayitli_ogrenci']) ?></td>
                            <td class="metin-sag sayi gizle-mobil"><?= e((string) $ders['notu_girilen']) ?></td>
                            <td class="metin-sag sayi"><?= $ders['ortalama'] !== null ? e(number_format((float) $ders['ortalama'], 2, ',', '.')) : '—' ?></td>
                            <td class="metin-sag">
                                <?php if ($ders['basari_yuzdesi'] === null): ?>
                                    <span class="metin-silik">—</span>
                                <?php else: ?>
                                    <span class="rozet <?= (float) $ders['basari_yuzdesi'] >= 70 ? 'rozet-basari' : ((float) $ders['basari_yuzdesi'] >= 40 ? 'rozet-uyari' : 'rozet-hata') ?>">
                                        %<?= e(number_format((float) $ders['basari_yuzdesi'], 1, ',', '')) ?>
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
            <h3>Son İşlemler</h3>
            <a class="baglanti metin-kucuk" href="<?= e(url('admin/loglar.php')) ?>">Tümü →</a>
        </div>

        <?php if ($loglar === []): ?>
            <div class="bos-durum">Henüz işlem kaydı yok.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($loglar as $log): ?>
                    <div class="satir">
                        <div class="satir-icerik">
                            <strong class="kirp"><?= e($log['islem']) ?></strong>
                            <span><?= e($log['kim']) ?> · <?= e($log['ip'] ?? '-') ?></span>
                        </div>
                        <div class="satir-sag sayi"><?= e(date('d.m H:i', strtotime((string) $log['zaman']))) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik"><h3>Hızlı İşlemler</h3></div>
        <div class="hizli-islem">
            <a class="hizli-kutu" href="<?= e(url('admin/kullanici.php')) ?>">
                <strong>Kullanıcı Ekle</strong>
                <span>Öğrenci veya öğretim elemanı</span>
            </a>
            <a class="hizli-kutu" href="<?= e(url('admin/donemler.php')) ?>">
                <strong>Dönem Yönetimi</strong>
                <span>Aktif dönemi belirle</span>
            </a>
            <a class="hizli-kutu" href="<?= e(url('admin/acilislar.php')) ?>">
                <strong>Ders Aç</strong>
                <span>Ders, dönem ve hoca seç</span>
            </a>
            <a class="hizli-kutu" href="<?= e(url('admin/csv.php')) ?>">
                <strong>CSV Aktar</strong>
                <span>Toplu öğrenci ekle</span>
            </a>
        </div>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
