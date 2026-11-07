-- =====================================================================
-- Nallıhan MYO Sanal Okul - Veritabanı Şeması
-- Dosya   : 01_schema.sql
-- Sürüm   : 1.0
-- Not     : Boş bir veritabanında sırayla çalıştırılır (01 -> 05).
--           Tüm tablolar InnoDB, karakter seti utf8mb4 / utf8mb4_turkish_ci.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+03:00';

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS kaynak_senkron_durumu;
DROP TABLE IF EXISTS dis_haberler;
DROP TABLE IF EXISTS yemek_menuleri;
DROP TABLE IF EXISTS giris_denemeleri;
DROP TABLE IF EXISTS islem_loglari;
DROP TABLE IF EXISTS notlar;
DROP TABLE IF EXISTS teslimler;
DROP TABLE IF EXISTS odevler;
DROP TABLE IF EXISTS duyurular;
DROP TABLE IF EXISTS ders_icerikleri;
DROP TABLE IF EXISTS ders_programi;
DROP TABLE IF EXISTS ders_kayitlari;
DROP TABLE IF EXISTS ders_acilislari;
DROP TABLE IF EXISTS dersler;
DROP TABLE IF EXISTS ogretim_elemani_profilleri;
DROP TABLE IF EXISTS ogrenci_profilleri;
DROP TABLE IF EXISTS kullanicilar;
DROP TABLE IF EXISTS donemler;
DROP TABLE IF EXISTS bolumler;
DROP TABLE IF EXISTS roller;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Kimlik ve organizasyon
-- ---------------------------------------------------------------------

