-- =====================================================================
-- Nallıhan MYO Sanal Okul - 09_tema.sql
-- Tema tercihi üç moddan iki moda indirildi: acik | koyu
--
-- Yapılanlar:
--   1. Kayıtlı "sistem" tercihleri "koyu"ya çekilir.
--   2. Kolonun varsayılanı "koyu" olur.
--   3. ENUM tanımı ('acik', 'koyu') olarak güncellenir.
--
-- Not: Bu dosya bir kez çalıştırılır. Tekrar çalıştırılabilir (idempotent).
-- =====================================================================

USE sanalokul;

-- 1. Mevcut "sistem" tercihlerini koyuya çek
UPDATE kullanicilar
SET tema_tercihi = 'koyu'
WHERE tema_tercihi = 'sistem';

-- 2. ENUM tanımını iki değere indir, varsayılanı "koyu" yap
ALTER TABLE kullanicilar
    MODIFY COLUMN tema_tercihi ENUM('acik', 'koyu') NOT NULL DEFAULT 'koyu';

-- Doğrulama
SELECT tema_tercihi, COUNT(*) AS adet
FROM kullanicilar
GROUP BY tema_tercihi;
