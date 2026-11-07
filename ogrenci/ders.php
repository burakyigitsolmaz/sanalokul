<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - tek dersin ayrıntısı.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$kayitId   = (int) ($_GET['id'] ?? 0);
$ders      = $kayitId > 0 ? Ogrenci::ders((int) $kullanici['id'], $kayitId) : null;

$sayfaBaslik = $ders !== null ? $ders['kod'] . ' · ' . $ders['ad'] : 'Ders Bulunamadı';
$aktifMenu   = 'panel';
$panelAktif  = 'dersler';

if ($ders === null) {
    http_response_code(404);
    require dirname(__DIR__) . '/templates/panel_bas.php';
    ?>
    <div class="kart metin-orta" style="padding:36px 26px">
        
        <h1 style="font-size:22px">Ders bulunamadı</h1>
        <p class="metin-soluk">Bu ders kaydı size ait değil ya da kayıt silinmiş olabilir.</p>
        <a class="dugme dugme-birincil" href="<?= e(url('ogrenci/dersler.php')) ?>">Derslerime Dön</a>
    </div>
    <?php
    require dirname(__DIR__) . '/templates/panel_son.php';
    exit;
}

$program   = Ogrenci::dersProgrami((int) $ders['acilis_id']);
$icerikler = Ogrenci::icerikler((int) $ders['acilis_id']);
$odevler   = Ogrenci::dersOdevleri((int) $ders['acilis_id'], (int) $kullanici['id']);

$duyurular = array_values(array_filter(
    Ogrenci::duyurular((int) $kullanici['id'], 50),
    static fn (array $d): bool => (int) ($d['ders_acilis_id'] ?? 0) === (int) $ders['acilis_id']
));

$acikOdev = count(array_filter($odevler, static fn (array $o): bool => Ogrenci::odevDurumu($o) === 'bekliyor'));
$teslim   = count(array_filter($odevler, static fn (array $o): bool => $o['teslim_id'] !== null));

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<nav class="kirinti" aria-label="Sayfa yolu">
    <a href="<?= e(url('ogrenci/')) ?>">Panelim</a>
    <span>/</span>
    <a href="<?= e(url('ogrenci/dersler.php')) ?>">Derslerim</a>
    <span>/</span>
    <span class="simdiki"><?= e($ders['kod']) ?></span>
</nav>

<div class="sayfa-basligi">
    <div>
        <h1><?= e($ders['ad']) ?></h1>
        <p>
            <?= e($ders['kod']) ?> · <?= e($ders['donem']) ?>
            <?= (int) $ders['aktif'] === 1 ? '· <span class="rozet rozet-basari">Aktif</span>' : '' ?>
            · <?= e($ders['sube']) ?> şube
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogrenci/odevler.php')) ?>">Ödevler</a>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogrenci/notlar.php')) ?>">Notlarım</a>
    </div>
</div>

<div class="izgara izgara-4" style="margin-bottom:22px">
    <div class="stat-kart">
        <div class="etiket">Vize</div>
        <div class="deger"><?= $ders['vize'] !== null ? e(number_format((float) $ders['vize'], 0)) : '—' ?></div>
        <div class="alt">%40 etkili</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Final</div>
        <div class="deger"><?= $ders['final_notu'] !== null ? e(number_format((float) $ders['final_notu'], 0)) : '—' ?></div>
        <div class="alt">%60 etkili</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Ortalama</div>
        <div class="deger"><?= $ders['ortalama'] !== null ? e(number_format((float) $ders['ortalama'], 2, ',', '.')) : '—' ?></div>
        <div class="alt">
            <?php if ($ders['ortalama'] !== null): ?>
                <span class="ilerleme" style="margin-top:6px">
                    <span style="width:<?= e((string) min(100, (float) $ders['ortalama'])) ?>%"></span>
                </span>
            <?php else: ?>
                not bekleniyor
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Harf Notu</div>
        <div class="deger"><?= $ders['harf_notu'] !== null ? e($ders['harf_notu']) : '—' ?></div>
        <div class="alt">
            <?= $ders['harf_notu'] !== null && Ogrenci::harfPuani((string) $ders['harf_notu']) !== null
                ? e(number_format((float) Ogrenci::harfPuani((string) $ders['harf_notu']), 2, ',', '.')) . ' / 4.00'
                : 'henüz oluşmadı' ?>
        </div>
    </div>
</div>

