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
    <div class="giris-tema">
        <button class="tema-dugme" id="temaDugme" type="button"
                title="Tema değiştir" aria-label="Tema değiştir">
            <svg class="ikon-ay" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
            </svg>
            <svg class="ikon-gunes" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
        </button>
    </div>

    <div class="giris-kart-sarmal">
        <span class="giris-flama" aria-hidden="true"></span>
        <section class="giris-kart" aria-labelledby="girisBaslik">
        <div class="giris-kart-ust">
            <span class="marka-logo">
                <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Nallıhan Meslek Yüksekokulu">
            </span>
            <span class="marka-metin">
                <strong>Nallıhan MYO</strong>
                <span>Sanal Okul · Öğrenci Bilgi Sistemi</span>
            </span>
        </div>

        <h1 id="girisBaslik">Tekrar hoş geldiniz</h1>
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
            <div class="demo-baslik">
                <strong>Demo hesaplar</strong>
                <span>şifre: <code>Sanal1234!</code></span>
            </div>
            <div class="demo-liste">
                <button class="demo-oge" type="button" data-demo-eposta="burak.solmaz@ornek.local">
                    <span class="demo-rol">Öğrenci</span>
                    <span class="demo-adres">burak.solmaz@ornek.local</span>
                </button>
                <button class="demo-oge" type="button" data-demo-eposta="ayhan.aydin@ornek.local">
                    <span class="demo-rol">Öğretim elemanı</span>
                    <span class="demo-adres">ayhan.aydin@ornek.local</span>
                </button>
                <button class="demo-oge" type="button" data-demo-eposta="yonetici@ornek.local">
                    <span class="demo-rol">Yönetici</span>
                    <span class="demo-adres">yonetici@ornek.local</span>
                </button>
            </div>
        </div>

        <div class="giris-alt">
            Bu uygulama <strong>ders projesi</strong> olarak geliştirilmiştir; resmî bir uygulama değildir.
            Girilen bilgiler demo amaçlıdır.
            <br><a href="<?= e(url('')) ?>">← Ana sayfaya dön</a>
        </div>
        </section>
    </div>
</div>

<script src="<?= e(varlik('assets/js/app.js')) ?>"></script>
</body>
</html>
