-- =====================================================================
-- Nallıhan MYO Sanal Okul - Raporlama nesneleri (VIEW + PROCEDURE)
-- Dosya : 07_rapor.sql
-- Faz   : 6 - Raporlama ve istatistik paneli
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1) v_ders_doluluk
--    Ders açılışı bazında kontenjan, kayıtlı öğrenci ve doluluk oranı.
--    Yönetici raporlarındaki "Doluluk" sütununu besler.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ders_doluluk AS
SELECT
    da.id                                    AS ders_acilis_id,
    da.donem_id                              AS donem_id,
    dn.ad                                    AS donem,
    d.id                                     AS ders_id,
    d.kod                                    AS ders_kodu,
    d.ad                                     AS ders_adi,
    d.sinif                                  AS sinif,
    b.id                                     AS bolum_id,
    b.kod                                    AS bolum_kodu,
    b.ad                                     AS bolum,
    da.sube                                  AS sube,
    CONCAT(h.ad, ' ', h.soyad)               AS ogretim_elemani,
    da.kontenjan                             AS kontenjan,
    COUNT(dk.id)                             AS kayitli,
    CASE
        WHEN da.kontenjan IS NULL OR da.kontenjan = 0 THEN NULL
        ELSE ROUND(100 * COUNT(dk.id) / da.kontenjan, 1)
    END                                      AS doluluk_yuzdesi
FROM ders_acilislari da
    JOIN dersler d       ON d.id = da.ders_id
    JOIN donemler dn     ON dn.id = da.donem_id
    JOIN bolumler b      ON b.id = d.bolum_id
    JOIN kullanicilar h  ON h.id = da.ogretim_elemani_id
    LEFT JOIN ders_kayitlari dk
        ON dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
GROUP BY
    da.id, da.donem_id, dn.ad,
    d.id, d.kod, d.ad, d.sinif,
    b.id, b.kod, b.ad,
    da.sube, h.ad, h.soyad, da.kontenjan;

-- ---------------------------------------------------------------------
-- 2) v_ogretim_elemani_yuku
--    Öğretim elemanı bazında dönem yükü: açılan ders, kayıtlı öğrenci,
--    not girme ve başarı durumu.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ogretim_elemani_yuku AS
SELECT
    h.id                                     AS ogretim_elemani_id,
    CONCAT(h.ad, ' ', h.soyad)               AS ogretim_elemani,
    COALESCE(pr.unvan, '')                   AS unvan,
    COALESCE(b.ad, '-')                      AS bolum,
    da.donem_id                              AS donem_id,
    dn.ad                                    AS donem,
    COUNT(DISTINCT da.id)                    AS ders_acilisi,
    COUNT(DISTINCT da.ders_id)               AS farkli_ders,
    COUNT(DISTINCT dk.ogrenci_id)            AS ogrenci,
    SUM(CASE WHEN n.ortalama IS NOT NULL THEN 1 ELSE 0 END) AS notu_girilen,
    ROUND(AVG(n.ortalama), 2)                AS ortalama
FROM ders_acilislari da
    JOIN kullanicilar h   ON h.id = da.ogretim_elemani_id
    JOIN donemler dn      ON dn.id = da.donem_id
    LEFT JOIN ogretim_elemani_profilleri pr ON pr.kullanici_id = h.id
    LEFT JOIN bolumler b  ON b.id = pr.bolum_id
    LEFT JOIN ders_kayitlari dk
        ON dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
    LEFT JOIN notlar n    ON n.ders_kaydi_id = dk.id
GROUP BY h.id, h.ad, h.soyad, pr.unvan, b.ad, da.donem_id, dn.ad;

-- ---------------------------------------------------------------------
-- 3) v_donem_ozet
--    Dönem bazında toplu göstergeler (kayıtlı öğrenci, not girme,
--    ortalama, başarı yüzdesi). Rapor üst kartlarını besler.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_donem_ozet AS
SELECT
    dn.id                                    AS donem_id,
    dn.ad                                    AS donem,
    dn.aktif                                 AS aktif,
    COUNT(DISTINCT da.id)                    AS ders_acilisi,
    COUNT(DISTINCT da.ders_id)               AS farkli_ders,
    COUNT(DISTINCT dk.ogrenci_id)            AS kayitli_ogrenci,
    COUNT(DISTINCT dk.id)                    AS toplam_kayit,
    SUM(CASE WHEN n.ortalama IS NOT NULL THEN 1 ELSE 0 END) AS notu_girilen,
    ROUND(AVG(n.ortalama), 2)                AS ortalama,
    SUM(CASE WHEN n.ortalama >= 60 THEN 1 ELSE 0 END)       AS gecen,
    SUM(CASE WHEN n.ortalama IS NOT NULL AND n.ortalama < 60 THEN 1 ELSE 0 END) AS kalan,
    ROUND(
        100 * SUM(CASE WHEN n.ortalama >= 60 THEN 1 ELSE 0 END)
        / NULLIF(SUM(CASE WHEN n.ortalama IS NOT NULL THEN 1 ELSE 0 END), 0),
        1
    )                                        AS basari_yuzdesi
FROM donemler dn
    LEFT JOIN ders_acilislari da ON da.donem_id = dn.id
    LEFT JOIN ders_kayitlari dk
        ON dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
    LEFT JOIN notlar n ON n.ders_kaydi_id = dk.id
GROUP BY dn.id, dn.ad, dn.aktif;

