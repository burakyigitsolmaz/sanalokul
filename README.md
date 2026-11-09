# Nallıhan MYO Sanal Okul

Ankara Üniversitesi Nallıhan Meslek Yüksekokulu Bilgisayar Programcılığı
ders projesi. PHP + MySQL (MariaDB) ile geliştirilmiş, rol bazlı sanal okul
uygulaması.

> **Resmî bir uygulama değildir.** Ders projesi kapsamında geliştirilmiştir.
> İçindeki e-posta, telefon ve şifreler demo amaçlıdır.

## Hızlı başlangıç

```bat
cd C:\xampp\htdocs\sanalokul

C:\xampp\mysql\bin\mysql.exe -u root < database\00_kurulum.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\01_schema.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\02_views.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\03_triggers.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\04_procedures.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\05_seed.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\06_seed_faz3.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\07_rapor.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\08_performans.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\09_tema.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\10_ogrenci_temizlik.sql
```

`config/config.local.php` dosyasını oluşturun (bkz. `docs/TESLIM.md`), Apache ve
MySQL'i başlatıp `http://localhost/sanalokul/` adresini açın.

## Demo hesaplar

| Rol | E-posta | Şifre |
|---|---|---|
| Yönetici | `yonetici@ornek.local` | `Sanal1234!` |
| Öğrenci | `burak.solmaz@ornek.local` | `Sanal1234!` |
| Öğretim elemanı | `ayhan.aydin@ornek.local` | `Sanal1234!` |

## Dokümanlar

- [Teslim dokümanı](docs/TESLIM.md) — kurulum, özellikler, güvenlik, yedekleme
- [Geliştirme günlüğü](docs/ILERLEME.md) — faz faz yapılanlar ve testler

## Yedekleme

```bat
C:\xampp\php\php.exe scripts\yedekle.php
```

Yedekler `yedekler/` klasörüne `.sql.gz` olarak yazılır; web'den erişime kapalıdır.
