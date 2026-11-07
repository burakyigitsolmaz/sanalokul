<?php

declare(strict_types=1);

/**
 * Yönetici paneli - işlem logları.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

Auth::rolGerekli(['yonetici']);

$arama = trim((string) ($_GET['q'] ?? ''));
$tablo = (string) ($_GET['tablo'] ?? '');
$sayfa = max(1, (int) ($_GET['sayfa'] ?? 1));
$limit = 25;
$offset = ($sayfa - 1) * $limit;

[$loglar, $toplam] = Yonetici::loglar($arama, $tablo, $limit, $offset);
$sayfaSayisi = max(1, (int) ceil($toplam / $limit));

$sayfaBaslik = 'İşlem Logları';
$aktifMenu   = 'panel';
$panelAktif  = 'loglar';

require dirname(__DIR__) . '/templates/admin_bas.php';
?>

<div class="sayfa-basligi">
    <div>
        <h1>İşlem Logları</h1>
        <p><?= e((string) $toplam) ?> kayıt · sayfa <?= e((string) $sayfa) ?> / <?= e((string) $sayfaSayisi) ?></p>
    </div>
</div>

<form class="filtre-cubuk" method="get" action="<?= e(url('admin/loglar.php')) ?>">
    <div class="filtre-alan">
        <label class="form-etiket" for="q">Ara</label>
        <input class="form-girdi" id="q" name="q" value="<?= e($arama) ?>"
               placeholder="İşlem, kişi veya IP">
    </div>
    <div class="filtre-alan">
        <label class="form-etiket" for="tablo">Tablo</label>
        <select class="form-girdi" id="tablo" name="tablo">
            <option value="">Tümü</option>
            <?php foreach (Yonetici::logTablolari() as $t): ?>
                <option value="<?= e($t) ?>" <?= $tablo === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtre-eylem">
        <button class="dugme dugme-birincil dugme-kucuk" type="submit">Filtrele</button>
        <a class="dugme dugme-hayalet dugme-kucuk" href="<?= e(url('admin/loglar.php')) ?>">Sıfırla</a>
    </div>
</form>

<section class="kart">
    <div class="kart-baslik"><h3>Kayıtlar</h3></div>

    <?php if ($loglar === []): ?>
        <div class="bos-durum">Eşleşen işlem kaydı yok.</div>
    <?php else: ?>
        <div class="tablo-sarmal">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>İşlem</th>
                        <th>Kim</th>
                        <th class="gizle-mobil">Tablo</th>
                        <th class="gizle-mobil">Kayıt ID</th>
                        <th class="gizle-mobil">IP</th>
                        <th class="metin-sag">Zaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loglar as $l): ?>
                        <tr>
                            <td><?= e((string) $l['id']) ?></td>
                            <td><strong><?= e($l['islem']) ?></strong></td>
                            <td class="metin-kucuk gizle-mobil"><?= e(trim((string) $l['kim'])) ?></td>
                            <td class="metin-kucuk gizle-mobil"><?= e($l['tablo_adi'] ?? '—') ?></td>
                            <td class="metin-kucuk sayi gizle-mobil"><?= e($l['kayit_id'] === null ? '—' : (string) $l['kayit_id']) ?></td>
                            <td class="metin-kucuk gizle-mobil"><?= e($l['ip'] ?? '—') ?></td>
                            <td class="metin-sag sayi"><?= e(date('d.m.Y H:i', strtotime((string) $l['zaman']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($sayfaSayisi > 1): ?>
            <div class="sayfalama" style="margin-top:12px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                <?php for ($s = 1; $s <= $sayfaSayisi; $s++): ?>
                    <a class="dugme dugme-hayalet dugme-kucuk <?= $s === $sayfa ? 'dugme-birincil' : '' ?>"
                       href="<?= e(url('admin/loglar.php' . '?sayfa=' . $s . ($arama !== '' ? '&q=' . urlencode($arama) : '') . ($tablo !== '' ? '&tablo=' . urlencode($tablo) : ''))) ?>">
                        <?= e((string) $s) ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/templates/admin_son.php'; ?>
