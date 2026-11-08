-- =====================================================================
-- Nallıhan MYO Sanal Okul - Performans indeksleri
-- Dosya : 08_performans.sql
-- Faz   : 7 - Güvenlik + performans + yedekleme
--
-- MariaDB 10.11 üzerinde çalıştırılır. CREATE INDEX IF NOT EXISTS
-- sayesinde betik birden fazla kez güvenle uygulanabilir.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1) ders_kayitlari (ders_acilis_id, durum)
--    Rapor view'leri her ders açılışı için "durum = 'aktif'" filtreler.
--    Tek sütunlu iki indeks yerine bileşik indeks tek taramada karşılar.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_dk_ders_durum
    ON ders_kayitlari (ders_acilis_id, durum);

-- ---------------------------------------------------------------------
-- 2) ders_acilislari (ogretim_elemani_id, donem_id)
--    v_ogretim_elemani_yuku ve hocaYuku alt sorgusu hoca + dönem
--    eşleşmesiyle çalışır.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_da_hoca_donem
    ON ders_acilislari (ogretim_elemani_id, donem_id);

-- ---------------------------------------------------------------------
-- 3) kullanicilar (rol_id, aktif)
--    Yönetici panelindeki rol sayaçları ve kullanıcı listeleri.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_kullanici_rol_aktif
    ON kullanicilar (rol_id, aktif);

-- ---------------------------------------------------------------------
-- 4) islem_loglari (tablo_adi, zaman)
--    Log ekranındaki tablo filtresi + tarih sıralaması.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_log_tablo_zaman
    ON islem_loglari (tablo_adi, zaman);

-- ---------------------------------------------------------------------
-- 5) duyurular (ders_acilis_id, yayin_tarihi)
--    Ders duyuruları ve genel duyurular tarih sırasıyla listelenir.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_duyuru_hedef_tarih
    ON duyurular (ders_acilis_id, yayin_tarihi);

-- ---------------------------------------------------------------------
-- 6) teslimler (ogrenci_id, teslim_zamani)
--    Öğrenci panelindeki teslim geçmişi.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_teslim_ogrenci_zaman
    ON teslimler (ogrenci_id, teslim_zamani);

ANALYZE TABLE
    ders_kayitlari,
    ders_acilislari,
    kullanicilar,
    islem_loglari,
    duyurular,
    teslimler;
