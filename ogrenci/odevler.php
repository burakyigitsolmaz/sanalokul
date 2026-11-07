<?php

declare(strict_types=1);

/**
 * Öğrenci paneli - ödevler ve dosya teslimi.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogrenci']);

$kullanici = Auth::kullanici();
$ogrenciId = (int) $kullanici['id'];
$pdo       = Database::get();

/**
 * Ödevin gerçekten öğrencinin dersine ait olduğunu doğrular.
 *
 * @return array<string, mixed>|null
 */
$odevBul = static function (int $odevId, int $ogrenci): ?array {
    $sorgu = Database::get()->prepare(
        'SELECT o.id, o.baslik, o.son_teslim, o.maks_puan,
                (SELECT t.puan FROM teslimler t WHERE t.odev_id = o.id AND t.ogrenci_id = :ogrenci2) AS mevcut_puan
         FROM odevler o
             JOIN ders_acilislari da ON da.id = o.ders_acilis_id
             JOIN ders_kayitlari dk ON dk.ders_acilis_id = da.id
         WHERE o.id = :odev AND dk.ogrenci_id = :ogrenci1 AND dk.durum IN ("aktif", "tamamlandi")
         LIMIT 1'
    );
    $sorgu->execute(['odev' => $odevId, 'ogrenci1' => $ogrenci, 'ogrenci2' => $ogrenci]);
    $satir = $sorgu->fetch();

    return $satir === false ? null : $satir;
};

