<?php

declare(strict_types=1);

/**
 * Öğrenci tarafı veri erişimi.
 *
 * Bütün sorgular öğrenci kimliğiyle sınırlandırılır; bir öğrenci başka bir
 * öğrencinin kaydını göremez. Sayfalar bu sınıfı kullanır.
 */
final class Ogrenci
{
    /** Harf notu -> 4'lük sistemdeki puan. */
    public const HARF_PUAN = [
        'AA' => 4.00,
        'BA' => 3.50,
        'BB' => 3.00,
        'CB' => 2.50,
        'CC' => 2.00,
        'DC' => 1.50,
        'DD' => 1.00,
        'FD' => 0.50,
        'FF' => 0.00,
    ];

    private function __construct()
    {
    }

    // ------------------------------------------------------------------
    // Dersler
    // ------------------------------------------------------------------

    /**
     * Öğrencinin kayıtlı olduğu dersler.
     *
     * @param int|null $donemId null ise tüm dönemler
     * @return list<array<string, mixed>>
     */
    public static function dersler(int $ogrenciId, ?int $donemId = null): array
    {
        $sql = <<<'SQL'
            SELECT dk.id AS kayit_id,
                   da.id AS acilis_id,
                   da.sube,
                   d.kod, d.ad, d.kredi, d.akts, d.zorunlu_mu, d.sinif,
                   dn.id AS donem_id, dn.ad AS donem, dn.aktif, dn.baslangic AS donem_baslangic,
                   k.id AS hoca_id, CONCAT(k.ad, ' ', k.soyad) AS hoca,
                   oep.unvan AS hoca_unvan,
                   n.vize, n.`final` AS final_notu, n.ortalama, n.harf_notu,
                   (SELECT COUNT(*) FROM ders_icerikleri di WHERE di.ders_acilis_id = da.id) AS icerik_sayisi,
                   (SELECT COUNT(*) FROM odevler o WHERE o.ders_acilis_id = da.id) AS odev_sayisi,
                   (SELECT COUNT(*) FROM teslimler t
                        JOIN odevler o2 ON o2.id = t.odev_id
                     WHERE t.ogrenci_id = dk.ogrenci_id AND o2.ders_acilis_id = da.id) AS teslim_sayisi
            FROM ders_kayitlari dk
                JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id
                JOIN kullanicilar k ON k.id = da.ogretim_elemani_id
                LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                LEFT JOIN notlar n ON n.ders_kaydi_id = dk.id
            WHERE dk.ogrenci_id = :ogrenci
              AND dk.durum IN ('aktif', 'tamamlandi')
            SQL;

        $parametreler = ['ogrenci' => $ogrenciId];

        if ($donemId !== null) {
            $sql .= ' AND da.donem_id = :donem';
            $parametreler['donem'] = $donemId;
        }

        $sql .= ' ORDER BY dn.baslangic DESC, d.kod';

        $sorgu = Database::get()->prepare($sql);
        $sorgu->execute($parametreler);

        return $sorgu->fetchAll();
    }

