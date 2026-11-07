<?php

declare(strict_types=1);

/**
 * Yönetici paneli - derse öğrenci atama.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$acilisId = (int) ($_GET['acilis'] ?? 0);
$arama    = trim((string) ($_GET['q'] ?? ''));

$acilis = Yonetici::atamaAcilisi($acilisId);

if ($acilis === null) {
    http_response_code(404);
}

$don = static function () use ($acilisId): void {
    header('Location: ' . url('admin/atama.php?acilis=' . $acilisId));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    $islem      = (string) ($_POST['islem'] ?? '');
    $ogrenciId  = (int) ($_POST['ogrenci_id'] ?? 0);
    $kayitId    = (int) ($_POST['kayit_id'] ?? 0);

    if ($islem === 'kaydet' && $ogrenciId > 0 && $acilis !== null) {
        [$ok, $mesaj] = Yonetici::derseKaydet($ogrenciId, $acilisId);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Öğrenci derse kaydedildi #' . $ogrenciId, 'ders_kayitlari', 0);
        }

        $don();
    }

    if ($islem === 'sil' && $kayitId > 0) {
        [$ok, $mesaj] = Yonetici::kayitSil($kayitId);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Ders kaydı silindi #' . $kayitId, 'ders_kayitlari', $kayitId);
        }

        $don();
    }

    Auth::flash('hata', 'Bilinmeyen işlem.');
    $don();
}

$kayitlar = Yonetici::atamaKayitlari($acilisId);
$adaylar  = Yonetici::atamaAdaylari($acilisId, $arama);

$sayfaBaslik = 'Derse Atama';
$aktifMenu   = 'panel';
$panelAktif  = 'atama';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1><?= e($acilis['kod'] . ' · ' . $acilis['sube']) ?></h1>
        <p><?= e($acilis['ders_adi']) ?> · <?= e($acilis['donem']) ?> · Hoca: <?= e(trim((string) $acilis['hoca'])) ?></p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/acilislar.php')) ?>">Açılışlara Dön</a>
</div>

<?php if ($acilis === null): ?>
    <div class="kart bos-durum">Ders açılışı bulunamadı.</div>
    <?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
    <?php return; ?>
<?php endif; ?>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Kayıtlı Öğrenciler</h3>
            <span class="rozet"><?= e((string) count($kayitlar)) ?></span>
        </div>

        <?php if ($kayitlar === []): ?>
            <div class="bos-durum">Henüz kayıtlı öğrenci yok.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Öğrenci</th>
                            <th class="gizle-mobil">No</th>
                            <th class="metin-sag gizle-mobil">Kayıt</th>
                            <th class="metin-sag">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kayitlar as $k): ?>
                            <tr>
                                <td><strong><?= e($k['ad'] . ' ' . $k['soyad']) ?></strong></td>
                                <td class="metin-kucuk gizle-mobil"><?= e($k['numara']) ?></td>
                                <td class="metin-sag sayi gizle-mobil">
                                    <?= e(date('d.m.Y H:i', strtotime((string) $k['kayit_zamani']))) ?>
                                </td>
                                <td class="metin-sag">
                                    <div class="satir-eylem">
                                        <form method="post" action="<?= e(url('admin/atama.php?acilis=' . $acilisId)) ?>"
                                              onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                            <?= Auth::csrfAlani() ?>
                                            <input type="hidden" name="islem" value="sil">
                                            <input type="hidden" name="kayit_id" value="<?= e((string) $k['kayit_id']) ?>">
                                            <button class="dugme dugme-tehlike dugme-kucuk" type="submit">Kaldır</button>
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

    <section class="kart">
        <div class="kart-baslik">
            <h3>Öğrenci Ekle</h3>
            <span class="rozet"><?= e((string) count($adaylar)) ?> aday</span>
        </div>

        <?php if ($adaylar === []): ?>
            <div class="bos-durum">Tüm aktif öğrenciler zaten kayıtlı.</div>
        <?php else: ?>
            <form class="filtre-cubuk" method="get" action="<?= e(url('admin/atama.php?acilis=' . $acilisId)) ?>">
                <div class="filtre-alan">
                    <label class="form-etiket" for="q">Ara</label>
                    <input class="form-girdi" id="q" name="q" value="<?= e($arama) ?>"
                           placeholder="Ad, soyad veya numara">
                </div>
                <div class="filtre-eylem">
                    <button class="dugme dugme-birincil dugme-kucuk" type="submit">Ara</button>
                </div>
            </form>

            <div class="satir-liste">
                <?php foreach ($adaylar as $a): ?>
                    <div class="satir">
                        <div class="satir-icerik">
                            <strong><?= e($a['ad'] . ' ' . $a['soyad']) ?></strong>
                            <span><?= e($a['numara']) ?> · <?= e((string) $a['sinif']) ?>. sınıf</span>
                        </div>
                        <div class="satir-sag">
                            <form method="post" action="<?= e(url('admin/atama.php?acilis=' . $acilisId)) ?>">
                                <?= Auth::csrfAlani() ?>
                                <input type="hidden" name="islem" value="kaydet">
                                <input type="hidden" name="ogrenci_id" value="<?= e((string) $a['id']) ?>">
                                <button class="dugme dugme-birincil dugme-kucuk" type="submit">Ekle</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
