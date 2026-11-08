<?php

declare(strict_types=1);

/**
 * Yönetici paneli - ders açılışları.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$donemFiltre = (int) ($_GET['donem'] ?? 0);
$duzenleId   = (int) ($_GET['duzenle'] ?? 0);

$don = static function () use ($donemFiltre): void {
    header('Location: ' . url('admin/acilislar.php' . ($donemFiltre > 0 ? '?donem=' . $donemFiltre : '')));
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
        [$ok, $mesaj] = Yonetici::acilisSil($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Ders açılışı silindi #' . $id, 'ders_acilislari', $id);
        }

        $don();
    }

    if (!in_array($islem, ['ekle', 'guncelle'], true)) {
        Auth::flash('hata', 'Bilinmeyen işlem.');
        $don();
    }

    $dersId    = (int) ($_POST['ders_id'] ?? 0);
    $acilisDon = (int) ($_POST['donem_id'] ?? 0);
    $hocaId    = (int) ($_POST['ogretim_elemani_id'] ?? 0);
    $sube      = mb_strtoupper(trim((string) ($_POST['sube'] ?? 'A')), 'UTF-8');
    $kontenjan = trim((string) ($_POST['kontenjan'] ?? ''));

    if ($dersId <= 0) {
        Auth::flash('hata', 'Ders seçilmelidir.');
        $don();
    }

    if ($acilisDon <= 0) {
        Auth::flash('hata', 'Dönem seçilmelidir.');
        $don();
    }

    if ($hocaId <= 0) {
        Auth::flash('hata', 'Öğretim elemanı seçilmelidir.');
        $don();
    }

    if ($sube === '' || mb_strlen($sube, 'UTF-8') > 5) {
        Auth::flash('hata', 'Şube en fazla 5 karakter olmalıdır.');
        $don();
    }

    if ($kontenjan !== '' && ((int) $kontenjan < 1 || (int) $kontenjan > 500)) {
        Auth::flash('hata', 'Kontenjan 1 ile 500 arasında olmalı ya da boş bırakılmalıdır.');
        $don();
    }

    $veri = [
        'ders_id'             => $dersId,
        'donem_id'            => $acilisDon,
        'ogretim_elemani_id'  => $hocaId,
        'sube'                => $sube,
        'kontenjan'           => $kontenjan,
    ];

    if ($islem === 'guncelle' && $id > 0) {
        [$ok, $mesaj] = Yonetici::acilisGuncelle($id, $veri);

        if ($ok) {
            Auth::log('Ders açılışı güncellendi #' . $id, 'ders_acilislari', $id);
        }
    } else {
        [$ok, $mesaj] = Yonetici::acilisEkle($veri);
    }

    Auth::flash($ok ? 'basari' : 'hata', $mesaj);
    $don();
}

$donemler = Yonetici::donemler();
$dersler  = Yonetici::dersler();
$hocalar  = Yonetici::hocalar();
$acilislar = Yonetici::acilislar($donemFiltre > 0 ? $donemFiltre : null);

$duzenlenen = null;
if ($duzenleId > 0) {
    $duzenlenen = Yonetici::acilis($duzenleId);
}

$sayfaBaslik = 'Ders Açılışları';
$aktifMenu   = 'panel';
$panelAktif  = 'acilislar';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Ders Açılışları</h1>
        <p><?= e((string) count($acilislar)) ?> açılış · dönem, hoca ve şube eşleşmesi</p>
    </div>
</div>

<form class="filtre-cubuk" method="get" action="<?= e(url('admin/acilislar.php')) ?>">
    <div class="filtre-alan">
        <label class="form-etiket" for="donem">Dönem</label>
        <select class="form-girdi" id="donem" name="donem">
            <option value="">Tümü</option>
            <?php foreach ($donemler as $d): ?>
                <option value="<?= e((string) $d['id']) ?>" <?= $donemFiltre === (int) $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['ad']) ?><?= (int) $d['aktif'] === 1 ? ' (aktif)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtre-eylem">
        <button class="dugme dugme-birincil dugme-kucuk" type="submit">Filtrele</button>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/acilislar.php')) ?>">Sıfırla</a>
    </div>
</form>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3><?= $duzenlenen === null ? 'Yeni Ders Açılışı' : 'Açılışı Düzenle' ?></h3>
            <?php if ($duzenlenen !== null): ?>
                <a class="baglanti metin-kucuk" href="<?= e(url('admin/acilislar.php' . ($donemFiltre > 0 ? '?donem=' . $donemFiltre : ''))) ?>">Vazgeç</a>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('admin/acilislar.php' . ($donemFiltre > 0 ? '?donem=' . $donemFiltre : ''))) ?>">
            <?= Auth::csrfAlani() ?>
            <input type="hidden" name="islem" value="<?= $duzenlenen === null ? 'ekle' : 'guncelle' ?>">
            <?php if ($duzenlenen !== null): ?>
                <input type="hidden" name="id" value="<?= e((string) $duzenlenen['id']) ?>">
            <?php endif; ?>

            <div class="form-grup">
                <label class="form-etiket" for="ders_id">Ders</label>
                <select class="form-girdi" id="ders_id" name="ders_id" required>
                    <option value="">Ders seçin…</option>
                    <?php foreach ($dersler as $d): ?>
                        <option value="<?= e((string) $d['id']) ?>"
                            <?= (int) ($duzenlenen['ders_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>>
                            <?= e($d['kod'] . ' · ' . $d['ad'] . ' (' . $d['bolum_kod'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="donem_id">Dönem</label>
                    <select class="form-girdi" id="donem_id" name="donem_id" required>
                        <option value="">Dönem seçin…</option>
                        <?php foreach ($donemler as $d): ?>
                            <option value="<?= e((string) $d['id']) ?>"
                                <?= (int) ($duzenlenen['donem_id'] ?? $donemFiltre) === (int) $d['id'] ? 'selected' : '' ?>>
                                <?= e($d['ad']) ?><?= (int) $d['aktif'] === 1 ? ' (aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grup">
                    <label class="form-etiket" for="sube">Şube</label>
                    <input class="form-girdi" id="sube" name="sube" maxlength="5"
                           value="<?= e((string) ($duzenlenen['sube'] ?? 'A')) ?>" required>
                </div>
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="ogretim_elemani_id">Öğretim elemanı</label>
                <select class="form-girdi" id="ogretim_elemani_id" name="ogretim_elemani_id" required>
                    <option value="">Hoca seçin…</option>
                    <?php foreach ($hocalar as $h): ?>
                        <option value="<?= e((string) $h['id']) ?>"
                            <?= (int) ($duzenlenen['ogretim_elemani_id'] ?? 0) === (int) $h['id'] ? 'selected' : '' ?>>
                            <?= e(trim($h['unvan'] . ' ' . $h['ad'] . ' ' . $h['soyad']) . ' · ' . ($h['bolum'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="kontenjan">Kontenjan (boş = sınırsız)</label>
                <input class="form-girdi" id="kontenjan" type="number" name="kontenjan" min="1" max="500"
                       value="<?= e((string) ($duzenlenen['kontenjan'] ?? '')) ?>">
            </div>

            <div class="form-eylem">
                <button class="dugme dugme-birincil" type="submit">
                    <?= $duzenlenen === null ? 'Açılış Ekle' : 'Değişiklikleri Kaydet' ?>
                </button>
            </div>
        </form>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Açılış Listesi</h3>
            <span class="rozet"><?= e((string) count($acilislar)) ?></span>
        </div>

        <?php if ($acilislar === []): ?>
            <div class="bos-durum">Bu filtreye uygun ders açılışı yok.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Ders</th>
                            <th class="gizle-mobil">Hoca</th>
                            <th class="metin-sag gizle-mobil">Doluluk</th>
                            <th class="metin-sag">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($acilislar as $a): ?>
                            <?php
                            $kontenjan = $a['kontenjan'] === null ? null : (int) $a['kontenjan'];
                            $kayit     = (int) $a['kayit_sayisi'];
                            $oran      = $kontenjan !== null && $kontenjan > 0
                                ? min(100, (int) round($kayit / $kontenjan * 100))
                                : null;
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($a['kod']) ?> · <?= e($a['sube']) ?></strong>
                                    <div class="metin-silik metin-kucuk"><?= e($a['ders_adi']) ?></div>
                                    <div class="metin-silik metin-kucuk"><?= e($a['donem']) ?></div>
                                </td>
                                <td class="metin-kucuk gizle-mobil"><?= e(trim((string) $a['hoca'])) ?></td>
                                <td class="metin-sag gizle-mobil">
                                    <div class="sayi"><?= e((string) $kayit) ?><?= $kontenjan !== null ? ' / ' . e((string) $kontenjan) : '' ?></div>
                                    <?php if ($oran !== null): ?>
                                        <div class="doluluk" style="margin-top:4px">
                                            <span style="width:<?= e((string) $oran) ?>%"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="metin-silik metin-kucuk"><?= e((string) $a['oturum_sayisi']) ?> oturum</div>
                                </td>
                                <td class="metin-sag">
                                    <div class="satir-eylem">
                                        <a class="dugme dugme-hayalet dugme-kucuk"
                                           href="<?= e(url('admin/atama.php?acilis=' . (int) $a['id'])) ?>">Atama</a>

                                        <a class="dugme dugme-hayalet dugme-kucuk"
                                           href="<?= e(url('admin/acilislar.php?duzenle=' . (int) $a['id'] . ($donemFiltre > 0 ? '&donem=' . $donemFiltre : ''))) ?>">Düzenle</a>

                                        <form method="post" action="<?= e(url('admin/acilislar.php' . ($donemFiltre > 0 ? '?donem=' . $donemFiltre : ''))) ?>"
                                              data-onay="Bu açılış ve bağlı kayıtlar silinecek. Emin misiniz?">
                                            <?= Auth::csrfAlani() ?>
                                            <input type="hidden" name="islem" value="sil">
                                            <input type="hidden" name="id" value="<?= e((string) $a['id']) ?>">
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
