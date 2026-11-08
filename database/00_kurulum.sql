-- =====================================================================
-- Nallıhan MYO Sanal Okul - Kurulum (veritabanı + uygulama kullanıcısı)
-- Dosya : 00_kurulum.sql
-- Sıra  : Bu dosya EN BAŞTA, root ile çalıştırılır.
--
-- Kullanım:
--   C:\xampp\mysql\bin\mysql.exe -u root < database/00_kurulum.sql
--
-- Not: Şifre demo amaçlıdır. Gerçek sunumda değiştirip
--      config/config.local.php içine aynı şifreyi yazın.
-- =====================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `sanalokul`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_turkish_ci;

CREATE USER IF NOT EXISTS 'sanalokul_app'@'localhost'
    IDENTIFIED BY 'SanalOkul_2026!App';

-- Uygulamanın günlük işlemleri (prepared statement + prosedür çağrıları).
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `sanalokul`.* TO 'sanalokul_app'@'localhost';

-- Yedekleme betiğinin view / trigger / prosedür tanımlarını okuyabilmesi için.
-- (mysqldump --routines --triggers --events)
GRANT TRIGGER, EVENT, SHOW VIEW ON `sanalokul`.* TO 'sanalokul_app'@'localhost';
GRANT SELECT ON `mysql`.`proc` TO 'sanalokul_app'@'localhost';

FLUSH PRIVILEGES;

-- Sonraki adımlar (sırayla):
--   mysql -u root sanalokul < database/01_schema.sql
--   mysql -u root sanalokul < database/02_views.sql
--   mysql -u root sanalokul < database/03_triggers.sql
--   mysql -u root sanalokul < database/04_procedures.sql
--   mysql -u root sanalokul < database/05_seed.sql
--   mysql -u root sanalokul < database/06_seed_faz3.sql
--   mysql -u root sanalokul < database/07_rapor.sql
--   mysql -u root sanalokul < database/08_performans.sql
