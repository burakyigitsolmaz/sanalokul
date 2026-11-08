<?php

declare(strict_types=1);

/**
 * Veri senkronizasyonu.
 *
 * Kaynaklar:
 *   - yemek: Ankara Üniversitesi SKS aylık menü XLSX dosyası
 *     https://sksbasvuru.ankara.edu.tr/kayit/moduller/yemeklistesi/aylikmenu.php
 *     (ZipArchive ile açılır, sheet1.xml içindeki satırlardan okunur)
 *   - haber: Nallıhan MYO WordPress REST API
 *     https://nallihanmyo.ankara.edu.tr/wp-json/wp/v2/posts
 *
 * Senkron idempotenttir: aynı tarih/url tekrar girilmez, mevcutların üstüne yazılır.
 */
final class Senkron
{
    private const YEMEK_URL = 'https://sksbasvuru.ankara.edu.tr/kayit/moduller/yemeklistesi/aylikmenu.php';
    private const HABER_URL = 'https://nallihanmyo.ankara.edu.tr/wp-json/wp/v2/posts'
        . '?per_page=10&_fields=id,date,title,link,slug,excerpt';

    private const EXCEL_EPOCH = '1899-12-30'; // Excel seri tarih tabanı (1900 uyumluluğu)

    /**
     * Yemek ve haberi sırayla senkronize eder.
     *
     * @return array{yemek: array{ok: bool, mesaj: string}, haber: array{ok: bool, mesaj: string}}
     */
    public static function tumunuGuncelle(): array
    {
        return [
            'yemek' => self::yemekGuncelle(),
            'haber' => self::haberGuncelle(),
        ];
    }

    /**
     * Aylık menü XLSX'ini indirir ve yemek_menuleri tablosunu günceller.
     *
     * @return array{ok: bool, mesaj: string}
     */
    public static function yemekGuncelle(): array
    {
        self::kaynakDurumu('yemek', 'calisiyor', null);

        $gecici = self::dosyaIndir(self::YEMEK_URL, '.xlsx');
        if ($gecici === null) {
            return self::hataBildir('yemek', 'Menü dosyası indirilemedi. Siteye veya ağa ulaşılamıyor olabilir.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($gecici) !== true) {
                @unlink($gecici);
                return self::hataBildir('yemek', 'Menü dosyası zip açılamadı (dosya bozuk olabilir).');
            }

            $sst = self::paylasilanDizeler($zip);
            $shXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($shXml === false) {
                $zip->close();
                @unlink($gecici);
                return self::hataBildir('yemek', 'sheet1.xml bulunamadı; dosya biçimi değişmiş olabilir.');
            }

            $ws = @simplexml_load_string($shXml);
            if ($ws === false) {
                $zip->close();
                @unlink($gecici);
                return self::hataBildir('yemek', 'Menü sayfası XML olarak okunamadı.');
            }

            $kayitlar = self::yemekSatirlariniOku($ws, $sst);
            $zip->close();
            @unlink($gecici);

            if (count($kayitlar) === 0) {
                return self::hataBildir('yemek', 'Dosyada tarih içeren satır bulunamadı.');
            }

            $eklenen = self::yemekKayitlariniYaz($kayitlar);
            self::kaynakDurumu('yemek', 'basarili', null);

            return [
                'ok' => true,
                'mesaj' => count($kayitlar) . ' menü günü ' . ($eklenen > 0 ? 'eklendi' : 'güncellendi')
                    . '. Aylık menü ' . self::yasadisi($kayitlar) . ' arasını kapsıyor.',
            ];
        } catch (Throwable $e) {
            if (file_exists($gecici)) {
                @unlink($gecici);
            }

            return self::hataBildir('yemek', 'Menü işlenemedi: ' . $e->getMessage());
        }
    }

