<?php

declare(strict_types=1);

/**
 * Yönetici paneli - veritabanı durumu.
 *
 * Tek ekranda şunları gösterir:
 *   - Bağlantı bilgisi ve nesne sayıları (tablo / view / trigger / prosedür)
 *   - Tabloların canlı kayıt sayıları (gruplu)
 *   - Son işlem logları
 *   - Son giriş denemeleri (başarılı / başarısız)
 *   - ER diyagramı
 *
 * Amaç: sunum sırasında "veritabanı burada çalışıyor" demeyi kolaylaştırmak.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$pdo = Database::get();

$bilgi = Database::bilgi();
$nesne = Database::nesneSayilari();

/** Tablo grupları: sunumda "hangi tablo ne işe yarıyor" sorusunu kolaylaştırır. */
$gruplar = [
    'Kimlik ve organizasyon' => [
        'roller', 'bolumler', 'donemler', 'kullanicilar',
        'ogrenci_profilleri', 'ogretim_elemani_profilleri',
    ],
    'Akademik' => [
        'dersler', 'ders_acilislari', 'ders_kayitlari', 'ders_programi', 'notlar',
    ],
    'İçerik' => [
        'ders_icerikleri', 'duyurular', 'odevler', 'teslimler',
    ],
    'Denetim ve güvenlik' => [
        'islem_loglari', 'giris_denemeleri',
    ],
    'Dış veri' => [
        'yemek_menuleri', 'dis_haberler', 'kaynak_senkron_durumu',
    ],
];

$tablolar = Database::tabloListesi();

$sayilar = [];
foreach ($tablolar as $tablo) {
    if (preg_match('/^[A-Za-z0-9_]+$/', $tablo) !== 1) {
        continue;
    }
    $sayilar[$tablo] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $tablo . '`')->fetchColumn();
}

// Gruplara girmeyen tablo kalırsa "Diğer" altında göster.
$gruplanan = array_merge(...array_values($gruplar));
$kalanlar  = array_values(array_diff($tablolar, $gruplanan));
if ($kalanlar !== []) {
    $gruplar['Diğer'] = $kalanlar;
}

$toplamKayit = array_sum($sayilar);

