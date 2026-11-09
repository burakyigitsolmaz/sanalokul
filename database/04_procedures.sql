-- =====================================================================
-- Nallıhan MYO Sanal Okul - Stored Procedure'ler
-- Dosya : 04_procedures.sql
-- =====================================================================

SET NAMES utf8mb4;

DELIMITER $$

-- ---------------------------------------------------------------------
-- 1) sp_derse_kayit_ol
--    Kontenjan + program çakışması + mükerrer kayıt kontrolü yapar,
--    kaydı transaction içinde oluşturur.
--
--    Kullanım:
--      CALL sp_derse_kayit_ol(1, 5, @ok, @mesaj);
--      SELECT @ok, @mesaj;
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_derse_kayit_ol$$
CREATE PROCEDURE sp_derse_kayit_ol(
    IN  p_ogrenci_id      INT UNSIGNED,
    IN  p_ders_acilis_id  INT UNSIGNED,
    OUT p_basarili        TINYINT,
    OUT p_mesaj           VARCHAR(255)
)
proc: BEGIN
    DECLARE v_var       INT DEFAULT 0;
    DECLARE v_donem     TINYINT UNSIGNED DEFAULT NULL;
    DECLARE v_kontenjan SMALLINT UNSIGNED DEFAULT NULL;
    DECLARE v_mevcut    INT DEFAULT 0;
    DECLARE v_cakisma   INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_basarili = 0;
        SET p_mesaj = 'Kayıt sırasında veritabanı hatası oluştu.';
    END;

    SET p_basarili = 0;
    SET p_mesaj = '';

    -- Ders açılışı var mı?
    SELECT COUNT(*) INTO v_var
    FROM ders_acilislari
    WHERE id = p_ders_acilis_id;

    IF v_var = 0 THEN
        SET p_mesaj = 'Ders açılışı bulunamadı.';
        LEAVE proc;
    END IF;

    -- Kullanıcı öğrenci mi?
    SELECT COUNT(*) INTO v_var
    FROM ogrenci_profilleri
    WHERE kullanici_id = p_ogrenci_id;

    IF v_var = 0 THEN
        SET p_mesaj = 'Öğrenci bulunamadı.';
        LEAVE proc;
    END IF;

    -- Daha önce kayıtlı mı?
    SELECT COUNT(*) INTO v_var
    FROM ders_kayitlari
    WHERE ogrenci_id = p_ogrenci_id
      AND ders_acilis_id = p_ders_acilis_id;

    IF v_var > 0 THEN
        SET p_mesaj = 'Bu derse zaten kayıtlısınız.';
        LEAVE proc;
    END IF;

    SELECT donem_id, kontenjan INTO v_donem, v_kontenjan
    FROM ders_acilislari
    WHERE id = p_ders_acilis_id;

    -- Kontenjan kontrolü
    SELECT COUNT(*) INTO v_mevcut
    FROM ders_kayitlari
    WHERE ders_acilis_id = p_ders_acilis_id
      AND durum = 'aktif';

    IF v_kontenjan IS NOT NULL AND v_mevcut >= v_kontenjan THEN
        SET p_mesaj = 'Ders kontenjanı doldu.';
        LEAVE proc;
    END IF;

    -- Aynı dönemde saat çakışması var mı?
    SELECT COUNT(*) INTO v_cakisma
    FROM ders_kayitlari dk
        JOIN ders_acilislari da  ON da.id = dk.ders_acilis_id
                                AND da.donem_id = v_donem
        JOIN ders_programi dp2   ON dp2.ders_acilis_id = da.id
        JOIN ders_programi dp1   ON dp1.ders_acilis_id = p_ders_acilis_id
    WHERE dk.ogrenci_id = p_ogrenci_id
      AND dk.durum = 'aktif'
      AND dp1.gun = dp2.gun
      AND dp1.baslangic_saati < dp2.bitis_saati
      AND dp2.baslangic_saati < dp1.bitis_saati;

    IF v_cakisma > 0 THEN
        SET p_mesaj = 'Bu ders, programınızdaki başka bir dersle çakışıyor.';
        LEAVE proc;
    END IF;

    START TRANSACTION;

    INSERT INTO ders_kayitlari (ogrenci_id, ders_acilis_id, durum)
    VALUES (p_ogrenci_id, p_ders_acilis_id, 'aktif');

    COMMIT;

    SET p_basarili = 1;
    SET p_mesaj = 'Derse kayıt başarıyla tamamlandı.';
END$$

-- ---------------------------------------------------------------------
-- 2) sp_donem_ortalamasi
--    Öğrencinin bir dönemdeki kredi ağırlıklı ortalamasını hesaplar.
--
--    Kullanım:
--      CALL sp_donem_ortalamasi(1, 1, @ort, @ders_sayisi);
--      SELECT @ort, @ders_sayisi;
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_donem_ortalamasi$$
CREATE PROCEDURE sp_donem_ortalamasi(
    IN  p_ogrenci_id  INT UNSIGNED,
    IN  p_donem_id    TINYINT UNSIGNED,
    OUT p_ortalama    DECIMAL(5, 2),
    OUT p_ders_sayisi INT
)
BEGIN
    SELECT
        COUNT(n.id),
        ROUND(
            SUM(n.ortalama * d.kredi)
            / NULLIF(SUM(CASE WHEN n.ortalama IS NOT NULL THEN d.kredi ELSE 0 END), 0),
            2
        )
    INTO p_ders_sayisi, p_ortalama
    FROM ders_kayitlari dk
        JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
        JOIN dersler d          ON d.id = da.ders_id
        LEFT JOIN notlar n      ON n.ders_kaydi_id = dk.id
    WHERE dk.ogrenci_id = p_ogrenci_id
      AND da.donem_id = p_donem_id
      AND dk.durum = 'aktif';
END$$

-- ---------------------------------------------------------------------
-- 3) sp_donem_raporu
--    Bir dönemin ders bazlı raporunu tek sonuç kümesi olarak döndürür.
--    v_ders_doluluk + v_ders_basari_istatistik birleşimini kullanır.
--
--    Kullanım:
--      CALL sp_donem_raporu(3);
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_donem_raporu$$
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
-- 4) sp_riskli_ogrenciler
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