    /**
     * WordPress REST API'den son haberleri indirir ve dis_haberler'e yazar.
     *
     * @return array{ok: bool, mesaj: string}
     */
    public static function haberGuncelle(): array
    {
        self::kaynakDurumu('haber', 'calisiyor', null);

        $json = self::httpGet(self::HABER_URL);
        if ($json === null) {
            return self::hataBildir('haber', 'Haber listesine ulaşılamadı.');
        }

        $veri = json_decode($json, true);
        if (!is_array($veri)) {
            return self::hataBildir('haber', 'Haber listesi JSON olarak okunamadı.');
        }

        $pdo = Database::get();
        $eklenen = 0;
        $degisti = 0;

        $sorgu = $pdo->prepare(
            'INSERT INTO dis_haberler (kaynak, baslik, ozet, url, yayin_tarihi)
             VALUES (:kaynak, :baslik, :ozet, :url, :yayin)
             ON DUPLICATE KEY UPDATE
                 baslik = VALUES(baslik),
                 ozet = VALUES(ozet),
                 yayin_tarihi = VALUES(yayin_tarihi),
                 cekilme_zamani = CURRENT_TIMESTAMP'
        );

        foreach ($veri as $post) {
            $baslik = is_array($post['title']['rendered'] ?? null)
                ? null
                : trim((string) ($post['title']['rendered'] ?? ''));

            $url  = trim((string) ($post['link'] ?? ''));
            $link = $url;

            if ($baslik === '' || $url === '') {
                continue;
            }

            $ozet = is_array($post['excerpt']['rendered'] ?? null)
                ? ''
                : trim(strip_tags((string) ($post['excerpt']['rendered'] ?? '')));
            $ozet = preg_replace('/\s+/u', ' ', $ozet);
            if (mb_strlen($ozet, 'UTF-8') > 500) {
                $ozet = mb_substr($ozet, 0, 500, 'UTF-8') . '…';
            }

            $yayin = null;
            $tarih = (string) ($post['date'] ?? '');
            if ($tarih !== '') {
                $yayin = date('Y-m-d H:i:s', strtotime($tarih));
            }

            $sorgu->execute([
                'kaynak' => 'nallihanmyo.ankara.edu.tr',
                'baslik' => $baslik,
                'ozet'   => $ozet,
                'url'    => $url,
                'yayin'  => $yayin,
            ]);

            if ($sorgu->rowCount() === 1) {
                $eklenen++;
            } else {
                $degisti++;
            }
        }

        self::kaynakDurumu('haber', 'basarili', null);

        return [
            'ok' => true,
            'mesaj' => $eklenen . ' haber eklendi, ' . $degisti . ' haber güncellendi.',
        ];
    }

    /**
     * Kaynakların son durumunu döndürür.
     *
     * @return list<array{kaynak: string, son_deneme: ?string, son_basari: ?string, durum: string, hata_mesaji: ?string}>
     */
    public static function kaynakDurumlari(): array
    {
        return Database::get()->query(
            'SELECT kaynak, son_deneme, son_basari, durum, hata_mesaji
             FROM kaynak_senkron_durumu
             ORDER BY kaynak'
        )->fetchAll();
    }

    /**
     * Senkron sonrası yemek tablosunu hızlı okuma için ana sayfaya yetecek kadar
     * örnek döndürür.
     *
     * @return list<array{tarih: string, corba: ?string, ana_yemek: ?string, toplam_kcal: ?int}>
     */
    public static function yemekOzeti(): array
    {
        return Database::get()->query(
            'SELECT tarih, corba, ana_yemek, toplam_kcal
             FROM yemek_menuleri
             WHERE tarih >= CURDATE()
             ORDER BY tarih
             LIMIT 5'
        )->fetchAll();
    }

    public static function haberOzeti(int $limit = 6): array
    {
        $sorgu = Database::get()->prepare(
            'SELECT baslik, ozet, url, yayin_tarihi
             FROM dis_haberler
             ORDER BY yayin_tarihi DESC
             LIMIT ' . max(1, min(20, $limit))
        );
        $sorgu->execute();

        return $sorgu->fetchAll();
    }

    // ------------------------------------------------------------------
    // Yemek XLSX işleme
    // ------------------------------------------------------------------

