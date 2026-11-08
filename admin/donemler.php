<?php

declare(strict_types=1);

/**
 * Yönetici paneli - dönem yönetimi.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$don = static function (): void {
    header('Location: ' . url('admin/donemler.php'));
    exit;
};

$duzenleId = (int) ($_GET['duzenle'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    $islem   = (string) ($_POST['islem'] ?? '');
    $id      = (int) ($_POST['id'] ?? 0);
    $ad      = trim((string) ($_POST['ad'] ?? ''));
    $bas     = trim((string) ($_POST['baslangic'] ?? ''));
    $bit     = trim((string) ($_POST['bitis'] ?? ''));
    $aktifMi = !empty($_POST['aktif']);

    if ($islem === 'sil' && $id > 0) {
        [$ok, $mesaj] = Yonetici::donemSil($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Dönem silindi #' . $id, 'donemler', $id);
        }

        $don();
    }

    if ($islem === 'aktif' && $id > 0) {
        [$ok, $mesaj] = Yonetici::donemAktifYap($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Aktif dönem değiştirildi #' . $id, 'donemler', $id);
        }

        $don();
    }

    if (!in_array($islem, ['ekle', 'guncelle'], true)) {
        Auth::flash('hata', 'Bilinmeyen işlem.');
        $don();
    }

    if (mb_strlen($ad, 'UTF-8') < 4) {
        Auth::flash('hata', 'Dönem adı en az 4 karakter olmalıdır.');
        $don();
    }

    $basZaman = strtotime($bas);
    $bitZaman = strtotime($bit);

    if ($basZaman === false || $bitZaman === false) {
        Auth::flash('hata', 'Başlangıç ve bitiş tarihleri geçerli olmalıdır.');
        $don();
    }

    if ($bitZaman <= $basZaman) {
        Auth::flash('hata', 'Bitiş tarihi başlangıçtan sonra olmalıdır.');
        $don();
    }

    if ($islem === 'guncelle' && $id > 0) {
        [$ok, $mesaj] = Yonetici::donemGuncelle($id, $ad, $bas, $bit, $aktifMi);

        if ($ok) {
            Auth::log('Dönem güncellendi: ' . $ad, 'donemler', $id);
        }
    } else {
        [$ok, $mesaj] = Yonetici::donemEkle($ad, $bas, $bit, $aktifMi);
    }

    Auth::flash($ok ? 'basari' : 'hata', $mesaj);
    $don();
}

$donemler = Yonetici::donemler();

$duzenlenen = null;
foreach ($donemler as $d) {
    if ((int) $d['id'] === $duzenleId) {
        $duzenlenen = $d;
        break;
    }
}

$sayfaBaslik = 'Dönemler';
$aktifMenu   = 'panel';
$panelAktif  = 'donemler';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Dönemler</h1>
        <p>Aynı anda yalnızca bir dönem aktif olabilir.</p>
    </div>
</div>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3><?= $duzenlenen === null ? 'Yeni Dönem' : 'Dönemi Düzenle' ?></h3>
            <?php if ($duzenlenen !== null): ?>
                <a class="baglanti metin-kucuk" href="<?= e(url('admin/donemler.php')) ?>">Vazgeç</a>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('admin/donemler.php')) ?>">
            <?= Auth::csrfAlani() ?>
            <input type="hidden" name="islem" value="<?= $duzenlenen === null ? 'ekle' : 'guncelle' ?>">
            <?php if ($duzenlenen !== null): ?>
                <input type="hidden" name="id" value="<?= e((string) $duzenlenen['id']) ?>">
            <?php endif; ?>

            <div class="form-grup">
                <label class="form-etiket" for="ad">Dönem adı</label>
                <input class="form-girdi" id="ad" name="ad" value="<?= e((string) ($duzenlenen['ad'] ?? '')) ?>"
                       required maxlength="60" placeholder="2026-2027 Güz">
            </div>

            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="baslangic">Başlangıç</label>
                    <input class="form-girdi" id="baslangic" type="date" name="baslangic"
                           value="<?= e((string) ($duzenlenen['baslangic'] ?? date('Y-m-d'))) ?>" required>
                </div>
                <div class="form-grup">
                    <label class="form-etiket" for="bitis">Bitiş</label>
                    <input class="form-girdi" id="bitis" type="date" name="bitis"
                           value="<?= e((string) ($duzenlenen['bitis'] ?? date('Y-m-d', strtotime('+4 months')))) ?>" required>
                </div>
            </div>

            <div class="form-grup">
                <label class="onay-kutu">
                    <input type="checkbox" name="aktif" value="1"
                        <?= ($duzenlenen === null || (int) $duzenlenen['aktif'] === 1) ? 'checked' : '' ?>>
                    <span>Bu dönemi aktif dönem yap</span>
                </label>
            </div>

            <div class="form-eylem">
                <button class="dugme dugme-birincil" type="submit">
                    <?= $duzenlenen === null ? 'Dönem Ekle' : 'Değişiklikleri Kaydet' ?>
                </button>
            </div>
        </form>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Dönem Listesi</h3>
        </div>

        <?php if ($donemler === []): ?>
            <div class="bos-durum">Henüz dönem eklenmemiş.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Dönem</th>
                            <th class="gizle-mobil">Tarih</th>
                            <th class="metin-sag gizle-mobil">Açılış</th>
                            <th class="metin-sag gizle-mobil">Kayıt</th>
                            <th class="metin-sag">Durum</th>
                            <th class="metin-sag">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donemler as $d): ?>
                            <tr>
                                <td><strong><?= e($d['ad']) ?></strong></td>
                                <td class="metin-kucuk gizle-mobil">
                                    <?= e(date('d.m.Y', strtotime((string) $d['baslangic']))) ?>
                                    –
                                    <?= e(date('d.m.Y', strtotime((string) $d['bitis']))) ?>
                                </td>
                                <td class="metin-sag sayi gizle-mobil"><?= e((string) $d['acilis_sayisi']) ?></td>
                                <td class="metin-sag sayi gizle-mobil"><?= e((string) $d['kayit_sayisi']) ?></td>
                                <td class="metin-sag">
                                    <?php if ((int) $d['aktif'] === 1): ?>
                                        <span class="rozet rozet-basari">Aktif</span>
                                    <?php else: ?>
                                        <span class="rozet">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="metin-sag">
                                    <div class="satir-eylem">
                                        <?php if ((int) $d['aktif'] !== 1): ?>
                                            <form method="post" action="<?= e(url('admin/donemler.php')) ?>">
                                                <?= Auth::csrfAlani() ?>
                                                <input type="hidden" name="islem" value="aktif">
                                                <input type="hidden" name="id" value="<?= e((string) $d['id']) ?>">
                                                <button class="dugme dugme-hayalet dugme-kucuk" type="submit">Aktif Yap</button>
                                            </form>
                                        <?php endif; ?>

                                        <a class="dugme dugme-hayalet dugme-kucuk"
                                           href="<?= e(url('admin/donemler.php?duzenle=' . (int) $d['id'])) ?>">Düzenle</a>

                                        <form method="post" action="<?= e(url('admin/donemler.php')) ?>"
                                              data-onay="Bu dönem silinecek. Bağlı ders açılışları da etkilenir. Emin misiniz?">
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
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
