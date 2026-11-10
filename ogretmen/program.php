<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - haftalık ders programı.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$program   = Ogretmen::program((int) $kullanici['id']);

$gunlere = [];

foreach ($program as $oturum) {
    $gunlere[(int) $oturum['gun']][] = $oturum;
}

$bugun = (int) date('N');

$sayfaBaslik = 'Ders Programı';
$aktifMenu   = 'panel';
$panelAktif  = 'program';

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<div class="sayfa-basligi program-basligi">
    <div>
        <h1>Haftalık Ders Programım</h1>
        <p><?= e((string) count($program)) ?> oturum · aktif dönem</p>
    </div>
    <div class="sayfa-eylem">
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogretmen/dersler.php')) ?>">Derslerim</a>
        <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('program.ics.php')) ?>">Takvime ekle</a>
        <button class="dugme dugme-hayalet dugme-kucuk" type="button" data-yazdir>Yazdır</button>
    </div>
</div>

<div class="yazdir-baslik">
    <strong>Haftalık Ders Programı</strong>
    <span><?= e($kullanici['ad'] . ' ' . $kullanici['soyad']) ?> · aktif dönem</span>
</div>

<?php if ($program === []): ?>
    <div class="kart bos-durum">
        
        Aktif dönem için tanımlı ders programınız bulunmuyor.
    </div>
<?php else: ?>
    <div class="program-izgara">
        <?php foreach (Ogretmen::GUNLER as $no => $gunAdi): ?>
            <?php if (empty($gunlere[$no])) { continue; } ?>
            <section class="program-gun <?= $no === $bugun ? 'bugun' : '' ?>">
                <header>
                    <strong><?= e($gunAdi) ?></strong>
                    <?php if ($no === $bugun): ?><span class="rozet rozet-vurgu">Bugün</span><?php endif; ?>
                </header>

                <?php foreach ($gunlere[$no] as $oturum): ?>
                    <div class="program-ders">
                        <span class="program-saat">
                            <?= e(substr((string) $oturum['baslangic_saati'], 0, 5)) ?>–<?= e(substr((string) $oturum['bitis_saati'], 0, 5)) ?>
                        </span>
                        <strong><?= e($oturum['kod']) ?> · <?= e($oturum['sube']) ?></strong>
                        <span class="program-ad"><?= e($oturum['ders_adi']) ?></span>
                        <span class="program-yer"> <?= e($oturum['derslik']) ?></span>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
