<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

if (Auth::girisYapmisMi()) {
    header('Location: ' . Auth::panelYolu());
    exit;
}

$hatalar = [];
$eposta  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        $hatalar[] = 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.';
    } else {
        $eposta = trim((string) ($_POST['eposta'] ?? ''));
        $sifre  = (string) ($_POST['sifre'] ?? '');

        $sonuc = Auth::girisYap($eposta, $sifre);

        if ($sonuc['ok'] && $sonuc['kullanici'] !== null) {
            Auth::flash('basari', 'Hoş geldiniz, ' . $sonuc['kullanici']['ad_soyad'] . '.');
            header('Location: ' . Auth::panelYolu());
            exit;
        }

        $hatalar[] = $sonuc['mesaj'];
    }
}

$sayfaBaslik = 'Giriş Yap';
$govdeSinifi = 'giris-govde';

require __DIR__ . '/templates/head.php';
?>
<div class="giris-sayfa">
    <aside class="giris-gorsel" aria-hidden="true">
        <img src="<?= e(url('assets/img/kampus.jpg')) ?>" alt="">
        <div class="giris-gorsel-katman"></div>
        <div class="giris-gorsel-icerik">
            <div></div>
            <div>
                <span class="hero-etiket">Ders Projesi · 2026</span>
                <h2>Derslerin, notların ve duyuruların tek yerde.</h2>
                <p>
                    Nallıhan Meslek Yüksekokulu Bilgisayar Programcılığı öğrencileri için hazırlanmış
                    sanal okul uygulaması.
                </p>
            </div>
        </div>
    </aside>

    <section class="giris-form-taraf">
        <div class="giris-kutu">
            <div class="marka" style="margin-bottom:22px">
                <span class="marka-logo">
                    <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Nallıhan MYO">
                </span>
                <span class="marka-metin">
                    <strong><?= e(config('uygulama.kisa_ad')) ?></strong>
                    <span>Öğrenci Bilgi Sistemi</span>
                </span>
                <button class="tema-dugme" id="temaDugme" type="button"
                        title="Tema değiştir" aria-label="Tema değiştir"
                        style="margin-left:auto">◐</button>
            </div>

            <h1>Tekrar hoş geldiniz</h1>
            <p class="alt-metin">Devam etmek için okul e-posta adresinizle giriş yapın.</p>

            <?php foreach ($hatalar as $hata): ?>
                <div class="uyari uyari-hata" role="alert">
                    <span class="uyari-ikon">✕</span>
                    <span><?= e($hata) ?></span>
                </div>
            <?php endforeach; ?>

            <form method="post" action="<?= e(url('giris.php')) ?>" data-tek-gonderim autocomplete="on">
                <?= Auth::csrfAlani() ?>

                <div class="form-grup">
                    <label class="form-etiket" for="eposta">E-posta</label>
                    <input class="form-girdi" type="email" id="eposta" name="eposta"
                           value="<?= e($eposta) ?>" placeholder="ornek@ornek.local"
                           required autofocus autocomplete="username">
                </div>

                <div class="form-grup">
                    <label class="form-etiket" for="sifre">Şifre</label>
                    <input class="form-girdi" type="password" id="sifre" name="sifre"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div class="form-satir">
                    <label class="onay-kutu">
                        <input type="checkbox" name="hatirla" value="1">
                        Beni hatırla
                    </label>
                    <span class="metin-kucuk metin-silik">Şifremi unuttum (demo)</span>
                </div>

                <button class="dugme dugme-birincil dugme-tam" type="submit">Giriş Yap</button>
            </form>

            <div class="demo-kutu">
                <strong>Demo hesaplar</strong>
                Tüm hesapların şifresi: <code>Sanal1234!</code><br>
                Öğrenci: <code>burak.solmaz@ornek.local</code><br>
                Öğretim elemanı: <code>ayhan.aydin@ornek.local</code><br>
                Yönetici: <code>yonetici@ornek.local</code>
            </div>

            <div class="giris-alt">
                Bu uygulama <strong>ders projesi</strong> olarak geliştirilmiştir; resmî bir uygulama değildir.
                Girilen bilgiler demo amaçlıdır.
                <br><a href="<?= e(url('')) ?>">← Ana sayfaya dön</a>
            </div>
        </div>
    </section>
</div>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