    /**
     * @param SimpleXMLElement $ws
     * @param list<string> $sst
     * @return list<array<string, string|int|null>>
     */
    private static function yemekSatirlariniOku(SimpleXMLElement $ws, array $sst): array
    {
        $kayitlar = [];

        foreach ($ws->sheetData->row as $row) {
            $h = [];

            foreach ($row->c as $c) {
                if (!preg_match('/^([A-Z]+)/', (string) $c['r'], $m)) {
                    continue;
                }

                $t = (string) $c['t'];
                $v = (string) $c->v;

                if ($t === 's') {
                    $k = (int) $v;
                    $v = $sst[$k] ?? '';
                }

                $h[$m[1]] = $v;
            }

            if (!isset($h['A']) || !is_numeric($h['A'])) {
                continue;
            }

            $tarih = date('Y-m-d', strtotime(self::EXCEL_EPOCH . ' UTC') + (int) $h['A'] * 86400);
            if ($tarih < date('Y-m-01')) {
                // Geçmiş aya ait ilk satırı (ör. ay sonu) da tutmak istiyoruz
            }

            $corba = self::ayikla($h['B'] ?? '');
            $ana   = self::ayikla($h['C'] ?? '');
            $yard  = self::ayikla($h['D'] ?? '');
            $salata = self::ayikla($h['E'] ?? '');
            $tatli = self::ayikla($h['F'] ?? '');

            if ($corba['ad'] === '' && $ana['ad'] === '') {
                continue;
            }

            $kayitlar[] = [
                'tarih'          => $tarih,
                'corba'          => self::kisalt($corba['ad'], 120),
                'corba_kcal'     => $corba['kcal'],
                'ana_yemek'      => self::kisalt($ana['ad'], 120),
                'ana_kcal'       => $ana['kcal'],
                'yardimci_yemek' => self::kisalt($yard['ad'], 120),
                'yardimci_kcal'  => $yard['kcal'],
                'tatli_salata'   => self::kisalt(trim($salata['ad'] . ' / ' . $tatli['ad'], ' /'), 150),
                'ek_kcal'        => ($salata['kcal'] ?? 0) + ($tatli['kcal'] ?? 0) ?: null,
                'toplam_kcal'    => self::kcalAyiklaToplam($h['G'] ?? '') ?: ($corba['kcal'] ?? 0) + ($ana['kcal'] ?? 0) + ($yard['kcal'] ?? 0) + ($tatli['kcal'] ?? 0) + ($salata['kcal'] ?? 0) ?: null,
            ];
        }

        return $kayitlar;
    }

    /**
     * @param list<array<string, string|int|null>> $kayitlar
     */
    private static function yemekKayitlariniYaz(array $kayitlar): int
    {
        $pdo = Database::get();

        $sorgu = $pdo->prepare(
            'INSERT INTO yemek_menuleri
                (tarih, corba, ana_yemek, yardimci_yemek, tatli_salata,
                 corba_kcal, ana_kcal, yardimci_kcal, ek_kcal, toplam_kcal, kaynak_url)
             VALUES
                (:tarih, :corba, :ana, :yard, :tatli,
                 :corba_kcal, :ana_kcal, :yard_kcal, :ek_kcal, :toplam_kcal, :kaynak)
             ON DUPLICATE KEY UPDATE
                 corba = VALUES(corba),
                 ana_yemek = VALUES(ana_yemek),
                 yardimci_yemek = VALUES(yardimci_yemek),
                 tatli_salata = VALUES(tatli_salata),
                 corba_kcal = VALUES(corba_kcal),
                 ana_kcal = VALUES(ana_kcal),
                 yardimci_kcal = VALUES(yardimci_kcal),
                 ek_kcal = VALUES(ek_kcal),
                 toplam_kcal = VALUES(toplam_kcal),
                 kaynak_url = VALUES(kaynak_url),
                 cekilme_zamani = CURRENT_TIMESTAMP'
        );

        $eklenen = 0;

        foreach ($kayitlar as $k) {
            $sorgu->execute([
                'tarih'       => $k['tarih'],
                'corba'       => $k['corba'],
                'ana'         => $k['ana_yemek'],
                'yard'        => $k['yardimci_yemek'],
                'tatli'       => $k['tatli_salata'],
                'corba_kcal'  => $k['corba_kcal'],
                'ana_kcal'    => $k['ana_kcal'],
                'yard_kcal'   => $k['yardimci_kcal'],
                'ek_kcal'     => $k['ek_kcal'],
                'toplam_kcal' => $k['toplam_kcal'],
                'kaynak'      => self::YEMEK_URL,
            ]);

            if ($sorgu->rowCount() === 1) {
                $eklenen++;
            }
        }

        return $eklenen;
    }

    /** @param list<array<string, string|int|null>> $kayitlar */
    private static function yasadisi(array $kayitlar): string
    {
        if ($kayitlar === []) {
            return '-';
        }

        $tarihler = array_map(static fn (array $k): string => (string) $k['tarih'], $kayitlar);
        sort($tarihler);

        return $tarihler[0] . ' ile ' . $tarihler[count($tarihler) - 1];
    }

