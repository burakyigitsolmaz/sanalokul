<?php

declare(strict_types=1);

/**
 * Raporlama ve istatistik veri katmanı (Faz 6).
 *
 * Sorgular:
 *   - v_ders_doluluk          ders açılışı + kontenjan/doluluk
 *   - v_ogretim_elemani_yuku  hoca bazlı dönem yükü
 *   - v_donem_ozet            dönem toplu göstergeleri
 *   - v_harf_notu_dagilimi    harf notu dağılımı
 *   - v_bolum_ozet            bölüm bazlı özet
 *   - sp_donem_raporu         ders bazlı dönem raporu (result set)
 *   - sp_riskli_ogrenciler    akademik risk listesi (result set)
 */
final class Rapor
{
    /** Risk listesi eşiği: ortalaması bunun altında ya da başarısız dersi olanlar. */
    public const RISK_ESIGI = 70.0;

    /**
     * Tüm dönemler (yeni → eski).
     *
     * @return list<array<string, mixed>>
     */
    public static function donemler(): array
    {
        return Database::get()->query(
            'SELECT id, ad, baslangic, bitis, aktif FROM donemler ORDER BY baslangic DESC'
        )->fetchAll();
    }

    /**
     * İstenen dönemi doğrular; geçersizse aktif döneme, o da yoksa en yeniye düşer.
     */
    public static function donemSec(?int $istenen): int
    {
        $pdo = Database::get();

        if ($istenen !== null && $istenen > 0) {
            $kontrol = $pdo->prepare('SELECT id FROM donemler WHERE id = :id');
            $kontrol->execute(['id' => $istenen]);

            if ($kontrol->fetchColumn() !== false) {
                return $istenen;
            }
        }

        $id = $pdo->query('SELECT id FROM donemler WHERE aktif = 1 LIMIT 1')->fetchColumn();

        if ($id === false) {
            $id = $pdo->query('SELECT id FROM donemler ORDER BY baslangic DESC LIMIT 1')->fetchColumn();
        }

        return $id === false ? 0 : (int) $id;
    }