-- ---------------------------------------------------------------------
-- 4) v_harf_notu_dagilimi
--    Dönem bazında harf notu dağılımı (grafik için).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_harf_notu_dagilimi AS
SELECT
    dn.id                                    AS donem_id,
    dn.ad                                    AS donem,
    COALESCE(n.harf_notu, '-')               AS harf_notu,
    COUNT(*)                                 AS adet
FROM notlar n
    JOIN ders_kayitlari dk  ON dk.id = n.ders_kaydi_id
    JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
    JOIN donemler dn        ON dn.id = da.donem_id
WHERE dk.durum = 'aktif'
GROUP BY dn.id, dn.ad, n.harf_notu
ORDER BY dn.id DESC,
         FIELD(COALESCE(n.harf_notu, '-'),
               'AA', 'BA', 'BB', 'CB', 'CC', 'DC', 'DD', 'FD', 'FF', '-');

-- ---------------------------------------------------------------------
-- 5) v_bolum_ozet
--    Bölüm bazında öğrenci, ders ve başarı özeti.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_bolum_ozet AS
SELECT
    b.id                                     AS bolum_id,
    b.kod                                    AS bolum_kodu,
    b.ad                                     AS bolum,
    COUNT(DISTINCT op.kullanici_id)          AS ogrenci,
    COUNT(DISTINCT d.id)                     AS ders_sayisi,
    COUNT(DISTINCT da.id)                    AS ders_acilisi,
    COUNT(DISTINCT dk.ogrenci_id)            AS kayitli_ogrenci,
    ROUND(AVG(n.ortalama), 2)                AS ortalama,
    ROUND(
        100 * SUM(CASE WHEN n.ortalama >= 60 THEN 1 ELSE 0 END)
        / NULLIF(SUM(CASE WHEN n.ortalama IS NOT NULL THEN 1 ELSE 0 END), 0),
        1
    )                                        AS basari_yuzdesi
FROM bolumler b
    LEFT JOIN ogrenci_profilleri op ON op.bolum_id = b.id
    LEFT JOIN dersler d             ON d.bolum_id = b.id
    LEFT JOIN ders_acilislari da    ON da.ders_id = d.id
    LEFT JOIN ders_kayitlari dk
        ON dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
    LEFT JOIN notlar n ON n.ders_kaydi_id = dk.id
GROUP BY b.id, b.kod, b.ad;

-- ---------------------------------------------------------------------
-- 6) sp_donem_raporu
--    Bir dönemin ders bazlı raporunu tek sonuç kümesi olarak döndürür.
--    v_ders_doluluk + v_ders_basari_istatistik birleşimini kullanır.
--
--    Kullanım:
--      CALL sp_donem_raporu(3);
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_donem_raporu;

DELIMITER $$

CREATE PROCEDURE sp_donem_raporu(IN p_donem_id TINYINT UNSIGNED)
BEGIN
    SELECT
        vd.ders_kodu,
        vd.ders_adi,
        vd.sube,
        vd.ogretim_elemani,
        vd.kontenjan,
        vd.kayitli,
        vd.doluluk_yuzdesi,
        COALESCE(vb.notu_girilen, 0)  AS notu_girilen,
        vb.ortalama,
        COALESCE(vb.gecen, 0)         AS gecen,
        COALESCE(vb.kalan, 0)         AS kalan,
        vb.basari_yuzdesi
    FROM v_ders_doluluk vd
        LEFT JOIN v_ders_basari_istatistik vb
            ON vb.ders_acilis_id = vd.ders_acilis_id
    WHERE vd.donem_id = p_donem_id
    ORDER BY vd.ders_kodu, vd.sube;
END$$

-- ---------------------------------------------------------------------
-- 7) sp_riskli_ogrenciler
--    Dönem ortalaması eşiğin altında kalan öğrencileri listeler.
--    Akademik risk raporunu besler.
--
--    Kullanım:
--      CALL sp_riskli_ogrenciler(3, 60);
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_riskli_ogrenciler$$

CREATE PROCEDURE sp_riskli_ogrenciler(
    IN p_donem_id TINYINT UNSIGNED,
    IN p_esik     DECIMAL(5, 2)
)
BEGIN
    SELECT
        k.numara                             AS ogrenci_no,
        CONCAT(k.ad, ' ', k.soyad)           AS ogrenci,
        COALESCE(b.ad, '-')                  AS bolum,
        COALESCE(op.sinif, 0)                AS sinif,
        COUNT(DISTINCT dk.id)                AS ders_sayisi,
        ROUND(AVG(n.ortalama), 2)            AS ortalama,
        SUM(CASE WHEN n.ortalama IS NOT NULL AND n.ortalama < 60 THEN 1 ELSE 0 END) AS kalan_ders,
        SUM(CASE WHEN n.ortalama >= 60 THEN 1 ELSE 0 END) AS gecen_ders
    FROM ders_kayitlari dk
        JOIN kullanicilar k     ON k.id = dk.ogrenci_id
        JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
        LEFT JOIN notlar n      ON n.ders_kaydi_id = dk.id
        LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
        LEFT JOIN bolumler b    ON b.id = op.bolum_id
    WHERE dk.durum = 'aktif'
      AND da.donem_id = p_donem_id
      AND k.aktif = 1
    GROUP BY k.id, k.numara, k.ad, k.soyad, b.ad, op.sinif
    HAVING AVG(n.ortalama) < p_esik
        OR SUM(CASE WHEN n.ortalama IS NOT NULL AND n.ortalama < 60 THEN 1 ELSE 0 END) > 0
    ORDER BY ortalama ASC, k.numara
    LIMIT 20;
END$$

DELIMITER ;
