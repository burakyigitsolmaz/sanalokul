<?php

declare(strict_types=1);

/**
 * Yönetici paneli - veri senkronizasyonu.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$sonuc = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfDogrula($_POST['csrf'] ?? null)) {
        Auth::flash('hata', 'Form oturumu geçersiz. Sayfayı yenileyip tekrar deneyin.');
        header('Location: ' . url('admin/senkron.php'));
        exit;
    }

    $islem = (string) ($_POST['islem'] ?? '');

    if ($islem === 'yemek' || $islem === 'haber' || $islem === 'tumu') {
        $sonuc = match ($islem) {
            'yemek' => ['yemek' => Senkron::yemekGuncelle()],
            'haber' => ['haber' => Senkron::haberGuncelle()],
            default => Senkron::tumunuGuncelle(),
        };

        $hatalar = array_values(array_filter($sonuc, static fn (array $s): bool => !$s['ok']));

        if (count($hatalar) === 0) {
            Auth::log('Veri senkronizasyonu çalıştırıldı (' . $islem . ')', 'dis_haberler', 0);
            Auth::flash('basari', 'Senkronizasyon tamamlandı.');
        } else {
            Auth::log('Veri senkronizasyonu hatası (' . $islem . ')', 'kaynak_senkron_durumu', 0);
            Auth::flash('hata', 'Senkronizasyon sırasında hata oluştu. Kaynak durumunu kontrol edin.');
        }

        header('Location: ' . url('admin/senkron.php'));
        exit;
    }

    Auth::flash('hata', 'Bilinmeyen işlem.');
    header('Location: ' . url('admin/senkron.php'));
    exit;
}

$kaynaklar = Senkron::kaynakDurumlari();
$yemekOzet = Senkron::yemekOzeti();
$haberOzet = Senkron::haberOzeti(6);

$durumEtiketi = static fn (string $d): string => match ($d) {
    'basarili' => 'Başarılı',
    'hata'      => 'Hata',
    'calisiyor' => 'Çalışıyor',
    default     => 'Bekliyor',
};

$durumRozeti = static fn (string $d): string => match ($d) {
    'basarili' => 'rozet rozet-basari',
    'hata'      => 'rozet rozet-hata',
    'calisiyor' => 'rozet rozet-uyari',
    default     => 'rozet',
};

$sayfaBaslik = 'Veri Senkronizasyonu';
$aktifMenu   = 'panel';
$panelAktif  = 'senkron';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>Veri Senkronizasyonu</h1>
        <p>Yemek menüsü ve haberler: okulun resmî kaynaklarından otomatik çekilir.</p>
    </div>
    <form method="post" action="<?= e(url('admin/senkron.php')) ?>" style="display:flex;gap:8px;flex-wrap:wrap">
        <?= Auth::csrfAlani() ?>
        <input type="hidden" name="islem" value="tumu">
        <button class="dugme dugme-birincil dugme-kucuk" type="submit">Hepsini Senkronize Et</button>
    </form>
</div>

<section class="kart">
    <div class="kart-baslik">
        <h3>Kaynak Durumu</h3>
    </div>

    <div class="izgara izgara-2" style="align-items:start">
        <?php foreach ($kaynaklar as $k): ?>
            <div class="stat-kart">
                <div class="etiket"><?= e(ucfirst($k['kaynak'])) ?> Kaynağı</div>
                <div style="display:flex;align-items:center;gap:10px;margin-top:6px">
                    <span class="<?= e($durumRozeti($k['durum'])) ?>"><?= e($durumEtiketi($k['durum'])) ?></span>
                    <?php if (!empty($k['son_basari'])): ?>
                        <span class="metin-silik metin-kucuk">Son: <?= e(date('d.m.Y H:i', strtotime((string) $k['son_basari']))) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($k['son_deneme'])): ?>
                    <div class="metin-silik metin-kucuk">Son deneme: <?= e(date('d.m.Y H:i', strtotime((string) $k['son_deneme']))) ?></div>
                <?php endif; ?>
                <?php if ($k['hata_mesaji'] !== null && $k['hata_mesaji'] !== ''): ?>
                    <div class="ornek-csv" style="margin-top:8px"><?= e($k['hata_mesaji']) ?></div>
                <?php endif; ?>
                <div style="margin-top:10px">
                    <form method="post" action="<?= e(url('admin/senkron.php')) ?>" data-onay="<?= e(ucfirst($k['kaynak'])) ?> kaynağı senkronize edilecek. Devam edilsin mi?">
                        <?= Auth::csrfAlani() ?>
                        <input type="hidden" name="islem" value="<?= e($k['kaynak']) ?>">
                        <button class="dugme dugme-hayalet dugme-kucuk" type="submit"><?= e(ucfirst($k['kaynak'])) ?> Kaynağını Senkronize Et</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-ipucu" style="margin-top:14px">
        Kaynaklar: Yemek → Ankara Üniversitesi SKS aylık menü dosyası (XLSX); Haber → Nallıhan MYO WordPress REST API.
        Senkron idempotenttir (aynı kayıt üzerine yazılmaz, yeniden yüklenir).
    </div>
</section>

<div class="izgara izgara-2" style="align-items:start">
    <section class="kart">
        <div class="kart-baslik"><h3>Yemek Menüsü (Yaklaşan Günler)</h3></div>
        <?php if ($yemekOzet === []): ?>
            <div class="bos-durum">Kayıtlı yemek menüsü yok. "Hepsini Senkronize Et" düğmesini kullanın.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead>
                        <tr><th>Tarih</th><th>Çorba</th><th>Ana Yemek</th><th class="metin-sag">KCal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($yemekOzet as $y): ?>
                            <tr>
                                <td><?= e(date('d.m.Y D', strtotime((string) $y['tarih']))) ?></td>
                                <td class="metin-kucuk"><?= e($y['corba'] ?? '-') ?></td>
                                <td class="metin-kucuk"><?= e($y['ana_yemek'] ?? '-') ?></td>
                                <td class="metin-sag sayi"><?= e($y['toplam_kcal'] === null ? '-' : (string) $y['toplam_kcal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="kart">
        <div class="kart-baslik"><h3>Son Haberler</h3></div>
        <?php if ($haberOzet === []): ?>
            <div class="bos-durum">Kayıtlı haber yok. Senkronu çalıştırın.</div>
        <?php else: ?>
            <div class="tablo-sarmal">
                <table class="tablo">
                    <thead><tr><th>Haber</th><th class="metin-sag">Tarih</th></tr></thead>
                    <tbody>
                        <?php foreach ($haberOzet as $h): ?>
                            <tr>
                                <td><a class="baglanti" href="<?= e($h['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($h['baslik']) ?></a></td>
                                <td class="metin-sag sayi"><?= e(empty($h['yayin_tarihi']) ? '-' : date('d.m.Y', strtotime((string) $h['yayin_tarihi']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
