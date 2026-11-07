<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - haftalık ders programı.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$program   = Ogrenci::haftalikProgram((int) $kullanici['id']);

$gunlere = [];

foreach ($program as $oturum) {
    $gunlere[(int) $oturum['gun']][] = $oturum;
}

$bugun = (int) date('N');

$sayfaBaslik = 'Ders Programı';
$aktifMenu   = 'panel';
$panelAktif  = 'program';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Haftalık Ders Programı</h1>
        <p><?= e((string) count($program)) ?> oturum · aktif dönem</p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('ogrenci/dersler.php')) ?>">Derslerim</a>
</div>

<?php if ($program === []): ?>
    <div class="kart bos-durum">
        
        Aktif dönem için tanımlı ders programı bulunmuyor.
    </div>
<?php else: ?>
    <div class="program-izgara">
        <?php foreach (Ogrenci::GUNLER as $no => $gunAdi): ?>
            <section class="program-gun <?= $no === $bugun ? 'bugun' : '' ?>">
                <header>
                    <strong><?= e($gunAdi) ?></strong>
                    <?php if ($no === $bugun): ?><span class="rozet rozet-vurgu">Bugün</span><?php endif; ?>
                </header>

                <?php if (empty($gunlere[$no])): ?>
                    <div class="program-bos">Ders yok</div>
                <?php else: ?>
                    <?php foreach ($gunlere[$no] as $oturum): ?>
                        <div class="program-ders">
                            <span class="program-saat">
                                <?= e(substr((string) $oturum['baslangic_saati'], 0, 5)) ?>–<?= e(substr((string) $oturum['bitis_saati'], 0, 5)) ?>
                            </span>
                            <strong><?= e($oturum['kod']) ?></strong>
                            <span class="program-ad"><?= e($oturum['ders_adi']) ?></span>
                            <span class="program-yer"> <?= e($oturum['derslik']) ?></span>
                            <span class="program-hoca"> <?= e($oturum['hoca']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
