<?php

declare(strict_types=1);

/**
 * Faz 1 - Kurulum ve veritabanı durum ekranı.
 * Amaç: PHP, Apache ve MySQL bağlantısının yanı sıra Faz 1'de kurulan
 *       şemayı (tablolar, view'lar, trigger'lar, prosedürler) tek ekranda göstermek.
 * Faz 2'de bu sayfa yerini gerçek ana sayfaya bırakacak.
 */

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

date_default_timezone_set((string) config('uygulama.zaman_dilimi'));

$hatalar   = [];
$veritabani = null;
$tablolar  = [];
$nesneler  = ['tablo' => 0, 'view' => 0, 'trigger' => 0, 'prosedur' => 0];

try {
    $veritabani = Database::bilgi();
    $tablolar   = Database::tabloListesi();
    $nesneler   = Database::nesneSayilari();
} catch (Throwable $hata) {
    $hatalar[] = $hata->getMessage();
}

$zorunluEklentiler = [
    'pdo_mysql' => 'Veritabanı bağlantısı',
    'mbstring'  => 'Türkçe karakter işlemleri',
    'zip'       => 'Excel (.xlsx) yemek listesi okuma',
    'openssl'   => 'Güvenli (https) bağlantı',
    'fileinfo'  => 'Dosya türü kontrolü',
];

$opsiyonelEklentiler = [
    'curl' => 'Dış kaynaklardan veri çekme (yoksa stream kullanılır)',
];

$klasorler = [
    'config'   => is_dir(__DIR__ . '/config'),
    'src'      => is_dir(__DIR__ . '/src'),
    'database' => is_dir(__DIR__ . '/database'),
    'cron'     => is_dir(__DIR__ . '/cron'),
    'uploads'  => is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads'),
    'docs'     => is_dir(__DIR__ . '/docs'),
];

$herSeyYolunda = $hatalar === []
    && !in_array(false, array_map(static fn (string $k): bool => extension_loaded($k), array_keys($zorunluEklentiler)), true)
    && !in_array(false, $klasorler, true);