    /**
     * Tek dönem kaydı.
     *
     * @return array<string, mixed>|null
     */
    public static function donem(int $donemId): ?array
    {
        $sorgu = Database::get()->prepare(
            'SELECT id, ad, baslangic, bitis, aktif FROM donemler WHERE id = :id'
        );
        $sorgu->execute(['id' => $donemId]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Dönem toplu göstergeleri + türetilmiş oranlar.
     *
     * @return array<string, mixed>
     */
    public static function ozet(int $donemId): array
    {
        $sorgu = Database::get()->prepare('SELECT * FROM v_donem_ozet WHERE donem_id = :id');
        $sorgu->execute(['id' => $donemId]);
        $satir = $sorgu->fetch();

        if ($satir === false) {
            $satir = [
                'donem_id'         => $donemId,
                'donem'            => '-',
                'aktif'            => 0,
                'ders_acilisi'     => 0,
                'farkli_ders'      => 0,
                'kayitli_ogrenci'  => 0,
                'toplam_kayit'     => 0,
                'notu_girilen'     => 0,
                'ortalama'         => null,
                'gecen'            => 0,
                'kalan'            => 0,
                'basari_yuzdesi'   => null,
            ];
        }

        $toplamKayit = (int) $satir['toplam_kayit'];
        $notuGirilen = (int) $satir['notu_girilen'];

        $satir['not_girme_orani'] = $toplamKayit > 0
            ? round(100 * $notuGirilen / $toplamKayit, 1)
            : null;

        return $satir;
    }

    /**
     * Ders bazlı dönem raporu (sp_donem_raporu).
     *
     * @return list<array<string, mixed>>
     */
    public static function dersRaporu(int $donemId): array
    {
        $pdo = Database::get();

        $sorgu = $pdo->prepare('CALL sp_donem_raporu(:donem)');
        $sorgu->execute(['donem' => $donemId]);
        $satirlar = $sorgu->fetchAll();
        $sorgu->closeCursor();

        return $satirlar;
    }

    /**
     * Hoca bazlı dönem yükü + haftalık ders saati (ders programından).
     *
     * @return list<array<string, mixed>>
     */
    public static function hocaYuku(int $donemId): array
    {
        $sorgu = Database::get()->prepare(
            'SELECT y.ogretim_elemani_id, y.ogretim_elemani, y.unvan, y.bolum,
                    y.ders_acilisi, y.farkli_ders, y.ogrenci, y.notu_girilen, y.ortalama,
                    COALESCE((
                        SELECT SUM(TIME_TO_SEC(TIMEDIFF(dp.bitis_saati, dp.baslangic_saati)) / 3600)
                        FROM ders_acilislari da2
                            JOIN ders_programi dp ON dp.ders_acilis_id = da2.id
                        WHERE da2.ogretim_elemani_id = y.ogretim_elemani_id
                          AND da2.donem_id = y.donem_id
                    ), 0) AS haftalik_saat
             FROM v_ogretim_elemani_yuku y
             WHERE y.donem_id = :donem
             ORDER BY ogrenci DESC, ogretim_elemani'
        );
        $sorgu->execute(['donem' => $donemId]);

        return $sorgu->fetchAll();
    }

    /**
     * Harf notu dağılımı (adet büyükten küçüğe değil, harf sırasına göre).
     *
     * @return list<array<string, mixed>>
     */
    public static function harfDagilimi(int $donemId): array
    {
        $sorgu = Database::get()->prepare(
            'SELECT harf_notu, adet FROM v_harf_notu_dagilimi
             WHERE donem_id = :donem
             ORDER BY FIELD(harf_notu, "AA", "BA", "BB", "CB", "CC", "DC", "DD", "FD", "FF", "-")'
        );
        $sorgu->execute(['donem' => $donemId]);

        return $sorgu->fetchAll();
    }

    /**
     * Bölüm bazlı genel özet (tüm dönemler).
     *
     * @return list<array<string, mixed>>
     */
    public static function bolumOzeti(): array
    {
        return Database::get()->query(
            'SELECT * FROM v_bolum_ozet ORDER BY ogrenci DESC, bolum'
        )->fetchAll();
    }

    /**
     * Akademik risk listesi (sp_riskli_ogrenciler).
     *
     * @return list<array<string, mixed>>
     */
    public static function riskliOgrenciler(int $donemId, float $esik = self::RISK_ESIGI): array
    {
        $pdo = Database::get();

        $sorgu = $pdo->prepare('CALL sp_riskli_ogrenciler(:donem, :esik)');
        $sorgu->execute(['donem' => $donemId, 'esik' => $esik]);
        $satirlar = $sorgu->fetchAll();
        $sorgu->closeCursor();

        return $satirlar;
    }

    /**
     * CSV çıktısı için ders raporu satırları (biçimlendirilmiş).
     *
     * @return array{basliklar: list<string>, satirlar: list<list<string>>}
     */
    public static function csvDersRaporu(int $donemId): array
    {
        $basliklar = [
            'Ders Kodu', 'Ders Adı', 'Şube', 'Öğretim Elemanı',
            'Kontenjan', 'Kayıtlı', 'Doluluk %',
            'Notu Girilen', 'Ortalama', 'Geçen', 'Kalan', 'Başarı %',
        ];

        $satirlar = [];

        foreach (self::dersRaporu($donemId) as $d) {
            $satirlar[] = [
                (string) $d['ders_kodu'],
                (string) $d['ders_adi'],
                (string) $d['sube'],
                (string) $d['ogretim_elemani'],
                $d['kontenjan'] === null ? 'Sınırsız' : (string) $d['kontenjan'],
                (string) $d['kayitli'],
                self::ondalik($d['doluluk_yuzdesi'], 1),
                (string) $d['notu_girilen'],
                self::ondalik($d['ortalama'], 2),
                (string) $d['gecen'],
                (string) $d['kalan'],
                self::ondalik($d['basari_yuzdesi'], 1),
            ];
        }

        return ['basliklar' => $basliklar, 'satirlar' => $satirlar];
    }

    /**
     * Türkçe ondalık biçimi (CSV ve ekran için). NULL → "-".
     */
    public static function ondalik(mixed $deger, int $basamak = 1): string
    {
        if ($deger === null || $deger === '') {
            return '-';
        }

        return number_format((float) $deger, $basamak, ',', '.');
    }

    /**
     * Yüzde rozet sınıfı.
     */
    public static function yuzdeSinifi(mixed $yuzde): string
    {
        if ($yuzde === null) {
            return 'rozet';
        }

        $deger = (float) $yuzde;

        if ($deger >= 70) {
            return 'rozet rozet-basari';
        }

        return $deger >= 40 ? 'rozet rozet-uyari' : 'rozet rozet-hata';
    }
}
