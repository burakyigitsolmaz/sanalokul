<?php

declare(strict_types=1);

/**
 * Yönetici paneli - bölüm yönetimi.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$don = static function (): void {
    header('Location: ' . url('admin/bolumler.php'));
    exit;
};

$duzenleId = (int) ($_GET['duzenle'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don();
    }

    $islem = (string) ($_POST['islem'] ?? '');
    $id    = (int) ($_POST['id'] ?? 0);
    $kod   = mb_strtoupper(trim((string) ($_POST['kod'] ?? '')), 'UTF-8');
    $ad    = trim((string) ($_POST['ad'] ?? ''));

    if ($islem === 'sil' && $id > 0) {
        [$ok, $mesaj] = Yonetici::bolumSil($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Bölüm silindi #' . $id, 'bolumler', $id);
        }

        $don();
    }

    if (!in_array($islem, ['ekle', 'guncelle'], true)) {
        Auth::flash('hata', 'Bilinmeyen işlem.');
        $don();
    }

    if ($kod === '' || mb_strlen($kod, 'UTF-8') > 12) {
        Auth::flash('hata', 'Bölüm kodu zorunludur ve en fazla 12 karakter olabilir.');
        $don();
    }

    if (mb_strlen($ad, 'UTF-8') < 3) {
        Auth::flash('hata', 'Bölüm adı en az 3 karakter olmalıdır.');
        $don();
    }

    if ($islem === 'guncelle' && $id > 0) {
        [$ok, $mesaj] = Yonetici::bolumGuncelle($id, $kod, $ad);

        if ($ok) {
            Auth::log('Bölüm güncellendi: ' . $kod, 'bolumler', $id);
        }
    } else {
        [$ok, $mesaj] = Yonetici::bolumEkle($kod, $ad);
    }

    Auth::flash($ok ? 'basari' : 'hata', $mesaj);
    $don();
}

$bolumler = Yonetici::bolumler();

$duzenlenen = null;
foreach ($bolumler as $b) {
    if ((int) $b['id'] === $duzenleId) {
        $duzenlenen = $b;
        break;
    }
}

$sayfaBaslik = 'Bölümler';
$aktifMenu   = 'panel';
$panelAktif  = 'bolumler';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Bölümler</h1>
        <p><?= e((string) count($bolumler)) ?> bölüm · ders, öğrenci ve hoca dağılımı</p>
    </div>
</div>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3><?= $duzenlenen === null ? 'Yeni Bölüm' : 'Bölümü Düzenle' ?></h3>
            <?php if ($duzenlenen !== null): ?>
                <a class="baglanti metin-kucuk" href="<?= e(url('admin/bolumler.php')) ?>">Vazgeç</a>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('admin/bolumler.php')) ?>">
            <?= Auth::csrfAlani() ?>
            <input type="hidden" name="islem" value="<?= $duzenlenen === null ? 'ekle' : 'guncelle' ?>">
            <?php if ($duzenlenen !== null): ?>
                <input type="hidden" name="id" value="<?= e((string) $duzenlenen['id']) ?>">
            <?php endif; ?>

            <div class="form-grup">
                <label class="form-etiket" for="kod">Bölüm kodu</label>
                <input class="form-girdi" id="kod" name="kod" value="<?= e((string) ($duzenlenen['kod'] ?? '')) ?>"
                       required maxlength="12" placeholder="Örn. NBP">
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="ad">Bölüm adı</label>
                <input class="form-girdi" id="ad" name="ad" value="<?= e((string) ($duzenlenen['ad'] ?? '')) ?>"
                       required maxlength="120" placeholder="Bilgisayar Programcılığı">
            </div>

            <div class="form-eylem">
                <button class="dugme dugme-birincil" type="submit">
                    <?= $duzenlenen === null ? 'Bölüm Ekle' : 'Değişiklikleri Kaydet' ?>
                </button>
            </div>
        </form>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Bölüm Listesi</h3>
            <span class="rozet"><?= e((string) count($bolumler)) ?></span>
        </div>

        <?php if ($bolumler === []): ?>
            <div class="bos-durum">Henüz bölüm eklenmemiş.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Kod</th>
                            <th>Bölüm</th>
                            <th class="metin-sag gizle-mobil">Ders</th>
                            <th class="metin-sag gizle-mobil">Öğrenci</th>
                            <th class="metin-sag">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bolumler as $b): ?>
                            <tr>
                                <td><strong><?= e($b['kod']) ?></strong></td>
                                <td>
                                    <?= e($b['ad']) ?>
                                    <div class="metin-silik metin-kucuk"><?= e((string) $b['hoca_sayisi']) ?> öğretim elemanı</div>
                                </td>
                                <td class="metin-sag sayi gizle-mobil"><?= e((string) $b['ders_sayisi']) ?></td>
                                <td class="metin-sag sayi gizle-mobil"><?= e((string) $b['ogrenci_sayisi']) ?></td>
                                <td class="metin-sag">
                                    <div class="satir-eylem">
                                        <a class="dugme dugme-hayalet dugme-kucuk"
                                           href="<?= e(url('admin/bolumler.php?duzenle=' . (int) $b['id'])) ?>">Düzenle</a>

                                        <form method="post" action="<?= e(url('admin/bolumler.php')) ?>"
                                              onsubmit="return confirm('Bu bölüm silinecek. Bağlı ders ve profiller varsa işlem başarısız olur. Emin misiniz?');">
                                            <?= Auth::csrfAlani() ?>
                                            <input type="hidden" name="islem" value="sil">
                                            <input type="hidden" name="id" value="<?= e((string) $b['id']) ?>">
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