$sunucu = $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor';
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Kurulum Kontrolü · <?= e(config('uygulama.ad')) ?></title>
    <style>
        :root {
            --so-bg: #0b1120;
            --so-bg-soft: #111a2e;
            --so-surface: #16213a;
            --so-border: #253552;
            --so-text: #e6edf7;
            --so-muted: #93a4c0;
            --so-primary: #4f8cff;
            --so-success: #22c55e;
            --so-danger: #ef4444;
            --so-warning: #f59e0b;
            --so-radius: 14px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(1000px 500px at 15% -10%, rgba(79, 140, 255, .18), transparent 60%),
                radial-gradient(800px 400px at 90% 0%, rgba(34, 197, 94, .10), transparent 55%),
                var(--so-bg);
            color: var(--so-text);
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        .kapsayici {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        header.ust {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .marka { display: flex; align-items: center; gap: 12px; }

        .marka-isaret {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--so-primary), #2b5fd9);
            font-weight: 700;
            font-size: 15px;
            letter-spacing: .5px;
            color: #fff;
            box-shadow: 0 8px 22px rgba(79, 140, 255, .35);
        }

        .marka h1 { font-size: 17px; margin: 0; font-weight: 650; }
        .marka p { margin: 0; font-size: 12.5px; color: var(--so-muted); }

        .rozet {
            font-size: 12px;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid var(--so-border);
            background: rgba(255, 255, 255, .04);
            color: var(--so-muted);
        }

        .durum {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 20px 22px;
            border-radius: var(--so-radius);
            border: 1px solid rgba(34, 197, 94, .35);
            background: linear-gradient(135deg, rgba(34, 197, 94, .14), rgba(34, 197, 94, .04));
            margin-bottom: 22px;
        }

        .durum.hata {
            border-color: rgba(239, 68, 68, .4);
            background: linear-gradient(135deg, rgba(239, 68, 68, .14), rgba(239, 68, 68, .04));
        }

        .durum-ikon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 21px;
            background: rgba(34, 197, 94, .18);
            color: var(--so-success);
        }

        .durum.hata .durum-ikon { background: rgba(239, 68, 68, .18); color: var(--so-danger); }

        .durum h2 { margin: 0 0 3px; font-size: 19px; }
        .durum p { margin: 0; color: var(--so-muted); font-size: 14px; }
        .durum code { color: var(--so-text); background: rgba(0, 0, 0, .25); padding: 2px 6px; border-radius: 6px; font-size: 12.5px; }

        .izgara {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .kart {
            background: var(--so-surface);
            border: 1px solid var(--so-border);
            border-radius: var(--so-radius);
            padding: 16px 18px;
        }

        .kart .etiket { font-size: 12px; color: var(--so-muted); text-transform: uppercase; letter-spacing: .6px; }
        .kart .deger { font-size: 17px; font-weight: 640; margin-top: 4px; word-break: break-word; }
        .kart .alt { font-size: 12px; color: var(--so-muted); margin-top: 2px; }

        .bolum {
            background: var(--so-surface);
            border: 1px solid var(--so-border);
            border-radius: var(--so-radius);
            padding: 18px 20px;
            margin-bottom: 16px;
        }

        .bolum h3 {
            margin: 0 0 12px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bolum h3 span { font-size: 12px; font-weight: 400; color: var(--so-muted); }

        .liste { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 8px; }

        .satir {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .03);
            font-size: 13.5px;
        }

        .satir .ad { color: var(--so-text); }
        .satir .ad small { display: block; color: var(--so-muted); font-size: 11.5px; }

        .im {
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .im.ok { background: rgba(34, 197, 94, .16); color: #4ade80; }
        .im.yok { background: rgba(239, 68, 68, .16); color: #fca5a5; }
        .im.bos { background: rgba(245, 158, 11, .16); color: #fcd34d; }

        .bos-durum {
            text-align: center;
            color: var(--so-muted);
            font-size: 13.5px;
            padding: 10px 0 2px;
        }

        .sonraki {
            border: 1px dashed var(--so-border);
            border-radius: var(--so-radius);
            padding: 16px 20px;
            font-size: 13.5px;
            color: var(--so-muted);
        }

        .sonraki strong { color: var(--so-text); }

        footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #6f819e;
            line-height: 1.7;
        }

        @media (max-width: 560px) {
            .kapsayici { padding: 22px 14px 40px; }
            .kart .deger { font-size: 15.5px; }
            .durum { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="kapsayici">
        <header class="ust">
            <div class="marka">
                <div class="marka-isaret">NMYO</div>
                <div>
                    <h1><?= e(config('uygulama.ad')) ?></h1>
                    <p>Kurulum kontrol ekranı · Faz 1 tamamlandı</p>
                </div>
            </div>
            <span class="rozet">v<?= e(config('uygulama.surum')) ?> · <?= e(config('uygulama.ortam')) ?></span>
        </header>

        <?php if ($herSeyYolunda): ?>
            <div class="durum">
                <div class="durum-ikon"></div>
                <div>
                    <h2>Veritabanına bağlandı</h2>
                    <p>
                        <code><?= e($veritabani['veritabani'] ?? 'sanalokul') ?></code> veritabanına
                        <code><?= e('sanalokul_app') ?></code> kullanıcısıyla bağlantı kuruldu.
                        Şema, trigger'lar, view'lar ve örnek veriler yüklü. Faz 2'ye geçilebilir.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="durum hata">
                <div class="durum-ikon">!</div>
                <div>
                    <h2>Kurulumda eksik var</h2>
                    <p>Aşağıdaki kırmızı maddeleri düzeltmeden Faz 2'ye geçmeyelim.</p>
                    <?php foreach ($hatalar as $hata): ?>
                        <p><code><?= e($hata) ?></code></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="izgara">
            <div class="kart">
                <div class="etiket">PHP</div>
                <div class="deger"><?= e(PHP_VERSION) ?></div>
                <div class="alt"><?= e(PHP_SAPI) ?></div>
            </div>
            <div class="kart">
                <div class="etiket">Sunucu</div>
                <div class="deger"><?= e($sunucu) ?></div>
                <div class="alt">Apache + MySQL (XAMPP)</div>
            </div>
            <div class="kart">
                <div class="etiket">Veritabanı</div>
                <div class="deger"><?= e($veritabani['surum'] ?? 'Bağlantı yok') ?></div>
                <div class="alt"><?= e($veritabani['veritabani'] ?? '-') ?></div>
            </div>
            <div class="kart">
                <div class="etiket">Karakter Seti</div>
                <div class="deger"><?= e($veritabani['charset'] ?? '-') ?></div>
                <div class="alt"><?= e($veritabani['collation'] ?? '-') ?></div>
            </div>
            <div class="kart">
                <div class="etiket">Zaman Dilimi</div>
                <div class="deger"><?= e(date_default_timezone_get()) ?></div>
                <div class="alt"><?= e(date('d.m.Y H:i')) ?></div>
            </div>
            <div class="kart">
                <div class="etiket">Tablo Sayısı</div>
                <div class="deger"><?= e((string) $nesneler['tablo']) ?></div>
                <div class="alt">
                    <?= e((string) $nesneler['view']) ?> view ·
                    <?= e((string) $nesneler['trigger']) ?> trigger ·
                    <?= e((string) $nesneler['prosedur']) ?> prosedür
                </div>
            </div>
        </div>

        <section class="bolum">
            <h3>PHP Eklentileri <span>Zorunlu olanlar</span></h3>
            <div class="liste">
                <?php foreach ($zorunluEklentiler as $eklenti => $aciklama): ?>
                    <?php $var = extension_loaded($eklenti); ?>
                    <div class="satir">
                        <div class="ad"><?= e($eklenti) ?><small><?= e($aciklama) ?></small></div>
                        <span class="im <?= $var ? 'ok' : 'yok' ?>"><?= $var ? 'Açık' : 'Kapalı' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 style="margin-top:16px">Opsiyonel <span>Yoksa da sistem çalışır</span></h3>
            <div class="liste">
                <?php foreach ($opsiyonelEklentiler as $eklenti => $aciklama): ?>
                    <?php $var = extension_loaded($eklenti); ?>
                    <div class="satir">
                        <div class="ad"><?= e($eklenti) ?><small><?= e($aciklama) ?></small></div>
                        <span class="im <?= $var ? 'ok' : 'bos' ?>"><?= $var ? 'Açık' : 'Kapalı' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bolum">
            <h3>Klasör Yapısı <span>uploads yazılabilir olmalı</span></h3>
            <div class="liste">
                <?php foreach ($klasorler as $ad => $durum): ?>
                    <div class="satir">
                        <div class="ad"><?= e($ad) ?>/</div>
                        <span class="im <?= $durum ? 'ok' : 'yok' ?>"><?= $durum ? 'Hazır' : 'Eksik' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bolum">
            <h3>Veritabanı Tabloları <span><?= e((string) $nesneler['tablo']) ?> tablo</span></h3>
            <?php if ($tablolar === []): ?>
                <div class="bos-durum">
                    Henüz tablo yok. <code>database/01_schema.sql</code> dosyasını içe aktarın.
                </div>
            <?php else: ?>
                <div class="liste">
                    <?php foreach ($tablolar as $tablo): ?>
                        <div class="satir"><div class="ad"><?= e($tablo) ?></div></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="sonraki">
            <strong>Sıradaki adım:</strong> Faz 2 — giriş sistemi (oturum, şifre doğrulama, rol bazlı yönlendirme),
            ortak şablon (header/footer), koyu/açık tema ve mobil uyumlu ana sayfa.
        </div>

        <footer>
            Bu uygulama Ankara Üniversitesi Nallıhan MYO öğrencileri tarafından ders projesi olarak geliştirilmiştir;
            resmî bir uygulama değildir.
        </footer>
    </div>
</body>
</html>
