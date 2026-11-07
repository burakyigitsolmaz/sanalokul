-- =====================================================================
-- Nallıhan MYO Sanal Okul - Görünümler (VIEW)
-- Dosya : 02_views.sql
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1) Öğrenci-ders-not özeti
--    Öğrenci panelindeki "Notlarım" ekranını tek sorguyla besler.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ogrenci_not_ozeti AS
SELECT
    k.id                                AS ogrenci_id,
    k.numara                            AS ogrenci_no,
    CONCAT(k.ad, ' ', k.soyad)          AS ogrenci_ad_soyad,
    d.id                                AS ders_id,
    d.kod                               AS ders_kodu,
    d.ad                                AS ders_adi,
    d.kredi                             AS kredi,
    dn.id                               AS donem_id,
    dn.ad                               AS donem,
    da.id                               AS ders_acilis_id,
    da.sube                             AS sube,
    CONCAT(h.ad, ' ', h.soyad)          AS ogretim_elemani,
    n.vize                              AS vize,
    n.`final`                           AS final_notu,
    n.ortalama                          AS ortalama,
    n.harf_notu                         AS harf_notu
FROM ders_kayitlari dk
    JOIN kullanicilar k       ON k.id = dk.ogrenci_id
    JOIN ders_acilislari da   ON da.id = dk.ders_acilis_id
    JOIN dersler d            ON d.id = da.ders_id
    JOIN donemler dn          ON dn.id = da.donem_id
    JOIN kullanicilar h       ON h.id = da.ogretim_elemani_id
    LEFT JOIN notlar n        ON n.ders_kaydi_id = dk.id
WHERE dk.durum = 'aktif';

-- ---------------------------------------------------------------------
-- 2) Ders bazlı başarı istatistiği
--    Yönetici rapor ekranında kullanılır.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_ders_basari_istatistik AS
SELECT
    da.id                                       AS ders_acilis_id,
    d.kod                                       AS ders_kodu,
    d.ad                                        AS ders_adi,
    dn.ad                                       AS donem,
    da.sube                                     AS sube,
    CONCAT(h.ad, ' ', h.soyad)                  AS ogretim_elemani,
    COUNT(dk.id)                                AS kayitli_ogrenci,
    SUM(CASE WHEN n.ortalama IS NOT NULL THEN 1 ELSE 0 END) AS notu_girilen,
    ROUND(AVG(n.ortalama), 2)                   AS ortalama,
    SUM(CASE WHEN n.ortalama >= 60 THEN 1 ELSE 0 END)       AS gecen,
    SUM(CASE WHEN n.ortalama IS NOT NULL AND n.ortalama < 60 THEN 1 ELSE 0 END) AS kalan,
    ROUND(
        100 * SUM(CASE WHEN n.ortalama >= 60 THEN 1 ELSE 0 END)
        / NULLIF(SUM(CASE WHEN n.ortalama IS NOT NULL THEN 1 ELSE 0 END), 0),
        1
    )                                           AS basari_yuzdesi
FROM ders_acilislari da
    JOIN dersler d      ON d.id = da.ders_id
    JOIN donemler dn    ON dn.id = da.donem_id
    JOIN kullanicilar h ON h.id = da.ogretim_elemani_id
    LEFT JOIN ders_kayitlari dk ON dk.ders_acilis_id = da.id AND dk.durum = 'aktif'
    LEFT JOIN notlar n          ON n.ders_kaydi_id = dk.id
GROUP BY da.id, d.kod, d.ad, dn.ad, da.sube, h.ad, h.soyad;

-- ---------------------------------------------------------------------
-- 3) Bugünün ders programı (aktif dönem)
--    Ana sayfadaki "Bugünün Programı" kartını besler.
--    WEEKDAY(): Pazartesi = 0 ... Pazar = 6  -> +1 ile 1..5 eşleşir.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_bugunun_programi AS
SELECT
    dp.id                       AS program_id,
    da.id                       AS ders_acilis_id,
    dn.ad                       AS donem,
    d.kod                       AS ders_kodu,
    d.ad                        AS ders_adi,
    d.sinif                     AS sinif,
    da.sube                     AS sube,
    CONCAT(h.ad, ' ', h.soyad)  AS ogretim_elemani,
    dp.gun                      AS gun,
    dp.baslangic_saati          AS baslangic_saati,
    dp.bitis_saati              AS bitis_saati,
    dp.derslik                  AS derslik,
    CURDATE()                   AS tarih
FROM ders_programi dp
    JOIN ders_acilislari da ON da.id = dp.ders_acilis_id
    JOIN donemler dn        ON dn.id = da.donem_id AND dn.aktif = 1
    JOIN dersler d          ON d.id = da.ders_id
    JOIN kullanicilar h     ON h.id = da.ogretim_elemani_id
WHERE dp.gun = WEEKDAY(CURDATE()) + 1
ORDER BY dp.baslangic_saati;
