<?php

declare(strict_types=1);

/**
 * Öğretim elemanı tarafı veri erişimi.
 *
 * Bütün sorgular öğretim elemanı kimliğiyle sınırlandırılır. Bir hoca yalnızca
 * kendi ders açılışlarına, öğrencilerine, ödevlerine ve duyurularına erişebilir.
 */
final class Ogretmen
{
    private function __construct()
    {
    }

    // ------------------------------------------------------------------
    // Dersler
    // ------------------------------------------------------------------

    /**
     * Hocanın verdiği ders açılışları (tüm dönemler).
     *
     * @return list<array<string, mixed>>
     */
    public static function dersler(int $hocaId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT da.id AS acilis_id, da.sube, da.kontenjan,
                   d.kod, d.ad, d.kredi, d.akts, d.sinif, d.zorunlu_mu,
                   dn.id AS donem_id, dn.ad AS donem, dn.aktif, dn.baslangic,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = da.id AND dk.durum = 'aktif') AS ogrenci_sayisi,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                        JOIN notlar n ON n.ders_kaydi_id = dk.id
                     WHERE dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
                       AND n.ortalama IS NOT NULL) AS notlu_sayisi,
                   (SELECT COUNT(*) FROM odevler o WHERE o.ders_acilis_id = da.id) AS odev_sayisi,
                   (SELECT COUNT(*) FROM teslimler t
                        JOIN odevler o2 ON o2.id = t.odev_id
                     WHERE o2.ders_acilis_id = da.id AND t.puan IS NULL) AS bekleyen_teslim,
                   (SELECT COUNT(*) FROM ders_icerikleri di WHERE di.ders_acilis_id = da.id) AS icerik_sayisi,
                   (SELECT COUNT(*) FROM ders_programi dp WHERE dp.ders_acilis_id = da.id) AS oturum_sayisi
            FROM ders_acilislari da
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id
            WHERE da.ogretim_elemani_id = :hoca
            ORDER BY dn.aktif DESC, dn.baslangic DESC, d.kod
            SQL
        );
        $sorgu->execute(['hoca' => $hocaId]);

        return $sorgu->fetchAll();
    }

    /**
     * Tek ders açılışı. Hocanın dersi değilse null.
     *
     * @return array<string, mixed>|null
     */
    public static function ders(int $hocaId, int $acilisId): ?array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT da.id AS acilis_id, da.sube, da.kontenjan,
                   d.kod, d.ad, d.kredi, d.akts, d.sinif, d.zorunlu_mu,
                   dn.id AS donem_id, dn.ad AS donem, dn.aktif, dn.baslangic, dn.bitis,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = da.id AND dk.durum = 'aktif') AS ogrenci_sayisi,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                        JOIN notlar n ON n.ders_kaydi_id = dk.id
                     WHERE dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
                       AND n.ortalama IS NOT NULL) AS notlu_sayisi,
                   (SELECT COUNT(*) FROM odevler o WHERE o.ders_acilis_id = da.id) AS odev_sayisi,
                   (SELECT COUNT(*) FROM teslimler t
                        JOIN odevler o2 ON o2.id = t.odev_id
                     WHERE o2.ders_acilis_id = da.id AND t.puan IS NULL) AS bekleyen_teslim,
                   (SELECT COUNT(*) FROM ders_icerikleri di WHERE di.ders_acilis_id = da.id) AS icerik_sayisi,
                   (SELECT COUNT(*) FROM ders_programi dp WHERE dp.ders_acilis_id = da.id) AS oturum_sayisi
            FROM ders_acilislari da
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id
            WHERE da.id = :acilis AND da.ogretim_elemani_id = :hoca
            LIMIT 1
            SQL
        );
        $sorgu->execute(['acilis' => $acilisId, 'hoca' => $hocaId]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Dersin öğrenci listesi + notları ve teslim sayıları.
     *
     * @return list<array<string, mixed>>
     */
    public static function ogrenciler(int $acilisId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT dk.id AS kayit_id, dk.durum, dk.kayit_zamani,
                   k.id AS ogrenci_id, k.numara,
                   CONCAT(k.ad, ' ', k.soyad) AS ad_soyad,
                   op.sinif,
                   n.vize, n.`final` AS final_notu, n.ortalama, n.harf_notu, n.guncelleme_zamani,
                   (SELECT COUNT(*) FROM teslimler t
                        JOIN odevler o ON o.id = t.odev_id
                     WHERE o.ders_acilis_id = dk.ders_acilis_id
                       AND t.ogrenci_id = dk.ogrenci_id) AS teslim_sayisi,
                   (SELECT COUNT(*) FROM odevler o
                     WHERE o.ders_acilis_id = dk.ders_acilis_id) AS odev_sayisi
            FROM ders_kayitlari dk
                JOIN kullanicilar k ON k.id = dk.ogrenci_id
                LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
                LEFT JOIN notlar n ON n.ders_kaydi_id = dk.id
            WHERE dk.ders_acilis_id = :acilis AND dk.durum = 'aktif'
            ORDER BY k.soyad, k.ad
            SQL
        );
        $sorgu->execute(['acilis' => $acilisId]);

        return $sorgu->fetchAll();
    }

    /**
     * Vize/final notunu kaydeder veya günceller.
     * Ortalama ve harf notu veritabanı trigger'ı tarafından hesaplanır.
     *
     * @return bool Ders kaydı hocaya ait değilse false
     */
    public static function notKaydet(int $hocaId, int $kayitId, ?float $vize, ?float $final): bool
    {
        $kontrol = Database::get()->prepare(
            'SELECT dk.id
             FROM ders_kayitlari dk
                 JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
             WHERE dk.id = :kayit AND da.ogretim_elemani_id = :hoca
             LIMIT 1'
        );
        $kontrol->execute(['kayit' => $kayitId, 'hoca' => $hocaId]);

        if ($kontrol->fetch() === false) {
            return false;
        }

        self::oturumDegiskenleri();

        $sorgu = Database::get()->prepare(
            'INSERT INTO notlar (ders_kaydi_id, vize, `final`)
             VALUES (:kayit, :vize, :final)
             ON DUPLICATE KEY UPDATE
                 vize = VALUES(vize),
                 `final` = VALUES(`final`)'
        );
        $sorgu->execute([
            'kayit' => $kayitId,
            'vize'  => $vize,
            'final' => $final,
        ]);

        return true;
    }

    // ------------------------------------------------------------------
    // Ödevler
    // ------------------------------------------------------------------

    /**
     * Tek dersin ödevleri + teslim istatistikleri.
     *
     * @return list<array<string, mixed>>
     */
    public static function dersOdevleri(int $acilisId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT o.id, o.baslik, o.aciklama, o.son_teslim, o.maks_puan, o.olusturma_zamani,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = o.ders_acilis_id AND dk.durum = 'aktif') AS ogrenci_sayisi,
                   (SELECT COUNT(*) FROM teslimler t WHERE t.odev_id = o.id) AS teslim_sayisi,
                   (SELECT COUNT(*) FROM teslimler t WHERE t.odev_id = o.id AND t.puan IS NULL) AS bekleyen,
                   (SELECT ROUND(AVG(t.puan), 1) FROM teslimler t
                     WHERE t.odev_id = o.id AND t.puan IS NOT NULL) AS ortalama_puan
            FROM odevler o
            WHERE o.ders_acilis_id = :acilis
            ORDER BY o.son_teslim DESC
            SQL
        );
        $sorgu->execute(['acilis' => $acilisId]);

        return $sorgu->fetchAll();
    }

    /**
     * Hocanın tüm derslerindeki ödevler.
     *
     * @return list<array<string, mixed>>
     */
    public static function tumOdevler(int $hocaId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT o.id, o.baslik, o.son_teslim, o.maks_puan,
                   d.kod, d.ad AS ders_adi, da.id AS acilis_id, da.sube,
                   dn.ad AS donem, dn.aktif,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = o.ders_acilis_id AND dk.durum = 'aktif') AS ogrenci_sayisi,
                   (SELECT COUNT(*) FROM teslimler t WHERE t.odev_id = o.id) AS teslim_sayisi,
                   (SELECT COUNT(*) FROM teslimler t WHERE t.odev_id = o.id AND t.puan IS NULL) AS bekleyen
            FROM odevler o
                JOIN ders_acilislari da ON da.id = o.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id
            WHERE da.ogretim_elemani_id = :hoca
            ORDER BY dn.aktif DESC, o.son_teslim DESC
            SQL
        );
        $sorgu->execute(['hoca' => $hocaId]);

        return $sorgu->fetchAll();
    }

    /**
     * Tek ödev. Hocanın dersi değilse null.
     *
     * @return array<string, mixed>|null
     */
    public static function odev(int $hocaId, int $odevId): ?array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT o.id, o.ders_acilis_id, o.baslik, o.aciklama, o.son_teslim, o.maks_puan,
                   d.kod, d.ad AS ders_adi, d.sinif, da.sube,
                   dn.ad AS donem,
                   (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = o.ders_acilis_id AND dk.durum = 'aktif') AS ogrenci_sayisi,
                   (SELECT COUNT(*) FROM teslimler t WHERE t.odev_id = o.id) AS teslim_sayisi,
                   (SELECT COUNT(*) FROM teslimler t WHERE t.odev_id = o.id AND t.puan IS NULL) AS bekleyen,
                   (SELECT ROUND(AVG(t.puan), 1) FROM teslimler t
                     WHERE t.odev_id = o.id AND t.puan IS NOT NULL) AS ortalama_puan
            FROM odevler o
                JOIN ders_acilislari da ON da.id = o.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id
            WHERE o.id = :odev AND da.ogretim_elemani_id = :hoca
            LIMIT 1
            SQL
        );
        $sorgu->execute(['odev' => $odevId, 'hoca' => $hocaId]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Ödev ekler. Ders hocanın değilse false.
     */
    public static function odevEkle(
        int $hocaId,
        int $acilisId,
        string $baslik,
        string $aciklama,
        string $sonTeslim,
        int $maksPuan
    ): bool {
        if (self::ders($hocaId, $acilisId) === null) {
            return false;
        }

        $sorgu = Database::get()->prepare(
            'INSERT INTO odevler (ders_acilis_id, baslik, aciklama, son_teslim, maks_puan)
             VALUES (:acilis, :baslik, :aciklama, :son, :maks)'
        );
        $sorgu->execute([
            'acilis'   => $acilisId,
            'baslik'   => $baslik,
            'aciklama' => $aciklama,
            'son'      => $sonTeslim,
            'maks'     => $maksPuan,
        ]);

        return true;
    }

    /**
     * Ödevi ve dosyalarını siler. Hocanın değilse false.
     */
    public static function odevSil(int $hocaId, int $odevId): bool
    {
        $odev = self::odev($hocaId, $odevId);

        if ($odev === null) {
            return false;
        }

        $dosyalar = Database::get()->prepare('SELECT dosya_yolu FROM teslimler WHERE odev_id = :odev');
        $dosyalar->execute(['odev' => $odevId]);

        foreach ($dosyalar->fetchAll() as $satir) {
            self::dosyaSil((string) $satir['dosya_yolu']);
        }

        $sorgu = Database::get()->prepare('DELETE FROM odevler WHERE id = :odev');
        $sorgu->execute(['odev' => $odevId]);

        return true;
    }

    /**
     * Ödeve ait teslim listesi (teslim etmeyenler dahil).
     *
     * @return list<array<string, mixed>>
     */
    public static function teslimler(int $acilisId, int $odevId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT dk.ogrenci_id, k.numara, CONCAT(k.ad, ' ', k.soyad) AS ad_soyad, op.sinif,
                   t.id AS teslim_id, t.dosya_yolu, t.aciklama AS teslim_aciklamasi,
                   t.teslim_zamani, t.puan, t.geri_bildirim, t.degerlendirme_zamani
            FROM ders_kayitlari dk
                JOIN kullanicilar k ON k.id = dk.ogrenci_id
                LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
                LEFT JOIN teslimler t ON t.odev_id = :odev AND t.ogrenci_id = dk.ogrenci_id
            WHERE dk.ders_acilis_id = :acilis AND dk.durum = 'aktif'
            ORDER BY (t.id IS NULL), k.soyad, k.ad
            SQL
        );
        $sorgu->execute(['odev' => $odevId, 'acilis' => $acilisId]);

        return $sorgu->fetchAll();
    }

    /**
     * Teslimi puanlar ve geri bildirim yazar.
     *
     * @return bool Teslim hocanın dersine ait değilse veya puan aralık dışıysa false
     */
    public static function degerlendir(int $hocaId, int $teslimId, int $puan, ?string $geriBildirim): bool
    {
        $sorgu = Database::get()->prepare(
            'SELECT t.id, o.maks_puan
             FROM teslimler t
                 JOIN odevler o ON o.id = t.odev_id
                 JOIN ders_acilislari da ON da.id = o.ders_acilis_id
             WHERE t.id = :teslim AND da.ogretim_elemani_id = :hoca
             LIMIT 1'
        );
        $sorgu->execute(['teslim' => $teslimId, 'hoca' => $hocaId]);
        $satir = $sorgu->fetch();

        if ($satir === false || $puan < 0 || $puan > (int) $satir['maks_puan']) {
            return false;
        }

        self::oturumDegiskenleri();

        $guncelle = Database::get()->prepare(
            'UPDATE teslimler
             SET puan = :puan, geri_bildirim = :geri, degerlendirme_zamani = NOW()
             WHERE id = :teslim'
        );
        $guncelle->execute([
            'puan'   => $puan,
            'geri'   => $geriBildirim === null || $geriBildirim === '' ? null : $geriBildirim,
            'teslim' => $teslimId,
        ]);

        return true;
    }

    /**
     * Hocanın tüm derslerinde değerlendirme bekleyen son teslimler.
     *
     * @return list<array<string, mixed>>
     */
    public static function bekleyenTeslimler(int $hocaId, int $limit = 8): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT t.id AS teslim_id, t.teslim_zamani, t.dosya_yolu,
                   o.id AS odev_id, o.baslik AS odev_baslik, o.maks_puan,
                   d.kod, d.ad AS ders_adi,
                   k.numara, CONCAT(k.ad, ' ', k.soyad) AS ad_soyad
            FROM teslimler t
                JOIN odevler o ON o.id = t.odev_id
                JOIN ders_acilislari da ON da.id = o.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN kullanicilar k ON k.id = t.ogrenci_id
            WHERE da.ogretim_elemani_id = :hoca AND t.puan IS NULL
            ORDER BY t.teslim_zamani
            LIMIT :limit
            SQL
        );
        $sorgu->bindValue('hoca', $hocaId, PDO::PARAM_INT);
        $sorgu->bindValue('limit', $limit, PDO::PARAM_INT);
        $sorgu->execute();

        return $sorgu->fetchAll();
    }

    /**
     * Hocanın erişebildiği tek teslim (dosya indirme için).
     *
     * @return array<string, mixed>|null
     */
    public static function teslim(int $hocaId, int $teslimId): ?array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT t.id AS teslim_id, t.ogrenci_id, t.dosya_yolu, t.teslim_zamani, t.puan,
                   o.baslik AS odev_baslik, d.kod,
                   k.numara, CONCAT(k.ad, ' ', k.soyad) AS ad_soyad
            FROM teslimler t
                JOIN odevler o ON o.id = t.odev_id
                JOIN ders_acilislari da ON da.id = o.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN kullanicilar k ON k.id = t.ogrenci_id
            WHERE t.id = :teslim AND da.ogretim_elemani_id = :hoca
            LIMIT 1
            SQL
        );
        $sorgu->execute(['teslim' => $teslimId, 'hoca' => $hocaId]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    // ------------------------------------------------------------------
    // Ders içerikleri
    // ------------------------------------------------------------------

    /**
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

    /**
     * Aynı hafta varsa günceller.
     */
    public static function icerikEkle(
        int $hocaId,
        int $acilisId,
        int $hafta,
        string $baslik,
        ?string $aciklama
    ): bool {
        if (self::ders($hocaId, $acilisId) === null) {
            return false;
        }

        $sorgu = Database::get()->prepare(
            'INSERT INTO ders_icerikleri (ders_acilis_id, hafta, baslik, aciklama)
             VALUES (:acilis, :hafta, :baslik, :aciklama)
             ON DUPLICATE KEY UPDATE
                 baslik = VALUES(baslik),
                 aciklama = VALUES(aciklama)'
        );
        $sorgu->execute([
            'acilis'   => $acilisId,
            'hafta'    => $hafta,
            'baslik'   => $baslik,
            'aciklama' => $aciklama,
        ]);

        return true;
    }

    public static function icerikSil(int $hocaId, int $icerikId): bool
    {
        $sorgu = Database::get()->prepare(
            'DELETE di
             FROM ders_icerikleri di
                 JOIN ders_acilislari da ON da.id = di.ders_acilis_id
             WHERE di.id = :icerik AND da.ogretim_elemani_id = :hoca'
        );
        $sorgu->execute(['icerik' => $icerikId, 'hoca' => $hocaId]);

        return $sorgu->rowCount() > 0;
    }

    // ------------------------------------------------------------------
    // Duyurular
    // ------------------------------------------------------------------

    /**
     * Hocanın derslerine ait duyurular.
     *
     * @return list<array<string, mixed>>
     */
    public static function duyurular(int $hocaId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT d.id, d.baslik, d.icerik, d.onemli, d.yayin_tarihi, d.bitis_tarihi,
                   d.ders_acilis_id,
                   dr.kod, dr.ad AS ders_adi, da.sube
            FROM duyurular d
                JOIN ders_acilislari da ON da.id = d.ders_acilis_id
                JOIN dersler dr ON dr.id = da.ders_id
            WHERE da.ogretim_elemani_id = :hoca
            ORDER BY d.yayin_tarihi DESC
            SQL
        );
        $sorgu->execute(['hoca' => $hocaId]);

        return $sorgu->fetchAll();
    }

    /**
     * Ders duyurusu ekler. Ders hocanın değilse false.
     */
    public static function duyuruEkle(
        int $hocaId,
        int $acilisId,
        string $baslik,
        string $icerik,
        bool $onemli,
        ?string $bitisTarihi
    ): bool {
        if (self::ders($hocaId, $acilisId) === null) {
            return false;
        }

        $sorgu = Database::get()->prepare(
            'INSERT INTO duyurular (ders_acilis_id, yazar_id, baslik, icerik, onemli, bitis_tarihi)
             VALUES (:acilis, :yazar, :baslik, :icerik, :onemli, :bitis)'
        );
        $sorgu->execute([
            'acilis' => $acilisId,
            'yazar'  => $hocaId,
            'baslik' => $baslik,
            'icerik' => $icerik,
            'onemli' => $onemli ? 1 : 0,
            'bitis'  => $bitisTarihi,
        ]);

        return true;
    }

    public static function duyuruSil(int $hocaId, int $duyuruId): bool
    {
        $sorgu = Database::get()->prepare(
            'DELETE d
             FROM duyurular d
                 JOIN ders_acilislari da ON da.id = d.ders_acilis_id
             WHERE d.id = :duyuru AND da.ogretim_elemani_id = :hoca'
        );
        $sorgu->execute(['duyuru' => $duyuruId, 'hoca' => $hocaId]);

        return $sorgu->rowCount() > 0;
    }

    // ------------------------------------------------------------------
    // Program
    // ------------------------------------------------------------------

    /**
     * Hocanın ders programı (aktif dönem).
     *
     * @return list<array<string, mixed>>
     */
    public static function program(int $hocaId): array
    {
        $sorgu = Database::get()->prepare(
            <<<'SQL'
            SELECT dp.gun, dp.baslangic_saati, dp.bitis_saati, dp.derslik,
                   d.kod, d.ad AS ders_adi, da.sube, dn.ad AS donem
            FROM ders_programi dp
                JOIN ders_acilislari da ON da.id = dp.ders_acilis_id
                JOIN dersler d ON d.id = da.ders_id
                JOIN donemler dn ON dn.id = da.donem_id AND dn.aktif = 1
            WHERE da.ogretim_elemani_id = :hoca
            ORDER BY dp.gun, dp.baslangic_saati
            SQL
        );
        $sorgu->execute(['hoca' => $hocaId]);

        return $sorgu->fetchAll();
    }

    // ------------------------------------------------------------------
    // Yardımcılar
    // ------------------------------------------------------------------

    public const GUNLER = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
    ];

    /**
     * Not girilmiş mi? (vize veya final)
     */
    public static function notGirilmis(array $ogrenci): bool
    {
        return $ogrenci['vize'] !== null || $ogrenci['final_notu'] !== null;
    }

    /**
     * Teslimin geç kalıp kalmadığını metne çevirir.
     *
     * @return array{metin:string, sinif:string}
     */
    public static function teslimDurumu(?string $teslimZamani, string $sonTeslim): array
    {
        if ($teslimZamani === null) {
            return strtotime($sonTeslim) < time()
                ? ['metin' => 'Teslim etmedi', 'sinif' => 'rozet-hata']
                : ['metin' => 'Bekliyor', 'sinif' => 'rozet-uyari'];
        }

        $gec = strtotime($teslimZamani) > strtotime($sonTeslim);

        return $gec
            ? ['metin' => 'Geç teslim', 'sinif' => 'rozet-uyari']
            : ['metin' => 'Zamanında', 'sinif' => 'rozet-basari'];
    }

    /**
     * MySQL trigger'ının log yazabilmesi için oturum değişkenlerini set eder.
     */
    private static function oturumDegiskenleri(): void
    {
        try {
            $sorgu = Database::get()->prepare('SET @oturum_kullanici_id = :id, @oturum_ip = :ip');
            $sorgu->execute([
                'id' => Auth::girisYapmisMi() ? (int) $_SESSION['kullanici_id'] : 0,
                'ip' => Auth::ip(),
            ]);
        } catch (Throwable) {
            // Log değişkenleri yazılamazsa işlem devam etsin.
        }
    }

    /**
     * uploads/ altındaki bir dosyayı diskten siler (yol doğrulamalı).
     */
    private static function dosyaSil(string $goreliYol): void
    {
        $kok = rtrim((string) config('yukleme.klasor', ''), '/\\');

        if ($kok === '' || $goreliYol === '') {
            return;
        }

        $tam = $kok . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $goreliYol);

        if (str_starts_with($goreliYol, 'uploads/') && is_file($tam)) {
            @unlink($tam);
        }
    }
}
