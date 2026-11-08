<?php

declare(strict_types=1);

/**
 * Yönetici paneli - kullanıcı ekle / düzenle.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$id      = (int) ($_GET['id'] ?? 0);
$mevcut  = $id > 0 ? Yonetici::kullanici($id) : null;
$bolumler = Yonetici::bolumler();

if ($id > 0 && $mevcut === null) {
    http_response_code(404);
}

$don = static function (int $kullaniciId = 0): void {
    header('Location: ' . url('admin/kullanici.php' . ($kullaniciId > 0 ? '?id=' . $kullaniciId : '')));
    exit;
};

$form = [
    'rol'       => (string) ($mevcut['rol'] ?? 'ogrenci'),
    'numara'    => (string) ($mevcut['numara'] ?? ''),
    'ad'        => (string) ($mevcut['ad'] ?? ''),
    'soyad'     => (string) ($mevcut['soyad'] ?? ''),
    'eposta'    => (string) ($mevcut['eposta'] ?? ''),
    'telefon'   => (string) ($mevcut['telefon'] ?? ''),
    'aktif'     => $mevcut === null ? 1 : (int) $mevcut['aktif'],
    'bolum_id'  => (string) ($mevcut['bolum_id'] ?? ''),
    'sinif'     => (string) ($mevcut['sinif'] ?? '1'),
    'giris_yili' => (string) ($mevcut['giris_yili'] ?? date('Y')),
    'unvan'     => (string) ($mevcut['unvan'] ?? 'Öğretim Görevlisi'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        $don($id);
    }

    foreach (array_keys($form) as $alan) {
        if (isset($_POST[$alan])) {
            $form[$alan] = trim((string) $_POST[$alan]);
        }
    }

    $form['aktif'] = !empty($_POST['aktif']) ? 1 : 0;

    $hatalar = [];

    if (!in_array($form['rol'], ['ogrenci', 'ogretim_elemani', 'yonetici'], true)) {
        $hatalar[] = 'Geçersiz rol.';
    }

    if ($form['numara'] === '') {
        $hatalar[] = 'Numara zorunludur.';
    }

    if (mb_strlen($form['ad'], 'UTF-8') < 2 || mb_strlen($form['soyad'], 'UTF-8') < 2) {
        $hatalar[] = 'Ad ve soyad en az 2 karakter olmalıdır.';
    }

    if (!filter_var($form['eposta'], FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = 'Geçerli bir e-posta girin.';
    }

    $sifre = (string) ($_POST['sifre'] ?? '');

    if ($mevcut === null && mb_strlen($sifre, 'UTF-8') < 8) {
        $hatalar[] = 'Yeni kullanıcı için en az 8 karakterlik şifre gerekli.';
    }

    if ($sifre !== '' && mb_strlen($sifre, 'UTF-8') < 8) {
        $hatalar[] = 'Şifre en az 8 karakter olmalıdır.';
    }

    if (in_array($form['rol'], ['ogrenci', 'ogretim_elemani'], true) && (int) $form['bolum_id'] <= 0) {
        $hatalar[] = 'Bölüm seçilmelidir.';
    }

    if ($form['rol'] === 'ogrenci' && !in_array($form['sinif'], ['1', '2'], true)) {
        $hatalar[] = 'Sınıf 1 veya 2 olmalıdır.';
    }

    if ($form['rol'] === 'ogretim_elemani' && $form['unvan'] === '') {
        $hatalar[] = 'Unvan zorunludur.';
    }

    if ($hatalar !== []) {
        Auth::flash('hata', implode(' ', $hatalar));
        $don($id);
    }

    $veri = [
        'rol'        => $form['rol'],
        'numara'     => $form['numara'],
        'ad'         => $form['ad'],
        'soyad'      => $form['soyad'],
        'eposta'     => $form['eposta'],
        'telefon'    => $form['telefon'],
        'aktif'      => $form['aktif'],
        'bolum_id'   => (int) $form['bolum_id'],
        'sinif'      => (int) $form['sinif'],
        'giris_yili' => (int) $form['giris_yili'],
        'unvan'      => $form['unvan'],
        'sifre'      => $sifre,
    ];

    if ($mevcut === null) {
        [$ok, $yeniId, $mesaj] = Yonetici::kullaniciEkle($veri);

        if (!$ok) {
            Auth::flash('hata', $mesaj);
            $don();
        }

        Auth::log('Kullanıcı oluşturuldu: ' . $form['ad'] . ' ' . $form['soyad'], 'kullanicilar', $yeniId);
        Auth::flash('basari', 'Kullanıcı oluşturuldu.');
        $don($yeniId);
    }

    [$ok, $mesaj] = Yonetici::kullaniciGuncelle($id, $veri);

    if (!$ok) {
        Auth::flash('hata', $mesaj);
        $don($id);
    }

    Auth::log('Kullanıcı güncellendi: ' . $form['ad'] . ' ' . $form['soyad'], 'kullanicilar', $id);
    Auth::flash('basari', 'Kullanıcı güncellendi.');
    $don($id);
}

$sayfaBaslik = $mevcut === null ? 'Yeni Kullanıcı' : 'Kullanıcı Düzenle';
$aktifMenu   = 'panel';
$panelAktif  = 'kullanicilar';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<nav class="kirinti" aria-label="Sayfa yolu">
    <a href="<?= e(url('admin/')) ?>">Panelim</a>
    <span>/</span>
    <a href="<?= e(url('admin/kullanicilar.php')) ?>">Kullanıcılar</a>
    <span>/</span>
    <span class="simdiki"><?= $mevcut === null ? 'Yeni' : e($mevcut['ad'] . ' ' . $mevcut['soyad']) ?></span>
</nav>

<div class="sayfa-basligi">
    <div>
        <h1><?= $mevcut === null ? 'Yeni Kullanıcı' : 'Kullanıcı Düzenle' ?></h1>
        <p><?= $mevcut === null ? 'Öğrenci, öğretim elemanı veya yönetici hesabı oluştur.' : e($mevcut['numara'] . ' · ' . $mevcut['eposta']) ?></p>
    </div>
    <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/kullanicilar.php')) ?>">Listeye Dön</a>
</div>

<?php if ($id > 0 && $mevcut === null): ?>
    <div class="kart bos-durum">Kullanıcı bulunamadı.</div>
<?php else: ?>
    <form class="kart" method="post" action="<?= e(url('admin/kullanici.php' . ($id > 0 ? '?id=' . $id : ''))) ?>">
        <?= Auth::csrfAlani() ?>

        <div class="form-izgara">
            <div class="form-grup">
                <label class="form-etiket" for="rol">Rol</label>
                <select class="form-girdi" id="rol" name="rol" required data-rol-secim>
                    <option value="ogrenci" <?= $form['rol'] === 'ogrenci' ? 'selected' : '' ?>>Öğrenci</option>
                    <option value="ogretim_elemani" <?= $form['rol'] === 'ogretim_elemani' ? 'selected' : '' ?>>Öğretim Elemanı</option>
                    <option value="yonetici" <?= $form['rol'] === 'yonetici' ? 'selected' : '' ?>>Yönetici</option>
                </select>
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="numara">Numara / Sicil</label>
                <input class="form-girdi" id="numara" name="numara" value="<?= e($form['numara']) ?>" required maxlength="20">
            </div>
        </div>

        <div class="form-izgara">
            <div class="form-grup">
                <label class="form-etiket" for="ad">Ad</label>
                <input class="form-girdi" id="ad" name="ad" value="<?= e($form['ad']) ?>" required maxlength="60">
            </div>
            <div class="form-grup">
                <label class="form-etiket" for="soyad">Soyad</label>
                <input class="form-girdi" id="soyad" name="soyad" value="<?= e($form['soyad']) ?>" required maxlength="60">
            </div>
        </div>

        <div class="form-izgara">
            <div class="form-grup">
                <label class="form-etiket" for="eposta">E-posta</label>
                <input class="form-girdi" id="eposta" type="email" name="eposta" value="<?= e($form['eposta']) ?>" required maxlength="120">
            </div>
            <div class="form-grup">
                <label class="form-etiket" for="telefon">Telefon (isteğe bağlı)</label>
                <input class="form-girdi" id="telefon" name="telefon" value="<?= e($form['telefon']) ?>" maxlength="20">
            </div>
        </div>

        <div class="form-izgara">
            <div class="form-grup">
                <label class="form-etiket" for="sifre">
                    Şifre <?= $mevcut === null ? '' : '(boş bırakılırsa değişmez)' ?>
                </label>
                <input class="form-girdi" id="sifre" type="password" name="sifre" <?= $mevcut === null ? 'required' : '' ?> minlength="8" autocomplete="new-password">
                <p class="form-ipucu">En az 8 karakter.</p>
            </div>
            <div class="form-grup">
                <label class="form-etiket">Durum</label>
                <label class="onay-kutu">
                    <input type="checkbox" name="aktif" value="1" <?= (int) $form['aktif'] === 1 ? 'checked' : '' ?>>
                    <span>Hesap aktif</span>
                </label>
            </div>
        </div>

        <div data-rol-grup="ogrenci" <?= $form['rol'] === 'ogrenci' ? '' : 'hidden' ?>>
            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="bolum_id">Bölüm</label>
                    <select class="form-girdi" id="bolum_id" name="bolum_id">
                        <option value="">Bölüm seçin…</option>
                        <?php foreach ($bolumler as $b): ?>
                            <option value="<?= e((string) $b['id']) ?>" <?= (string) $form['bolum_id'] === (string) $b['id'] ? 'selected' : '' ?>>
                                <?= e($b['kod'] . ' · ' . $b['ad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grup">
                    <label class="form-etiket" for="sinif">Sınıf</label>
                    <select class="form-girdi" id="sinif" name="sinif">
                        <option value="1" <?= $form['sinif'] === '1' ? 'selected' : '' ?>>1. sınıf</option>
                        <option value="2" <?= $form['sinif'] === '2' ? 'selected' : '' ?>>2. sınıf</option>
                    </select>
                </div>
            </div>
            <div class="form-grup">
                <label class="form-etiket" for="giris_yili">Giriş yılı</label>
                <input class="form-girdi" id="giris_yili" type="number" name="giris_yili" min="2000" max="2100" value="<?= e($form['giris_yili']) ?>">
            </div>
        </div>

        <div data-rol-grup="ogretim_elemani" <?= $form['rol'] === 'ogretim_elemani' ? '' : 'hidden' ?>>
            <div class="form-izgara">
                <div class="form-grup">
                    <label class="form-etiket" for="bolum_hoca">Bölüm</label>
                    <select class="form-girdi" id="bolum_hoca" name="bolum_id_hoca" data-hoca-bolum>
                        <option value="">Bölüm seçin…</option>
                        <?php foreach ($bolumler as $b): ?>
                            <option value="<?= e((string) $b['id']) ?>" <?= (string) $form['bolum_id'] === (string) $b['id'] ? 'selected' : '' ?>>
                                <?= e($b['kod'] . ' · ' . $b['ad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grup">
                    <label class="form-etiket" for="unvan">Unvan</label>
                    <input class="form-girdi" id="unvan" name="unvan" value="<?= e($form['unvan']) ?>" maxlength="60">
                </div>
            </div>
        </div>

        <div class="form-eylem">
            <button class="dugme dugme-birincil" type="submit">
                <?= $mevcut === null ? 'Kullanıcıyı Oluştur' : 'Değişiklikleri Kaydet' ?>
            </button>
            <a class="dugme dugme-hayalet" href="<?= e(url('admin/kullanicilar.php')) ?>">Vazgeç</a>
        </div>
    </form>
<?php endif; ?>

<script nonce="<?= e(Guvenlik::nonce()) ?>">
(function () {
    var rol = document.querySelector('[data-rol-secim]');
    if (!rol) { return; }

    var gruplar = document.querySelectorAll('[data-rol-grup]');

    function guncelle() {
        for (var i = 0; i < gruplar.length; i++) {
            gruplar[i].hidden = gruplar[i].getAttribute('data-rol-grup') !== rol.value;
        }
    }

    rol.addEventListener('change', guncelle);
    guncelle();

    // İki bölüm seçicisini senkron tut (öğrenci ve hoca alanları ayrı isim kullanıyor)
    var ogrBolum = document.getElementById('bolum_id');
    var hocaBolum = document.getElementById('bolum_hoca');

    if (ogrBolum && hocaBolum) {
        ogrBolum.addEventListener('change', function () { hocaBolum.value = ogrBolum.value; });
        hocaBolum.addEventListener('change', function () { ogrBolum.value = hocaBolum.value; });
    }
})();
</script>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
