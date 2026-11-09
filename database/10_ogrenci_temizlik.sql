-- =====================================================================
-- Nallıhan MYO Sanal Okul - 10_ogrenci_temizlik.sql
-- Demo verisindeki öğrenci sayısını ikiye indirir.
--
-- Kalan öğrenciler:
--   Burak Yiğit Solmaz  (burak.solmaz@ornek.local)
--   Kaan Tokatlı        (kaan.tokatli@ornek.local)
--
-- Öğretim elemanları ve yönetici hesabı değişmez.
-- Bağımlı kayıtlar (ogrenci_profilleri, ders_kayitlari, teslimler) şemadaki
-- ON DELETE CASCADE kuralı ile birlikte silinir.
--
-- Tekrar çalıştırılabilir (idempotent).
-- =====================================================================

USE sanalokul;

DELETE FROM kullanicilar
WHERE rol_id = (SELECT id FROM roller WHERE ad = 'ogrenci')
  AND eposta NOT IN ('burak.solmaz@ornek.local', 'kaan.tokatli@ornek.local');

-- Doğrulama: kalan öğrenciler
SELECT k.id, k.ad, k.soyad, k.eposta
FROM kullanicilar k
JOIN roller r ON r.id = k.rol_id
WHERE r.ad = 'ogrenci'
ORDER BY k.id;