CREATE TABLE roller (
    id  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ad  VARCHAR(20)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roller_ad (ad),
    CONSTRAINT chk_roller_ad CHECK (ad IN ('ogrenci', 'ogretim_elemani', 'yonetici'))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE bolumler (
    id   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kod  VARCHAR(12)      NOT NULL,
    ad   VARCHAR(120)     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bolumler_kod (kod)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE donemler (
    id         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ad         VARCHAR(40)      NOT NULL,
    baslangic  DATE             NOT NULL,
    bitis      DATE             NOT NULL,
    aktif      TINYINT(1)       NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_donemler_ad (ad),
    CONSTRAINT chk_donemler_tarih CHECK (bitis > baslangic)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE kullanicilar (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    rol_id          TINYINT UNSIGNED NOT NULL,
    numara          VARCHAR(20)      NOT NULL COMMENT 'Öğrenci no veya personel sicil no',
    ad              VARCHAR(60)      NOT NULL,
    soyad           VARCHAR(60)      NOT NULL,
    eposta          VARCHAR(120)     NOT NULL,
    sifre_hash      VARCHAR(255)     NOT NULL,
    telefon         VARCHAR(20)          NULL,
    tema_tercihi    ENUM('sistem', 'acik', 'koyu') NOT NULL DEFAULT 'sistem',
    aktif           TINYINT(1)       NOT NULL DEFAULT 1,
    son_giris       DATETIME             NULL,
    olusturma_zamani TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kullanici_numara (numara),
    UNIQUE KEY uq_kullanici_eposta (eposta),
    KEY idx_kullanici_rol (rol_id),
    CONSTRAINT fk_kullanici_rol FOREIGN KEY (rol_id) REFERENCES roller (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE ogrenci_profilleri (
    kullanici_id  INT UNSIGNED     NOT NULL,
    bolum_id      TINYINT UNSIGNED NOT NULL,
    sinif         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    giris_yili    SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (kullanici_id),
    KEY idx_op_bolum (bolum_id),
    CONSTRAINT fk_op_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_op_bolum FOREIGN KEY (bolum_id) REFERENCES bolumler (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_op_sinif CHECK (sinif BETWEEN 1 AND 2)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE ogretim_elemani_profilleri (
    kullanici_id  INT UNSIGNED     NOT NULL,
    bolum_id      TINYINT UNSIGNED NOT NULL,
    unvan         VARCHAR(60)      NOT NULL,
    PRIMARY KEY (kullanici_id),
    KEY idx_oep_bolum (bolum_id),
    CONSTRAINT fk_oep_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_oep_bolum FOREIGN KEY (bolum_id) REFERENCES bolumler (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------
-- 2. Akademik yapı
-- ---------------------------------------------------------------------

CREATE TABLE dersler (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bolum_id    TINYINT UNSIGNED  NOT NULL,
    kod         VARCHAR(12)       NOT NULL,
    ad          VARCHAR(140)      NOT NULL,
    kredi       TINYINT UNSIGNED  NOT NULL DEFAULT 3,
    akts        TINYINT UNSIGNED  NOT NULL DEFAULT 4,
    zorunlu_mu  TINYINT(1)        NOT NULL DEFAULT 1,
    sinif       TINYINT UNSIGNED  NOT NULL DEFAULT 1 COMMENT 'Dersin okutulduğu sınıf (1 veya 2)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_dersler_kod (kod),
    KEY idx_dersler_bolum (bolum_id),
    CONSTRAINT fk_dersler_bolum FOREIGN KEY (bolum_id) REFERENCES bolumler (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_dersler_kredi CHECK (kredi BETWEEN 1 AND 10),
    CONSTRAINT chk_dersler_sinif CHECK (sinif BETWEEN 1 AND 2)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE ders_acilislari (
    id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    ders_id             SMALLINT UNSIGNED NOT NULL,
    donem_id            TINYINT UNSIGNED  NOT NULL,
    ogretim_elemani_id  INT UNSIGNED      NOT NULL,
    sube                VARCHAR(5)        NOT NULL DEFAULT 'A',
    kontenjan           SMALLINT UNSIGNED     NULL COMMENT 'NULL = sınırsız',
    PRIMARY KEY (id),
    UNIQUE KEY uq_ders_acilisi (ders_id, donem_id, sube),
    KEY idx_da_donem (donem_id),
    KEY idx_da_hoca (ogretim_elemani_id),
    CONSTRAINT fk_da_ders FOREIGN KEY (ders_id) REFERENCES dersler (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_da_donem FOREIGN KEY (donem_id) REFERENCES donemler (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_da_hoca FOREIGN KEY (ogretim_elemani_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE ders_kayitlari (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    ogrenci_id      INT UNSIGNED     NOT NULL,
    ders_acilis_id  INT UNSIGNED     NOT NULL,
    kayit_zamani    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    durum           ENUM('aktif', 'birakti', 'tamamlandi') NOT NULL DEFAULT 'aktif',
    PRIMARY KEY (id),
    UNIQUE KEY uq_dk_ogrenci_ders (ogrenci_id, ders_acilis_id),
    KEY idx_dk_ders (ders_acilis_id),
    KEY idx_dk_durum (durum),
    CONSTRAINT fk_dk_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_dk_ders_acilisi FOREIGN KEY (ders_acilis_id) REFERENCES ders_acilislari (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE ders_programi (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ders_acilis_id  INT UNSIGNED    NOT NULL,
    gun             TINYINT UNSIGNED NOT NULL COMMENT '1=Pazartesi ... 5=Cuma',
    baslangic_saati TIME            NOT NULL,
    bitis_saati     TIME            NOT NULL,
    derslik         VARCHAR(40)     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_dp_ders (ders_acilis_id),
    KEY idx_dp_gun_saat (gun, baslangic_saati),
    CONSTRAINT fk_dp_ders_acilisi FOREIGN KEY (ders_acilis_id) REFERENCES ders_acilislari (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_dp_gun CHECK (gun BETWEEN 1 AND 5),
    CONSTRAINT chk_dp_saat CHECK (bitis_saati > baslangic_saati)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE ders_icerikleri (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    ders_acilis_id  INT UNSIGNED     NOT NULL,
    hafta           TINYINT UNSIGNED NOT NULL,
    baslik          VARCHAR(160)     NOT NULL,
    aciklama        TEXT                 NULL,
    dosya_yolu      VARCHAR(255)         NULL,
    olusturma_zamani TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_di_hafta (ders_acilis_id, hafta),
    CONSTRAINT fk_di_ders_acilisi FOREIGN KEY (ders_acilis_id) REFERENCES ders_acilislari (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_di_hafta CHECK (hafta BETWEEN 1 AND 16)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE duyurular (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    ders_acilis_id  INT UNSIGNED         NULL COMMENT 'NULL = genel duyuru',
    yazar_id        INT UNSIGNED     NOT NULL,
    baslik          VARCHAR(160)     NOT NULL,
    icerik          TEXT             NOT NULL,
    onemli          TINYINT(1)       NOT NULL DEFAULT 0,
    yayin_tarihi    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bitis_tarihi    DATETIME             NULL,
    PRIMARY KEY (id),
    KEY idx_duyuru_ders (ders_acilis_id),
    KEY idx_duyuru_yazar (yazar_id),
    KEY idx_duyuru_tarih (yayin_tarihi),
    CONSTRAINT fk_duyuru_ders_acilisi FOREIGN KEY (ders_acilis_id) REFERENCES ders_acilislari (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_duyuru_yazar FOREIGN KEY (yazar_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE odevler (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    ders_acilis_id  INT UNSIGNED     NOT NULL,
    baslik          VARCHAR(160)     NOT NULL,
    aciklama        TEXT             NOT NULL,
    son_teslim      DATETIME         NOT NULL,
    maks_puan       SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    dosya_yolu      VARCHAR(255)         NULL,
    olusturma_zamani TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_odev_ders (ders_acilis_id),
    KEY idx_odev_teslim (son_teslim),
    CONSTRAINT fk_odev_ders_acilisi FOREIGN KEY (ders_acilis_id) REFERENCES ders_acilislari (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_odev_puan CHECK (maks_puan BETWEEN 1 AND 1000)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE teslimler (
    id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    odev_id             INT UNSIGNED     NOT NULL,
    ogrenci_id          INT UNSIGNED     NOT NULL,
    dosya_yolu          VARCHAR(255)     NOT NULL,
    aciklama            TEXT                 NULL,
    teslim_zamani       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    puan                SMALLINT UNSIGNED     NULL,
    geri_bildirim       TEXT                 NULL,
    degerlendirme_zamani DATETIME            NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teslim_odev_ogrenci (odev_id, ogrenci_id),
    KEY idx_teslim_ogrenci (ogrenci_id),
    CONSTRAINT fk_teslim_odev FOREIGN KEY (odev_id) REFERENCES odevler (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_teslim_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE notlar (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    ders_kaydi_id     INT UNSIGNED  NOT NULL,
    vize              DECIMAL(5,2)      NULL,
    `final`           DECIMAL(5,2)      NULL,
    ortalama          DECIMAL(5,2)      NULL COMMENT 'Trigger ile hesaplanır',
    harf_notu         VARCHAR(2)        NULL COMMENT 'Trigger ile hesaplanır',
    guncelleme_zamani TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_not_ders_kaydi (ders_kaydi_id),
    CONSTRAINT fk_not_ders_kaydi FOREIGN KEY (ders_kaydi_id) REFERENCES ders_kayitlari (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_not_vize CHECK (vize IS NULL OR vize BETWEEN 0 AND 100),
    CONSTRAINT chk_not_final CHECK (`final` IS NULL OR `final` BETWEEN 0 AND 100)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

-- ---------------------------------------------------------------------
-- 3. Denetim ve dış içerik
-- ---------------------------------------------------------------------

CREATE TABLE islem_loglari (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kullanici_id INT UNSIGNED       NULL,
    islem       VARCHAR(255)    NOT NULL,
    tablo_adi   VARCHAR(60)         NULL,
    kayit_id    INT UNSIGNED        NULL,
    ip          VARCHAR(45)         NULL,
    zaman       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_log_kullanici (kullanici_id),
    KEY idx_log_zaman (zaman),
    CONSTRAINT fk_log_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE giris_denemeleri (
    id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    eposta    VARCHAR(120)    NOT NULL,
    ip        VARCHAR(45)     NOT NULL,
    basarili  TINYINT(1)      NOT NULL DEFAULT 0,
    zaman     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_gd_eposta_zaman (eposta, zaman),
    KEY idx_gd_ip_zaman (ip, zaman)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE yemek_menuleri (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    tarih          DATE             NOT NULL,
    corba          VARCHAR(120)         NULL,
    ana_yemek      VARCHAR(120)         NULL,
    yardimci_yemek VARCHAR(120)         NULL,
    tatli_salata   VARCHAR(120)         NULL,
    corba_kcal     SMALLINT UNSIGNED    NULL,
    ana_kcal       SMALLINT UNSIGNED    NULL,
    yardimci_kcal  SMALLINT UNSIGNED    NULL,
    ek_kcal        SMALLINT UNSIGNED    NULL,
    toplam_kcal    SMALLINT UNSIGNED    NULL,
    kaynak_url     VARCHAR(500)         NULL,
    cekilme_zamani TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_yemek_tarih (tarih),
    KEY idx_yemek_tarih (tarih)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE dis_haberler (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kaynak         VARCHAR(60)  NOT NULL,
    baslik         VARCHAR(255) NOT NULL,
    ozet           TEXT             NULL,
    url            VARCHAR(500) NOT NULL,
    gorsel_url     VARCHAR(500)     NULL,
    yayin_tarihi   DATETIME         NULL,
    cekilme_zamani TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_haber_url (url(191)),
    KEY idx_haber_tarih (yayin_tarihi)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;

CREATE TABLE kaynak_senkron_durumu (
    kaynak       VARCHAR(60)  NOT NULL,
    son_deneme   DATETIME         NULL,
    son_basari   DATETIME         NULL,
    durum        ENUM('bekliyor', 'basarili', 'hata', 'calisiyor') NOT NULL DEFAULT 'bekliyor',
    hata_mesaji  TEXT             NULL,
    PRIMARY KEY (kaynak)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_turkish_ci;
