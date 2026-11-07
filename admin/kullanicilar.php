<?php

declare(strict_types=1);

/**
 * Yönetici paneli - kullanıcı listesi.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$rol   = (string) ($_GET['rol'] ?? '');
$arama = trim((string) ($_GET['q'] ?? ''));

$don = static function (): void {
    $geri = [];

    if (!empty($_GET['rol'])) {
        $geri['rol'] = (string) $_GET['rol'];
    }

    if (!empty($_GET['q'])) {
        $geri['q'] = (string) $_GET['q'];
    }

    header('Location: ' . url('admin/kullanicilar.php' . ($geri === [] ? '' : '?' . http_build_query($geri))));
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
        if ($id === (int) Auth::kullanici()['id']) {
            Auth::flash('hata', 'Kendi hesabınızı silemezsiniz.');
            $don();
        }

        [$ok, $mesaj] = Yonetici::kullaniciSil($id);
        Auth::flash($ok ? 'basari' : 'hata', $mesaj);

        if ($ok) {
            Auth::log('Kullanıcı silindi', 'kullanicilar', $id);
        }

        $don();
    }

    if ($islem === 'durum' && $id > 0) {
        $k = Yonetici::kullanici($id);

        if ($k === null) {
            Auth::flash('hata', 'Kullanıcı bulunamadı.');
            $don();
        }

        if ((int) $k['aktif'] === 1 && $id === (int) Auth::kullanici()['id']) {
            Auth::flash('hata', 'Kendi hesabınızı pasife alamazsınız.');
            $don();
        }

        $yeni = (int) $k['aktif'] === 1 ? 0 : 1;
        $pdo  = Database::get();
        $pdo->prepare('UPDATE kullanicilar SET aktif = :aktif WHERE id = :id')
            ->execute(['aktif' => $yeni, 'id' => $id]);

        Auth::log($yeni === 1 ? 'Kullanıcı etkinleştirildi' : 'Kullanıcı pasife alındı', 'kullanicilar', $id);
        Auth::flash('basari', $yeni === 1 ? 'Kullanıcı etkinleştirildi.' : 'Kullanıcı pasife alındı.');

        $don();
    }

    Auth::flash('hata', 'Bilinmeyen işlem.');
    $don();
}

$kullanicilar = Yonetici::kullanicilar($rol === '' ? null : $rol, $arama);
$sayilar = [
    'toplam'  => count($kullanicilar),
    'aktif'   => count(array_filter($kullanicilar, static fn (array $k): bool => (int) $k['aktif'] === 1)),
    'ogrenci' => count(array_filter($kullanicilar, static fn (array $k): bool => $k['rol'] === 'ogrenci')),
    'hoca'    => count(array_filter($kullanicilar, static fn (array $k): bool => $k['rol'] === 'ogretim_elemani')),
];

$sayfaBaslik = 'Kullanıcılar';
$aktifMenu   = 'panel';
$panelAktif  = 'kullanicilar';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Kullanıcılar</h1>
        <p><?= e((string) $sayilar['toplam']) ?> kayıt · <?= e((string) $sayilar['aktif']) ?> aktif · <?= e((string) $sayilar['ogrenci']) ?> öğrenci · <?= e((string) $sayilar['hoca']) ?> hoca</p>
    </div>
    <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('admin/kullanici.php')) ?>">Yeni Kullanıcı</a>
</div>

<form class="filtre-cubuk" method="get" action="<?= e(url('admin/kullanicilar.php')) ?>">
    <div class="filtre-alan">
        <label class="form-etiket" for="q">Ara</label>
        <input class="form-girdi" id="q" name="q" value="<?= e($arama) ?>" placeholder="Ad, numara veya e-posta">
    </div>
    <div class="filtre-alan">
        <label class="form-etiket" for="rol">Rol</label>
        <select class="form-girdi" id="rol" name="rol">
            <option value="">Tümü</option>
            <?php foreach (Yonetici::roller() as $r): ?>
                <option value="<?= e($r['ad']) ?>" <?= $rol === $r['ad'] ? 'selected' : '' ?>>
                    <?= e(Auth::rolEtiketi($r['ad'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtre-eylem">
        <button class="dugme dugme-birincil dugme-kucuk" type="submit">Filtrele</button>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/kullanicilar.php')) ?>">Sıfırla</a>
    </div>
</form>

<?php if ($kullanicilar === []): ?>
    <div class="kart bos-durum">Bu filtreye uygun kullanıcı bulunamadı.</div>
<?php else: ?>
    <section class="kart">
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Rol</th>
                        <th class="gizle-mobil">Bölüm / Sınıf</th>
                        <th class="gizle-mobil">E-posta</th>
                        <th>Durum</th>
                        <th class="metin-sag">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kullanicilar as $k): ?>
                        <tr>
                            <td>
                                <strong><?= e($k['ad'] . ' ' . $k['soyad']) ?></strong>
                                <div class="metin-silik metin-kucuk"><?= e($k['numara']) ?></div>
                            </td>
                            <td>
                                <span class="rozet <?= $k['rol'] === 'yonetici' ? 'rozet-vurgu' : ($k['rol'] === 'ogretim_elemani' ? 'rozet-basari' : '') ?>">
                                    <?= e(Auth::rolEtiketi($k['rol'])) ?>
                                </span>
                            </td>
                            <td class="metin-kucuk gizle-mobil">
                                <?= e($k['bolum'] ?? '—') ?>
                                <?php if (!empty($k['sinif'])): ?>
                                    <div class="metin-silik"><?= e((string) $k['sinif']) ?>. sınıf</div>
                                <?php elseif (!empty($k['unvan'])): ?>
                                    <div class="metin-silik"><?= e($k['unvan']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="metin-kucuk gizle-mobil"><?= e($k['eposta']) ?></td>
                            <td>
                                <?php if ((int) $k['aktif'] === 1): ?>
                                    <span class="rozet rozet-basari">Aktif</span>
                                <?php else: ?>
                                    <span class="rozet rozet-hata">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td class="metin-sag">
                                <div class="satir-eylem">
                                    <a class="dugme dugme-hayalet dugme-kucuk"
                                       href="<?= e(url('admin/kullanici.php?id=' . (int) $k['id'])) ?>">Düzenle</a>

                                    <form method="post" action="<?= e(url('admin/kullanicilar.php')) ?>">
                                        <?= Auth::csrfAlani() ?>
                                        <input type="hidden" name="islem" value="durum">
                                        <input type="hidden" name="id" value="<?= e((string) $k['id']) ?>">
                                        <button class="dugme dugme-hayalet dugme-kucuk" type="submit">
                                            <?= (int) $k['aktif'] === 1 ? 'Pasifleştir' : 'Etkinleştir' ?>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= e(url('admin/kullanicilar.php')) ?>"
                                          onsubmit="return confirm('Bu kullanıcı silinecek. Emin misiniz?');">
                                        <?= Auth::csrfAlani() ?>
                                        <input type="hidden" name="islem" value="sil">
                                        <input type="hidden" name="id" value="<?= e((string) $k['id']) ?>">
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
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