// ---------------------------------------------------------------------
// Dosya teslimi
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $odevId   = (int) ($_POST['odev_id'] ?? 0);
    $aciklama = trim((string) ($_POST['aciklama'] ?? ''));
    $odev     = $odevBul($odevId, $ogrenciId);

    if ($odev === null) {
        Auth::flash('hata', 'Ödev bulunamadı veya bu ödev size ait değil.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    if ($odev['mevcut_puan'] !== null) {
        Auth::flash('uyari', 'Puanlanmış bir ödevi yeniden teslim edemezsiniz.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $dosya = $_FILES['dosya'] ?? null;

    if (!is_array($dosya) || ($dosya['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        Auth::flash('hata', 'Dosya yüklenemedi. Lütfen tekrar deneyin.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $maksBayt = (int) config('yukleme.maks_bayt', 10 * 1024 * 1024);

    if ((int) $dosya['size'] > $maksBayt) {
        Auth::flash('hata', 'Dosya çok büyük. En fazla ' . (int) round($maksBayt / 1048576) . ' MB yükleyebilirsiniz.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $uzantilar = (array) config('yukleme.izinli_uzantilar', []);
    $uzanti    = mb_strtolower(pathinfo((string) $dosya['name'], PATHINFO_EXTENSION), 'UTF-8');

    if (!in_array($uzanti, $uzantilar, true)) {
        Auth::flash('hata', 'Bu dosya türü kabul edilmiyor. İzin verilenler: ' . implode(', ', $uzantilar) . '.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file((string) $dosya['tmp_name']);
    $mimeIzinli = (array) config('yukleme.izinli_mime', []);

    if (!in_array($mime, $mimeIzinli, true)) {
        Auth::flash('hata', 'Dosya içeriği ile uzantısı uyuşmuyor.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $klasor = rtrim((string) config('yukleme.klasor', dirname(__DIR__) . '/uploads'), '/\\') . DIRECTORY_SEPARATOR . 'teslimler';

    if (!is_dir($klasor) && !mkdir($klasor, 0775, true) && !is_dir($klasor)) {
        Auth::flash('hata', 'Yükleme klasörü oluşturulamadı.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $yeniAd = sprintf('o%d_k%d_%s.%s', $odevId, $ogrenciId, bin2hex(random_bytes(6)), $uzanti);
    $hedef  = $klasor . DIRECTORY_SEPARATOR . $yeniAd;

    if (!move_uploaded_file((string) $dosya['tmp_name'], $hedef)) {
        Auth::flash('hata', 'Dosya kaydedilemedi.');
        header('Location: ' . url('ogrenci/odevler.php'));
        exit;
    }

    $goreliYol = 'uploads/teslimler/' . $yeniAd;

    $kaydet = $pdo->prepare(
        'INSERT INTO teslimler (odev_id, ogrenci_id, dosya_yolu, aciklama, teslim_zamani)
         VALUES (:odev, :ogrenci, :dosya, :aciklama, NOW())
         ON DUPLICATE KEY UPDATE
             dosya_yolu = VALUES(dosya_yolu),
             aciklama = VALUES(aciklama),
             teslim_zamani = NOW()'
    );
    $kaydet->execute([
        'odev'     => $odevId,
        'ogrenci'  => $ogrenciId,
        'dosya'    => $goreliYol,
        'aciklama' => $aciklama === '' ? null : $aciklama,
    ]);

    Auth::log('Ödev teslim edildi: ' . $odev['baslik'], 'teslimler', $odevId);
    Auth::flash('basari', 'Ödeviniz teslim edildi: ' . $dosya['name']);
    header('Location: ' . url('ogrenci/odevler.php'));
    exit;
}

// ---------------------------------------------------------------------
// Liste
// ---------------------------------------------------------------------
$odevler = Ogrenci::tumOdevler($ogrenciId);

$filtre = (string) ($_GET['durum'] ?? 'tumu');
$izinliFiltre = ['tumu', 'bekliyor', 'teslim', 'puanlandi', 'gecti'];

if (!in_array($filtre, $izinliFiltre, true)) {
    $filtre = 'tumu';
}

$sayaclar = ['tumu' => 0, 'bekliyor' => 0, 'teslim' => 0, 'puanlandi' => 0, 'gecti' => 0];

foreach ($odevler as $odev) {
    $sayaclar['tumu']++;
    $sayaclar[Ogrenci::odevDurumu($odev)]++;
}

$gosterilecek = $filtre === 'tumu'
    ? $odevler
    : array_values(array_filter($odevler, static fn (array $o): bool => Ogrenci::odevDurumu($o) === $filtre));

$sekmeler = [
    'tumu'      => 'Tümü',
    'bekliyor'  => 'Bekleyen',
    'teslim'    => 'Teslim Edilen',
    'puanlandi' => 'Puanlanan',
    'gecti'     => 'Süresi Geçen',
];

$sayfaBaslik = 'Ödevler';
$aktifMenu   = 'panel';
$panelAktif  = 'odevler';

require dirname(__DIR__) . '/templates/panel_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Ödevler</h1>
        <p><?= e((string) $sayaclar['bekliyor']) ?> bekleyen · <?= e((string) $sayaclar['teslim']) ?> teslim · <?= e((string) $sayaclar['puanlandi']) ?> puanlanan</p>
    </div>
</div>

<nav class="sekme-cubugu" aria-label="Ödev filtresi">
    <?php foreach ($sekmeler as $anahtar => $ad): ?>
        <a class="sekme <?= $filtre === $anahtar ? 'aktif' : '' ?>"
           href="<?= e(url('ogrenci/odevler.php?durum=' . $anahtar)) ?>">
            <?= e($ad) ?>
            <span class="sekme-sayi"><?= e((string) $sayaclar[$anahtar]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($gosterilecek === []): ?>
    <div class="kart bos-durum">
        
        Bu filtrede ödev bulunmuyor.
    </div>
<?php else: ?>
    <div class="odev-listesi">
        <?php foreach ($gosterilecek as $odev): ?>
            <?php
            $durum = Ogrenci::odevDurumu($odev);
            $kalan = Ogrenci::kalanSure((string) $odev['son_teslim']);
            $durumEtiket = match ($durum) {
                'bekliyor'  => ['Bekliyor', 'rozet-uyari'],
                'teslim'    => ['Teslim edildi', 'rozet-vurgu'],
                'puanlandi' => ['Puanlandı', 'rozet-basari'],
                default     => ['Süresi geçti', 'rozet-hata'],
            };
            ?>
            <article class="kart odev-kart">
                <div class="odev-ust">
                    <div style="min-width:0">
                        <div class="odev-ders"><?= e($odev['kod']) ?> · <?= e($odev['ders_adi']) ?></div>
                        <h3><?= e($odev['baslik']) ?></h3>
                    </div>
                    <span class="rozet <?= e($durumEtiket[1]) ?>"><?= e($durumEtiket[0]) ?></span>
                </div>

                <p class="odev-aciklama"><?= e($odev['aciklama']) ?></p>

                <div class="odev-meta">
                    <span> Son teslim: <strong><?= e(date('d.m.Y H:i', strtotime((string) $odev['son_teslim']))) ?></strong></span>
                    <span> <?= e((string) $odev['maks_puan']) ?> puan</span>
                    <?php if ($durum === 'bekliyor'): ?>
                        <span class="rozet <?= e($kalan['sinif']) ?>"><?= e($kalan['metin']) ?></span>
                    <?php endif; ?>
                    <?php if ($durum === 'puanlandi'): ?>
                        <span class="rozet rozet-basari sayi"><?= e((string) $odev['puan']) ?>/<?= e((string) $odev['maks_puan']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($odev['teslim_id'] !== null): ?>
                    <div class="teslim-kutu">
                        <div>
                            <strong>Tesliminiz</strong>
                            <span class="metin-kucuk metin-soluk">
                                <?= e(basename((string) $odev['teslim_dosyasi'])) ?>
                                · <?= e(date('d.m.Y H:i', strtotime((string) $odev['teslim_zamani']))) ?>
                            </span>
                        </div>
                        <?php if (!empty($odev['geri_bildirim'])): ?>
                            <p class="geri-bildirim"> <?= e($odev['geri_bildirim']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($durum === 'puanlandi'): ?>
                    <div class="metin-silik metin-kucuk">Puanlanmış ödev yeniden teslim edilemez.</div>
                <?php else: ?>
                    <details class="teslim-form-kutu">
                        <summary><?= $odev['teslim_id'] !== null ? 'Yeniden teslim et' : 'Ödev teslim et' ?></summary>
                        <form method="post" action="<?= e(url('ogrenci/odevler.php')) ?>"
                              enctype="multipart/form-data" data-tek-gonderim class="mt-2">
                            <?= Auth::csrfAlani() ?>
                            <input type="hidden" name="odev_id" value="<?= e((string) $odev['id']) ?>">

                            <div class="form-grup">
                                <label class="form-etiket" for="dosya-<?= e((string) $odev['id']) ?>">Dosya</label>
                                <input class="form-girdi" type="file" id="dosya-<?= e((string) $odev['id']) ?>"
                                       name="dosya" required
                                       accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg">
                                <div class="form-ipucu">
                                    PDF, DOC, DOCX, ZIP, PNG, JPG · en fazla <?= e((string) (int) round((int) config('yukleme.maks_bayt', 0) / 1048576)) ?> MB
                                </div>
                            </div>

                            <div class="form-grup">
                                <label class="form-etiket" for="aciklama-<?= e((string) $odev['id']) ?>">Açıklama (isteğe bağlı)</label>
                                <textarea class="form-girdi" id="aciklama-<?= e((string) $odev['id']) ?>"
                                          name="aciklama" rows="2" maxlength="1000"></textarea>
                            </div>

                            <button class="dugme dugme-birincil dugme-kucuk" type="submit">Teslim Et</button>
                        </form>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/panel_son.php'; ?>
