<?php

declare(strict_types=1);

/**
 * Yönetici paneli - CSV öğrenci içe aktarma.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$bolumler = Yonetici::bolumler();

$sonuc = null;
$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
    } else {
        $dosya = $_FILES['dosya'] ?? null;
        $sifre = trim((string) ($_POST['sifre'] ?? 'Sanal1234!'));

        if ($dosya === null || $dosya['error'] !== UPLOAD_ERR_OK) {
            Auth::flash('hata', 'Geçerli bir CSV dosyası seçin.');
        } elseif (!in_array(strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            Auth::flash('hata', 'Yalnızca .csv veya .txt dosyası kabul edilir.');
        } elseif (mb_strlen($sifre, 'UTF-8') < 8) {
            Auth::flash('hata', 'Varsayılan şifre en az 8 karakter olmalıdır.');
        } else {
            $icerik = file_get_contents($dosya['tmp_name']);

            // BOM temizle
            if (strlen($icerik) >= 3 && bin2hex(substr($icerik, 0, 3)) === 'efbbbf') {
                $icerik = substr($icerik, 3);
            }

            $satirlar = str_getcsv($icerik, "\n");
            $basliklar = null;
            $veri = [];

            foreach ($satirlar as $satir) {
                $hucreler = str_getcsv($satir);

                if ($basliklar === null) {
                    $basliklar = array_map(static fn ($h) => trim((string) $h), $hucreler);
                    continue;
                }

                $row = [];
                foreach ($basliklar as $i => $baslik) {
                    $row[$baslik] = $hucreler[$i] ?? '';
                }

                $veri[] = $row;
            }

            if ($veri === []) {
                Auth::flash('hata', 'Dosyada veri satırı bulunamadı.');
            } else {
                $sonuc = Yonetici::csvOgrenciAktar($veri, $sifre);

                if ($sonuc['eklenen'] > 0) {
                    Auth::log('CSV öğrenci aktarımı: ' . $sonuc['eklenen'] . ' kişi', 'kullanicilar', 0);
                    Auth::flash('basari', $sonuc['eklenen'] . ' öğrenci eklendi.');
                }

                $hatalar = $sonuc['hatalar'];
            }
        }
    }
}

$sayfaBaslik = 'CSV Aktarma';
$aktifMenu   = 'panel';
$panelAktif  = 'csv';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>CSV Öğrenci Aktarma</h1>
        <p>Toplu öğrenci eklemek için bir CSV dosyası yükleyin.</p>
    </div>
</div>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik"><h3>Dosya Yükle</h3></div>

        <form method="post" action="<?= e(url('admin/csv.php')) ?>" enctype="multipart/form-data">
            <?= Auth::csrfAlani() ?>

            <div class="form-grup">
                <label class="form-etiket" for="dosya">CSV dosyası</label>
                <input class="form-girdi" id="dosya" type="file" name="dosya" accept=".csv,.txt" required>
                <p class="form-ipucu">Sütunlar: numara, ad, soyad, eposta, bolum_kod, sinif, giris_yili</p>
            </div>

            <div class="form-grup">
                <label class="form-etiket" for="sifre">Varsayılan şifre</label>
                <input class="form-girdi" id="sifre" type="password" name="sifre" value="Sanal1234!" minlength="8">
                <p class="form-ipucu">Eklenen her öğrenci için varsayılan şifre.</p>
            </div>

            <div class="form-eylem">
                <button class="dugme dugme-birincil" type="submit">İçe Aktar</button>
            </div>
        </form>
    </section>

    <section class="kart">
        <div class="kart-baslik"><h3>Örnek CSV İçeriği</h3></div>
        <pre class="ornek-csv">numara,ad,soyad,eposta,bolum_kod,sinif,giris_yili
20260001,Ali,Veli,ali.veli@ornek.local,NBP,1,2026
20260002,Deniz,Yildiz,deniz.yildiz@ornek.local,NBP,2,2025
20260003,Elif,Kara,elif.kara@ornek.local,MKM,1,2026</pre>
        <div class="form-ipucu">İlk satır başlık satırı olmalı. Bölüm kodu sistemde tanımlı olmalıdır.</div>
    </section>
</div>

<?php if ($sonuc !== null): ?>
    <section class="kart mt-3">
        <div class="kart-baslik">
            <h3>Sonuç</h3>
        </div>
        <div class="form-izgara">
            <div class="stat-kart">
                <div class="etiket">Eklenen</div>
                <div class="deger"><?= e((string) $sonuc['eklenen']) ?></div>
            </div>
            <div class="stat-kart">
                <div class="etiket">Hata</div>
                <div class="deger"><?= e((string) count($hatalar)) ?></div>
            </div>
        </div>

        <?php if ($hatalar !== []): ?>
            <div class="tablo-sarmal" style="margin-top:10px">
                <table class="tablo">
                    <thead><tr><th>Hata</th></tr></thead>
                    <tbody>
                        <?php foreach ($hatalar as $h): ?>
                            <tr><td><?= e($h) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
