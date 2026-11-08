<?php

declare(strict_types=1);

/**
 * Yönetici paneli - genel duyurular.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$kapsam = (string) ($_GET['kapsam'] ?? '');
$duzenleId = (int) ($_GET['duzenle'] ?? 0);

$don = static function () use ($kapsam): void {
    $qs = $kapsam === '' ? '' : '?kapsam=' . urlencode($kapsam);
    header('Location: ' . url('admin/duyurular.php' . $qs));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    $islem    = (string) ($_POST['islem'] ?? '');
    $id       = (int) ($_POST['id'] ?? 0);
    $baslik   = trim((string) ($_POST['baslik'] ?? ''));
    $icerik   = trim((string) ($_POST['icerik'] ?? ''));
    $onemliMi = !empty($_POST['onemli']);
    $bitis    = trim((string) ($_POST['bitis'] ?? ''));

    if ($islem === 'sil' && $id > 0) {
        [$ok, $mesaj] = Yonetici::duyuruSil($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Duyuru silindi #' . $id, 'duyurular', $id);
        }

        $don();
    }

    if (!in_array($islem, ['ekle'], true)) {
        Auth::flash('hata', 'Bilinmeyen işlem.');
        $don();
    }

    if ($baslik === '' || mb_strlen($baslik, 'UTF-8') > 160) {
        Auth::flash('hata', 'Başlık zorunludur ve en fazla 160 karakter olabilir.');
        $don();
    }

    if ($icerik === '') {
        Auth::flash('hata', 'İçerik zorunludur.');
        $don();
    }

    $bitisZaman = $bitis === '' ? null : strtotime($bitis);
    if ($bitis !== '' && $bitisZaman === false) {
        Auth::flash('hata', 'Bitiş tarihi geçerli olmalıdır.');
        $don();
    }

    [$ok, $mesaj] = Yonetici::duyuruEkle(
        (int) Auth::kullanici()['id'],
        $baslik,
        $icerik,
        $onemliMi,
        $bitisZaman !== null ? date('Y-m-d H:i:s', $bitisZaman) : null
    );

    Auth::flash($ok ? 'basari' : 'hata', $mesaj);
    $don();
}

$duyurular = Yonetici::duyurular($kapsam === 'genel' || $kapsam === '' ? 'genel' : 'ders');

$duzenlenen = null;
if ($duzenleId > 0) {
    foreach ($duyurular as $d) {
        if ((int) $d['id'] === $duzenleId) {
            $duzenlenen = $d;
            break;
        }
    }
}

$sayfaBaslik = 'Duyurular';
$aktifMenu   = 'panel';
$panelAktif  = 'duyurular';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Duyurular</h1>
        <p><?= e((string) count($duyurular)) ?> duyuru · <?= $kapsam === 'ders' ? 'Ders duyuruları' : 'Genel duyurular' ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="dugme dugme-hayalet dugme-kucuk <?= $kapsam !== 'ders' ? 'dugme-birincil' : '' ?>" href="<?= e(url('admin/duyurular.php?kapsam=genel')) ?>">Genel</a>
        <a class="dugme dugme-hayalet dugme-kucuk <?= $kapsam === 'ders' ? 'dugme-birincil' : '' ?>" href="<?= e(url('admin/duyurular.php?kapsam=ders')) ?>">Ders</a>
        <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('admin/duyurular.php?duzenle=0' . ($kapsam !== '' ? '&kapsam=' . $kapsam : ''))) ?>">Yeni Duyuru</a>
    </div>
</div>

<section class="kart">
    <div class="kart-baslik">
        <h3><?= $duzenlenen === null ? 'Yeni Duyuru Yayınla' : 'Duyuyu Düzenle' ?></h3>
        <?php if ($duzenlenen !== null): ?>
            <a class="baglanti metin-kucuk" href="<?= e(url('admin/duyurular.php' . ($kapsam !== '' ? '?kapsam=' . $kapsam : ''))) ?>">Vazgeç</a>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e(url('admin/duyurular.php' . ($kapsam !== '' ? '?kapsam=' . $kapsam : ''))) ?>">
        <?= Auth::csrfAlani() ?>
        <input type="hidden" name="islem" value="ekle">
        <?php if ($duzenlenen !== null): ?>
            <input type="hidden" name="id" value="<?= e((string) $duzenlenen['id']) ?>">
        <?php endif; ?>

        <div class="form-grup">
            <label class="form-etiket" for="baslik">Başlık</label>
            <input class="form-girdi" id="baslik" name="baslik" value="<?= e((string) ($duzenlenen['baslik'] ?? '')) ?>"
                   required maxlength="160" placeholder="Sınav programı güncellendi">
        </div>

        <div class="form-grup">
            <label class="form-etiket" for="icerik">İçerik</label>
            <textarea class="form-girdi" id="icerik" name="icerik" rows="5" required
                      placeholder="Duyuru metni..."><?= e((string) ($duzenlenen['icerik'] ?? '')) ?></textarea>
        </div>

        <div class="form-izgara">
            <div class="form-grup">
                <label class="form-etiket" for="bitis">Bitiş tarihi (isteğe bağlı)</label>
                <input class="form-girdi" id="bitis" type="datetime-local" name="bitis"
                       value="<?= e((string) ($duzenlenen['bitis_tarihi'] ?? '')) ?>">
            </div>
            <div class="form-grup">
                <label class="onay-kutu">
                    <input type="checkbox" name="onemli" value="1"
                        <?= (int) ($duzenlenen['onemli'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <span>Önemli / sabitle</span>
                </label>
            </div>
        </div>

        <div class="form-eylem">
            <button class="dugme dugme-birincil" type="submit">
                <?= $duzenlenen === null ? 'Yayımla' : 'Değişiklikleri Kaydet' ?>
            </button>
        </div>
    </form>
</section>

<?php if ($duyurular !== []): ?>
    <section class="kart mt-3">
        <div class="kart-baslik">
            <h3>Yayınlanan Duyurular</h3>
            <span class="rozet"><?= e((string) count($duyurular)) ?></span>
        </div>
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th class="gizle-mobil">İçerik</th>
                        <th class="gizle-mobil">Yazar</th>
                        <th class="metin-sag gizle-mobil">Tarih</th>
                        <th class="metin-sag">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($duyurular as $d): ?>
                        <tr>
                            <td>
                                <strong><?= e($d['baslik']) ?></strong>
                                <?php if ((int) $d['onemli'] === 1): ?>
                                    <span class="rozet rozet-vurgu metin-kucuk">Önemli</span>
                                <?php endif; ?>
                                <div class="metin-silik metin-kucuk"><?= e((string) $d['yazar']) ?></div>
                            </td>
                            <td class="metin-kucuk gizle-mobil"><?= e(mb_strimwidth((string) $d['icerik'], 0, 90, '…')) ?></td>
                            <td class="metin-kucuk gizle-mobil"><?= e(date('d.m.Y H:i', strtotime((string) $d['yayin_tarihi']))) ?></td>
                            <td class="metin-sag sayi gizle-mobil"><?= e(date('d.m.Y', strtotime((string) $d['yayin_tarihi']))) ?></td>
                            <td class="metin-sag">
                                <div class="satir-eylem">
                                    <form method="post" action="<?= e(url('admin/duyurular.php' . ($kapsam !== '' ? '?kapsam=' . $kapsam : ''))) ?>"
                                          data-onay="Bu duyuru silinecek. Emin misiniz?">
                                        <?= Auth::csrfAlani() ?>
                                        <input type="hidden" name="islem" value="sil">
                                        <input type="hidden" name="id" value="<?= e((string) $d['id']) ?>">
                                        <button class="dugme dugme-tehlike dugme-kucuk" type="submit">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php else: ?>
    <div class="kart bos-durum">Bu kapsamda duyuru yok.</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
