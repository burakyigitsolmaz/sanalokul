# Nallıhan MYO Sanal Okul

Ankara Üniversitesi Nallıhan Meslek Yüksekokulu Bilgisayar Programcılığı bölümü
için hazırlanmış ders projesi. Öğrenci, öğretim elemanı ve yönetici panellerini
tek bir web uygulamasında toplar.

Uygulama; ders kaydı, not ve transkript takibi, ödev teslimi, duyuru yönetimi,
dönem raporları, yemek menüsü ve kampüs haberleri gibi akışları içerir.

> **Resmî bir uygulama değildir.** Ders projesi kapsamında geliştirilmiştir;
> okulun resmî bilgi sistemlerinin yerini almaz.

## Ne kullandık

| Katman | Teknoloji |
|---|---|
| Sunucu | Apache (XAMPP) |
| Backend | PHP 8.2+ · PDO prepared statement |
| Veritabanı | MariaDB 10.11 · InnoDB · `utf8mb4_turkish_ci` |
| Frontend | HTML5, CSS3, vanilla JavaScript |
| Dış veri | XLSX (yemek menüsü), WordPress REST API (kampüs haberleri) |
| Sürüm kontrolü | Git / GitHub |

Framework kullanılmadı. Not ortalaması ve harf notu veritabanı trigger'larıyla,
derse kayıt ise kontrollü bir stored procedure ile hesaplanır.

## Kurulum

Kurulum yaklaşık 10 dakika sürer. Komutlar **cmd** üzerinden çalıştırılmalıdır.

### 1. Gereksinimler

- XAMPP (Apache + PHP 8.2 veya üzeri + MariaDB 10.4 veya üzeri)
  - İndirme: <https://www.apachefriends.org/tr/index.html>
- PHP eklentileri: `pdo_mysql`, `mbstring`, `fileinfo`, `zip`, `openssl`, `curl`

### 2. Projeyi indir

GitHub deposunu doğrudan XAMPP klasörüne klonla:

```bat
git clone https://github.com/kullanici/sanalokul.git C:\xampp\htdocs\sanalokul
```

> `kullanici` yerine kendi GitHub kullanıcı adını yaz. Depo adı farklıysa adresi
> ona göre değiştir.

Git kullanmıyorsan GitHub sayfasında **Code → Download ZIP** ile indir, arşivi
çıkar ve klasörü şu konuma taşı:

```text
C:\xampp\htdocs\sanalokul
```

> Klasör adı **`sanalokul`** olmalıdır. Adresler bu klasör adına göre kurulmuştur.

### 3. Veritabanını kur

XAMPP panelinden **MySQL**'i başlat. Ardından cmd'de sırayla çalıştır:

```bat
cd C:\xampp\htdocs\sanalokul

C:\xampp\mysql\bin\mysql.exe -u root < database\00_kurulum.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\01_schema.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\02_views.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\03_triggers.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\04_procedures.sql
C:\xampp\mysql\bin\mysql.exe -u root sanalokul < database\05_seed.sql
```

> MySQL root şifresi varsa `-u root` yerine `-u root -p` yaz.

| Dosya | İçerik |
|---|---|
| `00_kurulum.sql` | `sanalokul` veritabanı + `sanalokul_app` kullanıcısı + izinler |
| `01_schema.sql` | 20 tablo, kısıtlar, yabancı anahtarlar, indeksler |
| `02_views.sql` | 8 görünüm (not özeti, başarı, doluluk, dönem, bölüm vb.) |
| `03_triggers.sql` | 4 tetikleyici (not hesabı, kontenjan, log) |
| `04_procedures.sql` | 4 prosedür (derse kayıt, dönem ortalaması, raporlar) |
| `05_seed.sql` | Demo veri: bölüm, dönem, ders, kullanıcı, not, ödev, duyuru |

**Sıra önemlidir:** `00 → 01 → 02 → 03 → 04 → 05`.

### 4. Ayar dosyasını oluştur