    /** @return array{ad: string, kcal: ?int} */
    private static function ayikla(string $metin): array
    {
        $metin = trim(preg_replace('/\s+/u', ' ', $metin));
        $kcal  = null;

        if (preg_match('/\((\d+)\s*kkal\)\s*$/iu', $metin, $m)) {
            $kcal  = (int) $m[1];
            $metin = trim(preg_replace('/\(\d+\s*kkal\)\s*$/iu', '', $metin));
        }

        return ['ad' => $metin, 'kcal' => $kcal];
    }

    /** @return ?int */
    private static function kcalAyiklaToplam(string $metin): ?int
    {
        $metin = trim($metin);

        if ($metin === '') {
            return null;
        }

        if (preg_match('/(\d+)\s*KKAL/iu', $metin, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private static function kisalt(string $metin, int $limit): ?string
    {
        $metin = trim($metin);

        if ($metin === '') {
            return null;
        }

        return mb_substr($metin, 0, $limit, 'UTF-8');
    }

    /**
     * Paylaşılan dizeler indeksini okur.
     *
     * @return list<string>
     */
    private static function paylasilanDizeler(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        $sst = [];

        if ($xml === false) {
            return $sst;
        }

        $ss = @simplexml_load_string($xml);
        if ($ss === false) {
            return $sst;
        }

        foreach ($ss->si as $si) {
            $sst[] = (string) $si->t;
        }

        return $sst;
    }

    // ------------------------------------------------------------------
    // Ağ ve durum
    // ------------------------------------------------------------------

    private static function dosyaIndir(string $url, string $uzanti): ?string
    {
        $icerik = self::httpGet($url);

        if ($icerik === null || $icerik === '') {
            return null;
        }

        $gecici = tempnam(sys_get_temp_dir(), 'snk') . $uzanti;

        if (file_put_contents($gecici, $icerik) === false) {
            return null;
        }

        return $gecici;
    }

    private static function httpGet(string $url): ?string
    {
        $baglam = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 30,
                'ignore_errors' => true,
                'header'        => "User-Agent: SanalOkul-Senkron/1.0\r\nAccept: */*\r\n",
            ],
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        $sonuc = @file_get_contents($url, false, $baglam);

        if ($sonuc === false || $sonuc === '') {
            return null;
        }

        // 404 gibi durumlarda gövde dönebiliyor; basit bir sağlama
        $baslangic = substr($sonuc, 0, 200);
        if (str_contains($baslangic, '404 Not Found') && strlen($sonuc) < 500) {
            return null;
        }

        return $sonuc;
    }

    /** @return array{ok: bool, mesaj: string} */
    private static function hataBildir(string $kaynak, string $mesaj): array
    {
        self::kaynakDurumu($kaynak, 'hata', $mesaj);

        return ['ok' => false, 'mesaj' => $mesaj];
    }

    private static function kaynakDurumu(string $kaynak, string $durum, ?string $hata): void
    {
        $pdo = Database::get();

        if ($durum === 'basarili') {
            $pdo->prepare(
                'INSERT INTO kaynak_senkron_durumu (kaynak, son_deneme, son_basari, durum, hata_mesaji)
                 VALUES (:kaynak, NOW(), NOW(), :durum_a, NULL)
                 ON DUPLICATE KEY UPDATE son_deneme = NOW(), son_basari = NOW(), durum = :durum_b,
                                        hata_mesaji = NULL'
            )->execute(['kaynak' => $kaynak, 'durum_a' => $durum, 'durum_b' => $durum]);

            return;
        }

        if ($durum === 'hata') {
            $pdo->prepare(
                'INSERT INTO kaynak_senkron_durumu (kaynak, son_deneme, durum, hata_mesaji)
                 VALUES (:kaynak, NOW(), :durum_a, :hata_a)
                 ON DUPLICATE KEY UPDATE son_deneme = NOW(), durum = :durum_b, hata_mesaji = :hata_b'
            )->execute([
                'kaynak'  => $kaynak,
                'durum_a' => $durum,
                'durum_b' => $durum,
                'hata_a'  => $hata,
                'hata_b'  => $hata,
            ]);

            return;
        }

        $pdo->prepare(
            'INSERT INTO kaynak_senkron_durumu (kaynak, son_deneme, durum, hata_mesaji)
             VALUES (:kaynak, NOW(), :durum_a, NULL)
             ON DUPLICATE KEY UPDATE son_deneme = NOW(), durum = :durum_b, hata_mesaji = NULL'
        )->execute(['kaynak' => $kaynak, 'durum_a' => $durum, 'durum_b' => $durum]);
    }
}