$viewlar = $pdo->query(
    "SELECT table_name AS ad
     FROM information_schema.views
     WHERE table_schema = DATABASE()
     ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$tetikleyiciler = $pdo->query(
    "SELECT trigger_name AS ad, event_object_table AS tablo,
            action_timing AS zaman, event_manipulation AS olay
     FROM information_schema.triggers
     WHERE trigger_schema = DATABASE()
     ORDER BY trigger_name"
)->fetchAll();

$prosedurler = $pdo->query(
    "SELECT routine_name AS ad
     FROM information_schema.routines
     WHERE routine_schema = DATABASE() AND routine_type = 'PROCEDURE'
     ORDER BY routine_name"
)->fetchAll(PDO::FETCH_COLUMN);

$loglar = $pdo->query(
    "SELECT l.islem, l.tablo_adi, l.zaman, l.ip,
            NULLIF(TRIM(CONCAT(COALESCE(k.ad, ''), ' ', COALESCE(k.soyad, ''))), '') AS kim
     FROM islem_loglari l
         LEFT JOIN kullanicilar k ON k.id = l.kullanici_id
     ORDER BY l.id DESC
     LIMIT 10"
)->fetchAll();

$denemeler = $pdo->query(
    'SELECT eposta, ip, basarili, zaman
     FROM giris_denemeleri
     ORDER BY id DESC
     LIMIT 10'
)->fetchAll();

$sayiYaz = static fn (int $n): string => number_format($n, 0, ',', '.');
$erDosya = 'docs/er-diyagram.png';

$sayfaBaslik = 'Veritabanı';
$aktifMenu   = 'panel';
$panelAktif  = 'veritabani';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Veritabanı</h1>
        <p>
            <?= e((string) $bilgi['veritabani']) ?> ·
            MySQL/MariaDB <?= e((string) $bilgi['surum']) ?> ·
            <?= e((string) $bilgi['charset']) ?> / <?= e((string) $bilgi['collation']) ?>
        </p>
    </div>
    <div class="sayfa-eylem">
        <?php if (is_file(dirname(__DIR__) . '/' . $erDosya)): ?>
            <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(varlik($erDosya)) ?>" target="_blank" rel="noopener">
                ER diyagramını aç
            </a>
        <?php endif; ?>
        <a class="dugme dugme-birincil dugme-kucuk" href="<?= e(url('admin/loglar.php')) ?>">İşlem Logları</a>
    </div>
</div>

<div class="izgara izgara-4" style="margin-bottom:16px">
    <div class="stat-kart">
        <div class="etiket">Tablo</div>
        <div class="deger"><?= e((string) $nesne['tablo']) ?></div>
        <div class="alt">InnoDB · yabancı anahtarlı</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">View</div>
        <div class="deger"><?= e((string) $nesne['view']) ?></div>
        <div class="alt">hazır rapor sorguları</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Trigger</div>
        <div class="deger"><?= e((string) $nesne['trigger']) ?></div>
        <div class="alt">not hesabı · kontenjan · log</div>
    </div>
    <div class="stat-kart">
        <div class="etiket">Prosedür</div>
        <div class="deger"><?= e((string) $nesne['prosedur']) ?></div>
        <div class="alt">kayıt · ortalama · rapor</div>
    </div>
</div>

<div class="kart mb-3" style="display:flex;flex-wrap:wrap;gap:10px 26px;align-items:center">
    <div>
        <span class="metin-silik metin-kucuk">Veritabanı</span>
        <strong class="veritabani-kod"><?= e((string) $bilgi['veritabani']) ?></strong>
    </div>
    <div>
        <span class="metin-silik metin-kucuk">Motor</span>
        <strong>InnoDB</strong>
    </div>
    <div>
        <span class="metin-silik metin-kucuk">Karakter seti</span>
        <strong class="veritabani-kod"><?= e((string) $bilgi['charset']) ?></strong>
    </div>
    <div>
        <span class="metin-silik metin-kucuk">Toplam kayıt</span>
        <strong class="veritabani-sayi"><?= e($sayiYaz($toplamKayit)) ?></strong>
    </div>
    <div>
        <span class="metin-silik metin-kucuk">Sunucu</span>
        <strong class="metin-kucuk"><?= e((string) $bilgi['surum']) ?></strong>
    </div>
</div>

<section class="kart mb-3">
    <div class="kart-baslik">
        <h3>Tablolar ve Canlı Kayıt Sayıları</h3>
        <span class="rozet"><?= e($sayiYaz($toplamKayit)) ?> kayıt</span>
    </div>

    <div class="izgara izgara-2" style="align-items:start">
        <?php foreach ($gruplar as $grupAd => $grupTablolar): ?>
            <?php
            $grupToplam = 0;
            foreach ($grupTablolar as $tablo) {
                $grupToplam += $sayilar[$tablo] ?? 0;
            }
            ?>
            <section class="kart">
                <div class="kart-baslik">
                    <h3><?= e($grupAd) ?></h3>
                    <span class="rozet"><?= e($sayiYaz($grupToplam)) ?></span>
                </div>
                <div class="satir-liste">
                    <?php foreach ($grupTablolar as $tablo): ?>
                        <?php if (!array_key_exists($tablo, $sayilar)) { continue; } ?>
                        <div class="satir">
                            <div class="satir-icerik">
                                <strong class="veritabani-kod"><?= e($tablo) ?></strong>
                            </div>
                            <div class="satir-sag veritabani-sayi"><?= e($sayiYaz((int) $sayilar[$tablo])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</section>

<div class="izgara izgara-2 mb-3" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>Son İşlemler</h3>
            <a class="baglanti metin-kucuk" href="<?= e(url('admin/loglar.php')) ?>">Tümü →</a>
        </div>

        <?php if ($loglar === []): ?>
            <div class="bos-durum">Henüz işlem kaydı yok. Panelde bir işlem yapınca burada görünür.</div>
        <?php else: ?>
            <div class="satir-liste">
                <?php foreach ($loglar as $log): ?>
                    <div class="satir">
                        <div class="satir-icerik">
                            <strong class="kirp"><?= e((string) $log['islem']) ?></strong>
                            <span>
                                <?= e($log['kim'] ?? 'Sistem') ?>
                                <?php if (!empty($log['tablo_adi'])): ?>
                                    · <span class="veritabani-kod"><?= e((string) $log['tablo_adi']) ?></span>
                                <?php endif; ?>
                                · <?= e((string) ($log['ip'] ?? '-')) ?>
                            </span>
                        </div>
                        <div class="satir-sag sayi"><?= e(date('d.m H:i', strtotime((string) $log['zaman']))) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Son Giriş Denemeleri</h3>
            <span class="rozet">giris_denemeleri</span>
        </div>

        <?php if ($denemeler === []): ?>
            <div class="bos-durum">Henüz giriş denemesi kaydı yok. Giriş ekranında bir deneme yapınca burada görünür.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>E-posta</th>
                            <th>IP</th>
                            <th>Durum</th>
                            <th class="metin-sag">Zaman</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($denemeler as $d): ?>
                            <tr>
                                <td class="metin-kucuk kirp"><?= e((string) $d['eposta']) ?></td>
                                <td class="metin-kucuk veritabani-kod"><?= e((string) $d['ip']) ?></td>
                                <td>
                                    <?php if ((int) $d['basarili'] === 1): ?>
                                        <span class="rozet rozet-basari">Başarılı</span>
                                    <?php else: ?>
                                        <span class="rozet rozet-hata">Başarısız</span>
                                    <?php endif; ?>
                                </td>
                                <td class="metin-sag metin-kucuk sayi"><?= e(date('d.m H:i', strtotime((string) $d['zaman']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="izgara izgara-3 mb-3" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik">
            <h3>View'ler</h3>
            <span class="rozet"><?= e((string) count($viewlar)) ?></span>
        </div>
        <div class="satir-liste">
            <?php foreach ($viewlar as $view): ?>
                <div class="satir">
                    <div class="satir-icerik"><strong class="veritabani-kod"><?= e((string) $view) ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Trigger'lar</h3>
            <span class="rozet"><?= e((string) count($tetikleyiciler)) ?></span>
        </div>
        <div class="satir-liste">
            <?php foreach ($tetikleyiciler as $t): ?>
                <div class="satir">
                    <div class="satir-icerik">
                        <strong class="veritabani-kod kirp"><?= e((string) $t['ad']) ?></strong>
                        <span>
                            <?= e((string) $t['zaman']) ?> <?= e((string) $t['olay']) ?> ·
                            <span class="veritabani-kod"><?= e((string) $t['tablo']) ?></span>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="kart">
        <div class="kart-baslik">
            <h3>Prosedürler</h3>
            <span class="rozet"><?= e((string) count($prosedurler)) ?></span>
        </div>
        <div class="satir-liste">
            <?php foreach ($prosedurler as $prosedur): ?>
                <div class="satir">
                    <div class="satir-icerik"><strong class="veritabani-kod"><?= e((string) $prosedur) ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="kart">
    <div class="kart-baslik">
        <h3>ER Diyagramı</h3>
        <span class="rozet">varlık–ilişki</span>
    </div>

    <?php if (is_file(dirname(__DIR__) . '/' . $erDosya)): ?>
        <p class="metin-silik metin-kucuk" style="margin-top:-4px">
            Kutular tabloları, çizgiler yabancı anahtar ilişkilerini gösterir.
            Diyagramı büyütmek için görsele tıklayın.
        </p>
        <div class="veritabani-er">
            <a href="<?= e(varlik($erDosya)) ?>" target="_blank" rel="noopener" data-er-buyut>
                <img src="<?= e(varlik($erDosya)) ?>" alt="Veritabanı ER diyagramı: 20 tablo ve ilişkileri" loading="lazy">
            </a>
        </div>

        <dialog class="er-buyut" id="erBuyut" aria-label="Veritabanı ER diyagramı büyütülmüş görünüm">
            <div class="er-buyut-bas">
                <strong>Veritabanı ER Diyagramı</strong>
                <div class="er-buyut-eylem">
                    <a class="dugme dugme-hayalet dugme-kucuk"
                       href="<?= e(varlik($erDosya)) ?>" target="_blank" rel="noopener">
                        Yeni sekmede aç
                    </a>
                    <button class="er-buyut-kapat" type="button" data-er-kapat aria-label="Pencereyi kapat">×</button>
                </div>
            </div>
            <div class="er-buyut-govde">
                <img src="<?= e(varlik($erDosya)) ?>" alt="Veritabanı ER diyagramı: 20 tablo ve ilişkileri">
            </div>
        </dialog>
    <?php else: ?>
        <div class="bos-durum">ER diyagramı bulunamadı: <span class="veritabani-kod">docs/er-diyagram.png</span></div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