`config/config.local.php` dosyasını oluştur ve içine şunu yaz:

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'sanalokul',
    'user' => 'sanalokul_app',
    'pass' => 'SanalOkul_2026!App',
];
```

Bu dosya `.gitignore` içindedir; GitHub'dan gelen projede **bulunmaz**, her
kurulumda elle oluşturulur. `00_kurulum.sql` içindeki şifreyi değiştirirsen
buradaki `pass` değerini de aynı yap.

### 5. Çalıştır

XAMPP panelinden **Apache** ve **MySQL**'i başlat, tarayıcıda aç:

```text
http://localhost/sanalokul/
```

### 6. Telefondan açma

Telefonu bilgisayarla **aynı Wi-Fi ağına** bağla. Bilgisayarda **cmd** açıp şunu
çalıştır:

```bat
ipconfig
```

Çıktıda **Wi-Fi** başlığı altındaki `IPv4` satırındaki adresi al (örnek:
`192.168.1.25`). Telefonun tarayıcısına şu adresi yaz:

```text
http://192.168.1.25/sanalokul/
```

> `localhost` telefonda çalışmaz; o adres telefonun kendisini gösterir. IPv4
> adresini yazman gerekir.

**Açılmazsa:**

- Telefonun mobil veri yerine Wi-Fi'de olduğundan emin ol.
- Windows güvenlik duvarı sorarsa Apache için **Özel ve genel ağlarda izin ver**
  seçeneğini işaretle.
- Okul, yurt veya kafe Wi-Fi'ları cihazların birbirini görmesini engelleyebilir.
  Bu durumda bilgisayarda **Ayarlar → Ağ ve internet → Mobil etkin nokta**'yı aç,
  telefonu bu ağa bağla ve `http://192.168.137.1/sanalokul/` adresini dene.
- Adres açılmıyorsa IPv4 değişmiş olabilir; `ipconfig` ile yeniden kontrol et.

### 7. Kurulumu doğrula

Sağlık kontrolü sayfası veritabanı nesnelerini ve PHP eklentilerini listeler:

```text
http://localhost/sanalokul/kurulum.php
```

Beklenen değerler: **20 tablo, 8 view, 4 trigger, 4 prosedür**. Hepsi yeşilse
kurulum tamamdır.

### Sık karşılaşılan hatalar

| Belirti | Çözüm |
|---|---|
| `Access denied for user 'sanalokul_app'` | `00_kurulum.sql` çalışmamış veya `config.local.php` şifresi farklı |
| `Unknown database 'sanalokul'` | `00_kurulum.sql` atlanmış; sırayı baştan uygula |
| `Table ... doesn't exist` | SQL dosyaları sıra dışı çalıştırılmış; `00 → 05` sırasını koru |
| Sayfa tamamen boş | `config.local.php` yok veya PHP sözdizimi hatası; Apache `error.log` dosyasına bak |
| Türkçe karakterler bozuk | Veritabanı `utf8mb4_turkish_ci` olmalı; `01_schema.sql` ile yeniden kur |

## ER diyagramı

![Veritabanı ER diyagramı](docs/er-diyagram.png)

20 tablo ve yabancı anahtar ilişkileri. Diyagram, yönetici panelindeki
**Veritabanı** sayfasında da görüntülenebilir.

## Demo hesaplar

Tüm demo hesapların şifresi: `Sanal1234!`

| Rol | Ad Soyad | E-posta |
|---|---|---|
| Yönetici | Sistem Yönetici | `yonetici@ornek.local` |
| Öğrenci | Burak Yiğit Solmaz | `burak.solmaz@ornek.local` |
| Öğrenci | Kaan Tokatlı | `kaan.tokatli@ornek.local` |
| Öğretim elemanı | Ayhan Aydın | `ayhan.aydin@ornek.local` |
| Öğretim elemanı | Ufuk Tanyeri | `ufuk.tanyeri@ornek.local` |
| Öğretim elemanı | Salih Erdurucan | `salih.erdurucan@ornek.local` |
| Öğretim elemanı | Güney Uğurlu | `guney.ugurlu@ornek.local` |

Giriş sayfası: `http://localhost/sanalokul/giris.php`

Giriş ekranındaki demo kartından bir hesaba tıklayınca e-posta ve şifre otomatik
dolar; ardından **Giriş Yap** düğmesine basman yeterlidir.

## Proje notu

Bu uygulama tamamen Ankara Üniversitesi Nallıhan MYO Bilgisayar Programcılığı
ders projesi için yapılmıştır. Resmî bir uygulama değildir; okulun resmî bilgi
sistemlerinin yerini almaz.

İçindeki tüm e-posta, telefon, şifre ve akademik veriler demo amaçlıdır; gerçek
kişisel veri değildir. Yayına almadan önce demo hesaplar ve veritabanı şifresi
değiştirilmelidir.
