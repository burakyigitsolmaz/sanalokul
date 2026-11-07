<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - tüm ödevler.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$odevler   = Ogretmen::tumOdevler((int) $kullanici['id']);

$toplamTeslim  = array_sum(array_map(static fn (array $o): int => (int) $o['teslim_sayisi'], $odevler));
$bekleyen      = array_sum(array_map(static fn (array $o): int => (int) $o['bekleyen'], $odevler));
$aktifOdevler  = array_values(array_filter($odevler, static fn (array $o): bool => (int) $o['aktif'] === 1));

$sayfaBaslik = 'Ödevler';
$aktifMenu   = 'panel';
$panelAktif  = 'odevler';

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Ödevler</h1>
        <p><?= e((string) count($odevler)) ?> ödev · <?= e((string) $toplamTeslim) ?> teslim · <?= e((string) $bekleyen) ?> bekliyor</p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogretmen/dersler.php')) ?>">Derslerim</a>
</div>

<?php if ($odevler === []): ?>
    <div class="kart bos-durum">
        
        Henüz ödev tanımlanmamış. Ders sayfasından yeni ödev ekleyebilirsiniz.
    </div>
<?php else: ?>
    <?php if ($aktifOdevler !== []): ?>
        <div class="bolum-basligi">
            <h2 style="font-size:16px">Bu Dönem</h2>
            <span class="rozet rozet-vurgu"><?= e((string) count($aktifOdevler)) ?> ödev</span>
        </div>
    <?php endif; ?>

    <div class="izgara izgara-2">
        <?php foreach ($odevler as $odev): ?>
            <?php $kalan = Ogrenci::kalanSure((string) $odev['son_teslim']); ?>
            <article class="kart odev-kart">
                <div class="odev-ust">
                    <div style="min-width:0">
                        <div class="odev-ders"><?= e($odev['kod']) ?> · <?= e($odev['ders_adi']) ?></div>
                        <h3><?= e($odev['baslik']) ?></h3>
                    </div>
                    <?php if ((int) $odev['teslim_sayisi'] === 0): ?>
                        <span class="rozet">Teslim yok</span>
                    <?php elseif ((int) $odev['bekleyen'] > 0): ?>
                        <span class="rozet rozet-uyari"><?= e((string) $odev['bekleyen']) ?> bekliyor</span>
                    <?php else: ?>
                        <span class="rozet rozet-basari">Tamamlandı</span>
                    <?php endif; ?>
                </div>

                <div class="odev-meta">
                    <span> <strong><?= e(date('d.m.Y H:i', strtotime((string) $odev['son_teslim']))) ?></strong></span>
                    <span> <?= e((string) $odev['maks_puan']) ?> puan</span>
                    <span> <?= e((string) $odev['teslim_sayisi']) ?>/<?= e((string) $odev['ogrenci_sayisi']) ?> teslim</span>
                    <span class="rozet <?= e($kalan['sinif']) ?>"><?= e($kalan['metin']) ?></span>
                </div>

                <div class="kart-eylem">
                    <a class="dugme dugme-birincil dugme-kucuk"
                       href="<?= e(url('ogretmen/odev.php?id=' . (int) $odev['id'])) ?>">Değerlendir →</a>
                    <a class="dugme dugme-hayalet dugme-kucuk"
                       href="<?= e(url('ogretmen/ders.php?id=' . (int) $odev['acilis_id'] . '&sekme=odevler')) ?>">Ders Sayfası</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
