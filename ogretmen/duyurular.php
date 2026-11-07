<?php

declare(strict_types=1);

/**
 * Öğretim elemanı paneli - ders duyuruları.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['ogretim_elemani']);

$kullanici = Auth::kullanici();
$hocaId    = (int) $kullanici['id'];
$dersler   = Ogretmen::dersler($hocaId);

$don = static function (): void {
    header('Location: ' . url('ogretmen/duyurular.php'));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    $islem = (string) ($_POST['islem'] ?? '');

    if ($islem === 'duyuru_ekle') {
        $acilisId = (int) ($_POST['ders_acilis_id'] ?? 0);
        $baslik   = trim((string) ($_POST['baslik'] ?? ''));
        $icerik   = trim((string) ($_POST['icerik'] ?? ''));
        $onemli   = !empty($_POST['onemli']);
        $bitisHam = trim((string) ($_POST['bitis_tarihi'] ?? ''));

        if ($acilisId <= 0 || $baslik === '' || $icerik === '') {
            Auth::flash('hata', 'Ders, başlık ve içerik zorunludur.');
            $don();
        }

        $bitis = null;

        if ($bitisHam !== '') {
            $bitisZaman = strtotime($bitisHam);

            if ($bitisZaman === false) {
                Auth::flash('hata', 'Geçerlilik tarihi anlaşılamadı.');
                $don();
            }

            $bitis = date('Y-m-d 23:59:59', $bitisZaman);
        }

        if (!Ogretmen::duyuruEkle($hocaId, $acilisId, $baslik, $icerik, $onemli, $bitis)) {
            Auth::flash('hata', 'Duyuru eklenemedi. Ders size ait olmayabilir.');
            $don();
        }

        Auth::log('Ders duyurusu yayınlandı: ' . $baslik, 'duyurular');
        Auth::flash('basari', 'Duyuru yayınlandı.');
        $don();
    }

    if ($islem === 'duyuru_sil') {
        $duyuruId = (int) ($_POST['duyuru_id'] ?? 0);

        if (!Ogretmen::duyuruSil($hocaId, $duyuruId)) {
            Auth::flash('hata', 'Duyuru bulunamadı veya size ait değil.');
            $don();
        }

        Auth::log('Duyuru silindi', 'duyurular', $duyuruId);
        Auth::flash('basari', 'Duyuru silindi.');
        $don();
    }

    Auth::flash('hata', 'Bilinmeyen işlem.');
    $don();
}

$duyurular = Ogretmen::duyurular($hocaId);
$onemliler = array_values(array_filter($duyurular, static fn (array $d): bool => (int) $d['onemli'] === 1));

$sayfaBaslik = 'Duyurular';
$aktifMenu   = 'panel';
$panelAktif  = 'duyurular';

require dirname(__DIR__) . '/templates/ogretmen_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Duyurular</h1>
        <p><?= e((string) count($duyurular)) ?> duyuru · <?= e((string) count($onemliler)) ?> önemli</p>
    </div>
</div>

<?php if ($dersler === []): ?>
    <div class="kart bos-durum">
        
        Duyuru yayınlamak için önce size tanımlı bir ders olmalı.
    </div>
<?php else: ?>
    <section class="kart" style="margin-bottom:20px">
        <div class="kart-baslik"><h3>Yeni Duyuru</h3></div>

        <form method="post" action="<?= e(url('ogretmen/duyurular.php')) ?>">
            <?= Auth::csrfAlani() ?>
            <input type="hidden" name="islem" value="duyuru_ekle">

            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="duyuru-ders">Ders</label>
                    <select class="form-girdi" id="duyuru-ders" name="ders_acilis_id" required>
                        <option value="">Ders seçin…</option>
                        <?php foreach ($dersler as $ders): ?>
                            <option value="<?= e((string) $ders['acilis_id']) ?>">
                                <?= e($ders['kod']) ?> · <?= e($ders['ad']) ?> (<?= e($ders['donem']) ?> / <?= e($ders['sube']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grup">
                    <label class="form-etiket" for="duyuru-bitis">Geçerlilik tarihi (isteğe bağlı)</label>
                    <input class="form-girdi" id="duyuru-bitis" type="date" name="bitis_tarihi">
                </div>
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="duyuru-baslik">Başlık</label>
                <input class="form-girdi" id="duyuru-baslik" name="baslik" required maxlength="160">
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="duyuru-icerik">İçerik</label>
                <textarea class="form-girdi" id="duyuru-icerik" name="icerik" rows="4" required></textarea>
            </div>

            <div class="form-grup">
                <label class="onay-kutu">
                    <input type="checkbox" name="onemli" value="1">
                    <span>Önemli duyuru olarak işaretle</span>
                </label>
            </div>

            <button class="dugme dugme-birincil" type="submit">Yayınla</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($duyurular === []): ?>
    <div class="kart bos-durum">Henüz duyuru yayınlamadınız.</div>
<?php else: ?>
    <div class="bolum-basligi">
        <h2 style="font-size:16px">Yayınlanan Duyurular</h2>
        <span class="rozet"><?= e((string) count($duyurular)) ?></span>
    </div>

    <div class="duyuru-listesi">
        <?php foreach ($duyurular as $duyuru): ?>
            <details class="kart duyuru-kart" <?= (int) $duyuru['onemli'] === 1 ? 'open' : '' ?>>
                <summary>
                    
                    <span class="duyuru-baslik">
                        <strong><?= e($duyuru['baslik']) ?></strong>
                        <span class="metin-kucuk metin-silik">
                            <?= e($duyuru['kod']) ?> · <?= e($duyuru['ders_adi']) ?>
                            · <?= e(date('d.m.Y H:i', strtotime((string) $duyuru['yayin_tarihi']))) ?>
                            <?php if (!empty($duyuru['bitis_tarihi'])): ?>
                                · <?= e(tarih_tr((string) $duyuru['bitis_tarihi'])) ?>'e kadar
                            <?php endif; ?>
                        </span>
                    </span>
                    <?php if ((int) $duyuru['onemli'] === 1): ?>
                        <span class="rozet rozet-uyari">Önemli</span>
                    <?php endif; ?>
                </summary>

                <div class="duyuru-govde">
                    <p><?= nl2br(e($duyuru['icerik'])) ?></p>
                    <div class="kart-eylem">
                        <a class="dugme dugme-hayalet dugme-kucuk"
                           href="<?= e(url('ogretmen/ders.php?id=' . (int) $duyuru['ders_acilis_id'] . '&sekme=duyurular')) ?>">
                            Ders Duyuruları
                        </a>
                        <form method="post" action="<?= e(url('ogretmen/duyurular.php')) ?>"
                              onsubmit="return confirm('Duyuru silinsin mi?');">
                            <?= Auth::csrfAlani() ?>
                            <input type="hidden" name="islem" value="duyuru_sil">
                            <input type="hidden" name="duyuru_id" value="<?= e((string) $duyuru['id']) ?>">
                            <button class="dugme dugme-tehlike dugme-kucuk" type="submit">Sil</button>
                        </form>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/ogretmen_son.php'; ?>