    /**
     * Tek dersin ayrıntısı. Kayıt öğrenciye ait değilse null döner.
     *
     * @return array<string, mixed>|null
     */
    public static function ders(int $ogrenciId, int $kayitId): ?array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT dk.id AS kayit_id, dk.durum AS kayit_durumu, dk.kayit_zamani,
                   da.id AS acilis_id, da.sube, da.kontenjan,
                   d.kod, d.ad, d.kredi, d.akts, d.zorunlu_mu, d.sinif,
                   dn.ad AS donem, dn.aktif, dn.baslangic, dn.bitis,
                   k.ad AS hoca_ad, k.soyad AS hoca_soyad, k.eposta AS hoca_eposta,
                   CONCAT(k.ad, ' ', k.soyad) AS hoca,
                   oep.unvan AS hoca_unvan,
                   n.vize, n.`final` AS final_notu, n.ortalama, n.harf_notu, n.guncelleme_zamani,
                   (SELECT COUNT(*) FROM ders_kayitlari x WHERE x.ders_acilis_id = da.id AND x.durum = 'aktif') AS sinif_mesudu
            FROM ders_kayitlari dk
                JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id
                JOIN kullanicilar k ON k.id = da.ogretim_elemani_id
                LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                LEFT JOIN notlar n ON n.ders_kaydi_id = dk.id
            WHERE dk.id = :kayit AND dk.ogrenci_id = :ogrenci
            LIMIT 1
            SQL
        );
        $sorgu->execute(['kayit' => $kayitId, 'ogrenci' => $ogrenciId]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Dersin haftalık programı.
     *
     * @return list<array<string, mixed>>
     */
    public static function dersProgrami(int $acilisId): array
    {
        $sorgu = Database::get()->prepare(
            'SELECT gun, baslangic_saati, bitis_saati, derslik
             FROM ders_programi
             WHERE ders_acilis_id = :acilis
             ORDER BY gun, baslangic_saati'
        );
        $sorgu->execute(['acilis' => $acilisId]);

        return $sorgu->fetchAll();
    }

    /**
     * Dersin haftalık içerikleri.
     *
     * @return list<array<string, mixed>>
     */
    public static function icerikler(int $acilisId): array
    {
        $sorgu = Database::get()->prepare(
            'SELECT id, hafta, baslik, aciklama, dosya_yolu, olusturma_zamani
             FROM ders_icerikleri
             WHERE ders_acilis_id = :acilis
             ORDER BY hafta'
        );
        $sorgu->execute(['acilis' => $acilisId]);

        return $sorgu->fetchAll();
    }

    // ------------------------------------------------------------------
    // Ödevler
    // ------------------------------------------------------------------

    /**
     * Tek dersin ödevleri + öğrencinin teslim durumu.
     *
     * @return list<array<string, mixed>>
     */
    public static function dersOdevleri(int $acilisId, int $ogrenciId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT o.id, o.baslik, o.aciklama, o.son_teslim, o.maks_puan, o.dosya_yolu,
                   t.id AS teslim_id, t.teslim_zamani, t.puan, t.geri_bildirim,
                   t.dosya_yolu AS teslim_dosyasi, t.aciklama AS teslim_aciklamasi
            FROM odevler o
                LEFT JOIN teslimler t ON t.odev_id = o.id AND t.ogrenci_id = :ogrenci
            WHERE o.ders_acilis_id = :acilis
            ORDER BY o.son_teslim
            SQL
        );
        $sorgu->execute(['acilis' => $acilisId, 'ogrenci' => $ogrenciId]);

        return $sorgu->fetchAll();
    }

    /**
     * Öğrencinin tüm derslerindeki ödevler.
     *
     * @return list<array<string, mixed>>
     */
    public static function tumOdevler(int $ogrenciId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT o.id, o.baslik, o.aciklama, o.son_teslim, o.maks_puan,
                   d.kod, d.ad AS ders_adi, dk.id AS kayit_id,
                   t.id AS teslim_id, t.teslim_zamani, t.puan, t.geri_bildirim,
                   t.dosya_yolu AS teslim_dosyasi
            FROM odevler o
                JOIN ders_acilislari da ON da.id = o.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN ders_kayitlari dk ON dk.ders_acilis_id = da.id
                     AND dk.ogrenci_id = :ogrenci1
                     AND dk.durum IN ('aktif', 'tamamlandi')
                LEFT JOIN teslimler t ON t.odev_id = o.id AND t.ogrenci_id = :ogrenci2
            ORDER BY (t.id IS NULL) DESC, o.son_teslim
            SQL
        );
        $sorgu->execute(['ogrenci1' => $ogrenciId, 'ogrenci2' => $ogrenciId]);

        return $sorgu->fetchAll();
    }

    /**
     * Ödev teslim durumunu metne çevirir: bekliyor | teslim | gecti | puanlandi
     */
    public static function odevDurumu(array $odev): string
    {
        if ($odev['teslim_id'] !== null) {
            return $odev['puan'] !== null ? 'puanlandi' : 'teslim';
        }

        return strtotime((string) $odev['son_teslim']) < time() ? 'gecti' : 'bekliyor';
    }

    // ------------------------------------------------------------------
    // Program, notlar, transkript
    // ------------------------------------------------------------------

    /**
     * Aktif dönemdeki haftalık ders programı (öğrencinin dersleri).
     *
     * @return list<array<string, mixed>>
     */
    public static function haftalikProgram(int $ogrenciId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT dp.gun, dp.baslangic_saati, dp.bitis_saati, dp.derslik,
                   d.kod, d.ad AS ders_adi, CONCAT(k.ad, ' ', k.soyad) AS hoca
            FROM ders_programi dp
                JOIN ders_acilislari da ON da.id = dp.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id AND dn.aktif = 1
                JOIN kullanicilar k ON k.id = da.ogretim_elemani_id
                JOIN ders_kayitlari dk ON dk.ders_acilis_id = da.id
                     AND dk.ogrenci_id = :ogrenci
                     AND dk.durum = 'aktif'
            ORDER BY dp.gun, dp.baslangic_saati
            SQL
        );
        $sorgu->execute(['ogrenci' => $ogrenciId]);

        return $sorgu->fetchAll();
    }

    /**
     * Dönem dönem not dökümü.
     *
     * @return list<array{donem:string, aktif:int, baslangic:string, dersler:list<array<string,mixed>>, ortalama:?float, kredi:int, akts:int}>
     */
    public static function notGecmisi(int $ogrenciId): array
    {
        $satirlar = self::dersler($ogrenciId);
        $gruplar = [];

        foreach ($satirlar as $satir) {
            $anahtar = (string) $satir['donem'];

            if (!isset($gruplar[$anahtar])) {
                $gruplar[$anahtar] = [
                    'donem'     => (string) $satir['donem'],
                    'aktif'     => (int) $satir['aktif'],
                    'baslangic' => (string) $satir['donem_baslangic'],
                    'dersler'   => [],
                ];
            }

            $gruplar[$anahtar]['dersler'][] = $satir;
        }

        $sonuc = [];

        foreach ($gruplar as $grup) {
            $ozet = self::donemOzeti($grup['dersler']);
            $grup['ortalama'] = $ozet['ortalama'];
            $grup['kredi']    = $ozet['kredi'];
            $grup['akts']     = $ozet['akts'];
            $sonuc[] = $grup;
        }

        usort($sonuc, static fn (array $a, array $b): int => strcmp($b['baslangic'], $a['baslangic']));

        return $sonuc;
    }

    /**
     * Kredi ağırlıklı dönem ortalaması + kredi/AKTS toplamı.
     *
     * @param list<array<string, mixed>> $dersler
     * @return array{ortalama:?float, kredi:int, akts:int, notlu:int}
     */
    public static function donemOzeti(array $dersler): array
    {
        $kredi = 0;
        $akts  = 0;
        $notlu = 0;
        $toplam = 0.0;

        foreach ($dersler as $ders) {
            if ($ders['ortalama'] === null) {
                continue;
            }

            $kredi += (int) $ders['kredi'];
            $akts  += (int) $ders['akts'];
            $toplam += (float) $ders['ortalama'] * (int) $ders['kredi'];
            $notlu++;
        }

        return [
            'ortalama' => $kredi > 0 ? round($toplam / $kredi, 2) : null,
            'kredi'    => $kredi,
            'akts'     => $akts,
            'notlu'    => $notlu,
        ];
    }

    /**
     * Transkript: notu oluşmuş tüm dersler + GANO.
     *
     * @return array{
     *     satirlar:list<array<string,mixed>>,
     *     gano:?float,
     *     kredi:int,
     *     akts:int,
     *     ders_sayisi:int,
     *     basarili:int,
     *     donem_sayisi:int
     * }
     */
    public static function transkript(int $ogrenciId): array
    {
        $tumu = self::dersler($ogrenciId);
        $satirlar = [];
        $kredi = 0;
        $akts = 0;
        $puanToplam = 0.0;
        $basarili = 0;
        $donemler = [];

        foreach ($tumu as $satir) {
            $puan = self::harfPuani($satir['harf_notu'] ?? null);

            if ($puan === null) {
                continue;
            }

            $satir['puan'] = $puan;
            $satirlar[] = $satir;

            $kredi += (int) $satir['kredi'];
            $akts  += (int) $satir['akts'];
            $puanToplam += $puan * (int) $satir['kredi'];

            if ($puan >= 2.0) {
                $basarili++;
            }

            $donemler[(string) $satir['donem']] = true;
        }

        return [
            'satirlar'    => $satirlar,
            'gano'        => $kredi > 0 ? round($puanToplam / $kredi, 2) : null,
            'kredi'       => $kredi,
            'akts'        => $akts,
            'ders_sayisi' => count($satirlar),
            'basarili'    => $basarili,
            'donem_sayisi' => count($donemler),
        ];
    }

    public static function harfPuani(?string $harf): ?float
    {
        if ($harf === null || $harf === '') {
            return null;
        }

        return self::HARF_PUAN[strtoupper($harf)] ?? null;
    }

    /**
     * Harf notuna göre rozet sınıfı.
     */
    public static function harfSinifi(?string $harf): string
    {
        $puan = self::harfPuani($harf);

        if ($puan === null) {
            return '';
        }

        return match (true) {
            $puan >= 2.5 => 'rozet-basari',
            $puan >= 1.5 => 'rozet-uyari',
            default      => 'rozet-hata',
        };
    }

    // ------------------------------------------------------------------
    // Duyurular
    // ------------------------------------------------------------------

    /**
     * Genel duyurular + öğrencinin derslerine ait duyurular.
     *
     * @return list<array<string, mixed>>
     */
    public static function duyurular(int $ogrenciId, int $limit = 30): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT d.id, d.baslik, d.icerik, d.onemli, d.yayin_tarihi, d.bitis_tarihi,
                   CASE WHEN d.ders_acilis_id IS NULL THEN 'Genel'
                        ELSE CONCAT(dr.kod, ' · ', dr.ad) END AS kapsam,
                   d.ders_acilis_id,
                   CONCAT(k.ad, ' ', k.soyad) AS yazar,
                   oep.unvan AS yazar_unvan
            FROM duyurular d
                JOIN kullanicilar k ON k.id = d.yazar_id
                LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                LEFT JOIN ders_acilislari da ON da.id = d.ders_acilis_id
                LEFT JOIN dersler dr ON dr.id = da.ders_id
            WHERE (d.bitis_tarihi IS NULL OR d.bitis_tarihi >= NOW())
              AND (
                    d.ders_acilis_id IS NULL
                    OR EXISTS (
                        SELECT 1 FROM ders_kayitlari dk
                        WHERE dk.ders_acilis_id = d.ders_acilis_id
                          AND dk.ogrenci_id = :ogrenci
                          AND dk.durum IN ('aktif', 'tamamlandi')
                    )
                  )
            ORDER BY d.onemli DESC, d.yayin_tarihi DESC
            LIMIT :limit
            SQL
        );
        $sorgu->bindValue('ogrenci', $ogrenciId, PDO::PARAM_INT);
        $sorgu->bindValue('limit', $limit, PDO::PARAM_INT);
        $sorgu->execute();

        return $sorgu->fetchAll();
    }

    // ------------------------------------------------------------------
    // Sunum yardımcıları
    // ------------------------------------------------------------------

    public const GUNLER = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
    ];

    /**
     * "2026-11-15 23:59:00" -> kalan süre metni.
     */
    public static function kalanSure(?string $tarih): array
    {
        if ($tarih === null || $tarih === '') {
            return ['metin' => '-', 'sinif' => ''];
        }

        $zaman = strtotime($tarih);

        if ($zaman === false) {
            return ['metin' => '-', 'sinif' => ''];
        }

        $fark = $zaman - time();

        if ($fark < 0) {
            $gun = (int) floor(abs($fark) / 86400);

            return [
                'metin' => $gun > 0 ? $gun . ' gün önce doldu' : 'Süresi doldu',
                'sinif' => 'rozet-hata',
            ];
        }

        $gun  = (int) floor($fark / 86400);
        $saat = (int) floor(($fark % 86400) / 3600);

        if ($gun > 0) {
            return ['metin' => $gun . ' gün kaldı', 'sinif' => $gun <= 3 ? 'rozet-uyari' : 'rozet-basari'];
        }

        return [
            'metin' => max(1, $saat) . ' saat kaldı',
            'sinif' => 'rozet-uyari',
        ];
    }
}