<div class="izgara izgara-2" style="align-items:start">
    <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
        <section class="kart">
            <div class="kart-baslik">
                <h3>Haftalık Ders Programı</h3>
                <span class="rozet"><?= e((string) count($program)) ?> oturum</span>
            </div>

            <?php if ($program === []): ?>
                <div class="bos-durum">Program bilgisi girilmemiş.</div>
            <?php else: ?>
                <div class="satir-liste">
                    <?php foreach ($program as $oturum): ?>
                        <div class="satir">
                            
                            <div class="satir-icerik">
                                <strong><?= e(Ogrenci::GUNLER[(int) $oturum['gun']] ?? '-') ?></strong>
                                <span><?= e($oturum['derslik']) ?></span>
                            </div>
                            <div class="satir-sag sayi">
                                <?= e(substr((string) $oturum['baslangic_saati'], 0, 5)) ?>–<?= e(substr((string) $oturum['bitis_saati'], 0, 5)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="kart">
            <div class="kart-baslik">
                <h3>Ders İçerikleri</h3>
                <span class="rozet"><?= e((string) count($icerikler)) ?> hafta</span>
            </div>

            <?php if ($icerikler === []): ?>
                <div class="bos-durum">Henüz içerik eklenmemiş.</div>
            <?php else: ?>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
        <section class="kart">
            <div class="kart-baslik">
                <h3>Ders Bilgileri</h3>
            </div>
            <dl class="bilgi-listesi">
                <div><dt>Öğretim Elemanı</dt><dd><?= e(trim(($ders['hoca_unvan'] ?? '') . ' ' . $ders['hoca'])) ?></dd></div>
                <div><dt>E-posta</dt><dd class="metin-kucuk"><?= e($ders['hoca_eposta']) ?></dd></div>
                <div><dt>Dönem</dt><dd><?= e($ders['donem']) ?></dd></div>
                <div><dt>Kredi / AKTS</dt><dd><?= e((string) $ders['kredi']) ?> kredi · <?= e((string) $ders['akts']) ?> AKTS</dd></div>
                <div><dt>Tür</dt><dd><?= (int) $ders['zorunlu_mu'] === 1 ? 'Zorunlu' : 'Seçmeli' ?></dd></div>
                <div><dt>Sınıf Mevcudu</dt><dd><?= e((string) $ders['sinif_mesudu']) ?> öğrenci</dd></div>
                <?php if ($ders['guncelleme_zamani'] !== null): ?>
                    <div><dt>Not Güncellemesi</dt><dd><?= e(date('d.m.Y H:i', strtotime((string) $ders['guncelleme_zamani']))) ?></dd></div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="kart">
            <div class="kart-baslik">
                <h3>Ödevler</h3>
                <a class="baglanti metin-kucuk" href="<?= e(url('ogrenci/odevler.php')) ?>">Tümü →</a>
            </div>

            <?php if ($odevler === []): ?>
                <div class="bos-durum">Bu derse ödev verilmemiş.</div>
            <?php else: ?>
                <div class="metin-kucuk metin-soluk mb-2">
                    <?= e((string) $acikOdev) ?> bekleyen · <?= e((string) $teslim) ?> teslim edildi
                </div>
                <div class="satir-liste">
                    <?php foreach ($odevler as $odev): ?>
                        <?php
                        $durum = Ogrenci::odevDurumu($odev);
                        $kalan = Ogrenci::kalanSure((string) $odev['son_teslim']);
                        ?>
                        <div class="satir" style="align-items:flex-start">
                            
                            <div class="satir-icerik" style="white-space:normal">
                                <strong style="white-space:normal"><?= e($odev['baslik']) ?></strong>
                                <span>
                                    Son teslim <?= e(tarih_tr((string) $odev['son_teslim'])) ?>
                                    <?php if ($durum === 'puanlandi'): ?>
                                        · <?= e((string) $odev['puan']) ?>/<?= e((string) $odev['maks_puan']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($durum === 'bekliyor'): ?>
                                <span class="rozet <?= e($kalan['sinif']) ?>"><?= e($kalan['metin']) ?></span>
                            <?php elseif ($durum === 'puanlandi'): ?>
                                <span class="rozet rozet-basari">Puanlandı</span>
                            <?php elseif ($durum === 'teslim'): ?>
                                <span class="rozet rozet-vurgu">Teslim</span>
                            <?php else: ?>
                                <span class="rozet rozet-hata">Geçti</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($duyurular !== []): ?>
            <section class="kart">
                <div class="kart-baslik">
                    <h3>Ders Duyuruları</h3>
                    <span class="rozet"><?= e((string) count($duyurular)) ?></span>
                </div>
                <div class="satir-liste">
                    <?php foreach (array_slice($duyurular, 0, 4) as $duyuru): ?>
                        <div class="satir" style="align-items:flex-start">
                            
                            <div class="satir-icerik" style="white-space:normal">
                                <strong style="white-space:normal"><?= e($duyuru['baslik']) ?></strong>
                                <span><?= e(tarih_tr(substr((string) $duyuru['yayin_tarihi'], 0, 10))) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
