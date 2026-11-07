-- =====================================================================
-- Nallıhan MYO Sanal Okul - Trigger'lar
-- Dosya : 03_triggers.sql
--
-- Not: Uygulama, işlemi yapan kullanıcıyı loglayabilmek için bağlantıda
--      @oturum_kullanici_id ve @oturum_ip değişkenlerini set eder.
-- =====================================================================

SET NAMES utf8mb4;

DELIMITER $$

-- ---------------------------------------------------------------------
-- 1) Not ortalaması ve harf notu otomatik hesaplama (INSERT)
--    Varsayım: vize %40, final %60. Hoca farklı oran isterse buradaki
--    0.40 / 0.60 değerleri değiştirilir.
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_notlar_hesapla_ins$$
CREATE TRIGGER trg_notlar_hesapla_ins
BEFORE INSERT ON notlar
FOR EACH ROW
BEGIN
    IF NEW.vize IS NULL OR NEW.`final` IS NULL THEN
        SET NEW.ortalama  = NULL;
        SET NEW.harf_notu = NULL;
    ELSE
        SET NEW.ortalama = ROUND(NEW.vize * 0.40 + NEW.`final` * 0.60, 2);
        SET NEW.harf_notu = CASE
            WHEN NEW.ortalama >= 90 THEN 'AA'
            WHEN NEW.ortalama >= 85 THEN 'BA'
            WHEN NEW.ortalama >= 80 THEN 'BB'
            WHEN NEW.ortalama >= 75 THEN 'CB'
            WHEN NEW.ortalama >= 70 THEN 'CC'
            WHEN NEW.ortalama >= 65 THEN 'DC'
            WHEN NEW.ortalama >= 60 THEN 'DD'
            WHEN NEW.ortalama >= 50 THEN 'FD'
            ELSE 'FF'
        END;
    END IF;
END$$

-- ---------------------------------------------------------------------
-- 2) Not ortalaması ve harf notu otomatik hesaplama (UPDATE)
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_notlar_hesapla_upd$$
CREATE TRIGGER trg_notlar_hesapla_upd
BEFORE UPDATE ON notlar
FOR EACH ROW
BEGIN
    IF NEW.vize IS NULL OR NEW.`final` IS NULL THEN
        SET NEW.ortalama  = NULL;
        SET NEW.harf_notu = NULL;
    ELSE
        SET NEW.ortalama = ROUND(NEW.vize * 0.40 + NEW.`final` * 0.60, 2);
        SET NEW.harf_notu = CASE
            WHEN NEW.ortalama >= 90 THEN 'AA'
            WHEN NEW.ortalama >= 85 THEN 'BA'
            WHEN NEW.ortalama >= 80 THEN 'BB'
            WHEN NEW.ortalama >= 75 THEN 'CB'
            WHEN NEW.ortalama >= 70 THEN 'CC'
            WHEN NEW.ortalama >= 65 THEN 'DC'
            WHEN NEW.ortalama >= 60 THEN 'DD'
            WHEN NEW.ortalama >= 50 THEN 'FD'
            ELSE 'FF'
        END;
    END IF;
END$$

-- ---------------------------------------------------------------------
-- 3) Not değişikliğini işlem loglarına yaz
--    Sadece vize/final gerçekten değiştiyse log atar (log kirlenmesin).
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_notlar_log$$
CREATE TRIGGER trg_notlar_log
AFTER UPDATE ON notlar
FOR EACH ROW
BEGIN
    IF NOT (OLD.vize <=> NEW.vize) OR NOT (OLD.`final` <=> NEW.`final`) THEN
        INSERT INTO islem_loglari (kullanici_id, islem, tablo_adi, kayit_id, ip)
        VALUES (
            NULLIF(@oturum_kullanici_id, 0),
            CONCAT(
                'Not güncellendi. Vize: ', COALESCE(OLD.vize, '-'), ' -> ', COALESCE(NEW.vize, '-'),
                ' | Final: ', COALESCE(OLD.`final`, '-'), ' -> ', COALESCE(NEW.`final`, '-')
            ),
            'notlar',
            NEW.id,
            @oturum_ip
        );
    END IF;
END$$

-- ---------------------------------------------------------------------
-- 4) Derse kayıt öncesi kontrol
--    a) Kayıt yapan kişi gerçekten öğrenci mi?
--    b) Kontenjan doldu mu?  (NULL kontenjan = sınırsız)
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_kayit_kontenjan$$
CREATE TRIGGER trg_kayit_kontenjan
BEFORE INSERT ON ders_kayitlari
FOR EACH ROW
BEGIN
    DECLARE v_ogrenci_mi INT DEFAULT 0;
    DECLARE v_kontenjan  SMALLINT UNSIGNED DEFAULT NULL;
    DECLARE v_mevcut     INT DEFAULT 0;

    SELECT COUNT(*) INTO v_ogrenci_mi
    FROM ogrenci_profilleri
    WHERE kullanici_id = NEW.ogrenci_id;

    IF v_ogrenci_mi = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Kayıt yapılan kullanıcı bir öğrenci değil.';
    END IF;

    SELECT kontenjan INTO v_kontenjan
    FROM ders_acilislari
    WHERE id = NEW.ders_acilis_id;

    SELECT COUNT(*) INTO v_mevcut
    FROM ders_kayitlari
    WHERE ders_acilis_id = NEW.ders_acilis_id
      AND durum = 'aktif';

    IF v_kontenjan IS NOT NULL AND v_mevcut >= v_kontenjan THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ders kontenjanı doldu, kayıt yapılamaz.';
    END IF;
END$$

DELIMITER ;
