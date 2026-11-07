<?php

declare(strict_types=1);

/**
 * Yönetici paneli - ders yönetimi.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$bolumFiltre = (int) ($_GET['bolum'] ?? 0);
$duzenleId   = (int) ($_GET['duzenle'] ?? 0);

$don = static function () use ($bolumFiltre): void {
    header('Location: ' . url('admin/dersler.php' . ($bolumFiltre > 0 ? '?bolum=' . $bolumFiltre : '')));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    $islem = (string) ($_POST['islem'] ?? '');
    $id    = (int) ($_POST['id'] ?? 0);

    if ($islem === 'sil' && $id > 0) {
        [$ok, $mesaj] = Yonetici::dersSil($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Ders silindi #' . $id, 'dersler', $id);
        }

        $don();
    }

    if (!in_array($islem, ['ekle', 'guncelle'], true)) {
        Auth::flash('hata', 'Bilinmeyen işlem.');
        $don();
    }

    $kod     = mb_strtoupper(trim((string) ($_POST['kod'] ?? '')), 'UTF-8');
    $ad      = trim((string) ($_POST['ad'] ?? ''));
    $bolumId = (int) ($_POST['bolum_id'] ?? 0);
    $kredi   = (int) ($_POST['kredi'] ?? 3);
    $akts    = (int) ($_POST['akts'] ?? 4);
    $sinif   = (int) ($_POST['sinif'] ?? 1);
    $zorunlu = !empty($_POST['zorunlu_mu']) ? 1 : 0;

    if ($kod === '' || mb_strlen($kod, 'UTF-8') > 12) {
        Auth::flash('hata', 'Ders kodu zorunludur ve en fazla 12 karakter olabilir.');
        $don();
    }

    if (mb_strlen($ad, 'UTF-8') < 3) {
        Auth::flash('hata', 'Ders adı en az 3 karakter olmalıdır.');
        $don();
    }

    if ($bolumId <= 0) {
        Auth::flash('hata', 'Bölüm seçilmelidir.');
        $don();
    }

    if ($kredi < 1 || $kredi > 10) {
        Auth::flash('hata', 'Kredi 1 ile 10 arasında olmalıdır.');
        $don();
    }

    if ($akts < 1 || $akts > 30) {
        Auth::flash('hata', 'AKTS 1 ile 30 arasında olmalıdır.');
        $don();
    }

    if ($sinif < 1 || $sinif > 2) {
        Auth::flash('hata', 'Sınıf 1 veya 2 olmalıdır.');
        $don();
    }

    $veri = [
        'bolum_id'   => $bolumId,
        'kod'        => $kod,
        'ad'         => $ad,
        'kredi'      => $kredi,
        'akts'       => $akts,
        'zorunlu_mu' => $zorunlu,
        'sinif'      => $sinif,
    ];

    if ($islem === 'guncelle' && $id > 0) {
        [$ok, $mesaj] = Yonetici::dersGuncelle($id, $veri);

        if ($ok) {
            Auth::log('Ders güncellendi: ' . $kod, 'dersler', $id);
        }
    } else {
        [$ok, $mesaj] = Yonetici::dersEkle($veri);
    }

    Auth::flash($ok ? 'basari' : 'hata', $mesaj);
    $don();
}

$bolumler = Yonetici::bolumler();
$dersler  = Yonetici::dersler($bolumFiltre > 0 ? $bolumFiltre : null);

$duzenlenen = null;
if ($duzenleId > 0) {
    $duzenlenen = Yonetici::ders($duzenleId);
}

$sayfaBaslik = 'Dersler';
$aktifMenu   = 'panel';
$panelAktif  = 'dersler';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Dersler</h1>
        <p><?= e((string) count($dersler)) ?> ders · bölüm ve sınıf bazlı</p>
    </div>
</div>

<form class="filtre-cubuk" method="get" action="<?= e(url('admin/dersler.php')) ?>">
    <div class="filtre-alan">
        <label class="form-etiket" for="bolum">Bölüm</label>
        <select class="form-girdi" id="bolum" name="bolum">
            <option value="">Tümü</option>
            <?php foreach ($bolumler as $b): ?>
                <option value="<?= e((string) $b['id']) ?>" <?= $bolumFiltre === (int) $b['id'] ? 'selected' : '' ?>>
                    <?= e($b['kod'] . ' · ' . $b['ad']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtre-eylem">
        <button class="dugme dugme-birincil dugme-kucuk" type="submit">Filtrele</button>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/dersler.php')) ?>">Sıfırla</a>
    </div>
</form>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3><?= $duzenlenen === null ? 'Yeni Ders' : 'Dersi Düzenle' ?></h3>
            <?php if ($duzenlenen !== null): ?>
                <a class="baglanti metin-kucuk" href="<?= e(url('admin/dersler.php' . ($bolumFiltre > 0 ? '?bolum=' . $bolumFiltre : ''))) ?>">Vazgeç</a>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('admin/dersler.php' . ($bolumFiltre > 0 ? '?bolum=' . $bolumFiltre : ''))) ?>">
            <?= Auth::csrfAlani() ?>
            <input type="hidden" name="islem" value="<?= $duzenlenen === null ? 'ekle' : 'guncelle' ?>">
            <?php if ($duzenlenen !== null): ?>
                <input type="hidden" name="id" value="<?= e((string) $duzenlenen['id']) ?>">
            <?php endif; ?>

            <div class="form-grup">
                <label class="form-etiket" for="bolum_id">Bölüm</label>
                <select class="form-girdi" id="bolum_id" name="bolum_id" required>
                    <option value="">Bölüm seçin…</option>
                    <?php foreach ($bolumler as $b): ?>
                        <option value="<?= e((string) $b['id']) ?>"
                            <?= (int) ($duzenlenen['bolum_id'] ?? $bolumFiltre) === (int) $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['kod'] . ' · ' . $b['ad']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="kod">Ders kodu</label>
                    <input class="form-girdi" id="kod" name="kod" value="<?= e((string) ($duzenlenen['kod'] ?? '')) ?>"
                           required maxlength="12" placeholder="NBP101">
                </div>
                <div class="form-grup">
                    <label class="form-etiket" for="sinif">Sınıf</label>
                    <select class="form-girdi" id="sinif" name="sinif">
                        <option value="1" <?= (int) ($duzenlenen['sinif'] ?? 1) === 1 ? 'selected' : '' ?>>1. sınıf</option>
                        <option value="2" <?= (int) ($duzenlenen['sinif'] ?? 1) === 2 ? 'selected' : '' ?>>2. sınıf</option>
                    </select>
                </div>
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="ad">Ders adı</label>
                <input class="form-girdi" id="ad" name="ad" value="<?= e((string) ($duzenlenen['ad'] ?? '')) ?>"
                       required maxlength="140" placeholder="Programlama Temelleri">
            </div>

            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="kredi">Kredi</label>
                    <input class="form-girdi" id="kredi" type="number" name="kredi" min="1" max="10"
                           value="<?= e((string) ($duzenlenen['kredi'] ?? 3)) ?>">
                </div>
                <div class="form-grup">
                    <label class="form-etiket" for="akts">AKTS</label>
                    <input class="form-girdi" id="akts" type="number" name="akts" min="1" max="30"
                           value="<?= e((string) ($duzenlenen['akts'] ?? 4)) ?>">
                </div>
            </div>

            <div class="form-grup">
                <label class="onay-kutu">
                    <input type="checkbox" name="zorunlu_mu" value="1"
                        <?= (int) ($duzenlenen['zorunlu_mu'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <span>Zorunlu ders</span>
                </label>
            </div>

            <div class="form-eylem">
                <button class="dugme dugme-birincil" type="submit">
                    <?= $duzenlenen === null ? 'Ders Ekle' : 'Değişiklikleri Kaydet' ?>
                </button>
            </div>
        </form>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Ders Listesi</h3>
            <span class="rozet"><?= e((string) count($dersler)) ?></span>
        </div>

        <?php if ($dersler === []): ?>
            <div class="bos-durum">Bu filtreye uygun ders yok.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Kod</th>
                            <th>Ders</th>
                            <th class="gizle-mobil">Bölüm</th>
                            <th class="metin-sag gizle-mobil">K/A</th>
                            <th class="metin-sag">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dersler as $d): ?>
                            <tr>
                                <td>
                                    <strong><?= e($d['kod']) ?></strong>
                                    <div class="metin-silik metin-kucuk"><?= e((string) $d['sinif']) ?>. sınıf</div>
                                </td>
                                <td>
                                    <?= e($d['ad']) ?>
                                    <?php if ((int) $d['zorunlu_mu'] === 1): ?>
                                        <span class="rozet rozet-vurgu metin-kucuk">Zorunlu</span>
                                    <?php endif; ?>
                                    <div class="metin-silik metin-kucuk"><?= e((string) $d['acilis_sayisi']) ?> açılış</div>
                                </td>
                                <td class="metin-kucuk gizle-mobil"><?= e($d['bolum_kod']) ?></td>
                                <td class="metin-sag sayi gizle-mobil"><?= e((string) $d['kredi']) ?>/<?= e((string) $d['akts']) ?></td>
                                <td class="metin-sag">
                                    <div class="satir-eylem">
                                        <a class="dugme dugme-hayalet dugme-kucuk"
                                           href="<?= e(url('admin/dersler.php?duzenle=' . (int) $d['id'] . ($bolumFiltre > 0 ? '&bolum=' . $bolumFiltre : ''))) ?>">Düzenle</a>

                                        <form method="post" action="<?= e(url('admin/dersler.php' . ($bolumFiltre > 0 ? '?bolum=' . $bolumFiltre : ''))) ?>"
                                              onsubmit="return confirm('Bu ders ve bağlı tüm açılış/kayıtlar silinecek. Emin misiniz?');">
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
