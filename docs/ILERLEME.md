# İlerleme Raporu

Bu dosya her fazın sonunda güncellenir. Yapılanlar, kalanlar ve bilinen sorunlar burada tutulur.

## Faz 0 — Hazırlık ve kurulum ✅

**Tarih:** 6 Kasım 2026

### Yapılanlar

- XAMPP servisleri kontrol edildi (Apache 2.4 + MySQL/MariaDB 10.11.16) ve çalışır durumda.
- `php.ini` düzenlendi: `extension=zip` açıldı (yemek listesi `.xlsx` okumak için gerekli),
  `date.timezone` → `Europe/Istanbul`.
- Apache yeni `php.ini` ile yeniden başlatıldı.
- Veritabanı oluşturuldu: `sanalokul` (`utf8mb4` / `utf8mb4_turkish_ci`).
- Uygulama kullanıcısı oluşturuldu: `sanalokul_app` (root hesabı uygulamada kullanılmıyor).
- Proje klasör yapısı kuruldu: `admin/`, `ogretmen/`, `ogrenci/`, `assets/`, `src/`, `templates/`,
  `config/`, `cron/`, `database/`, `uploads/`, `docs/`.
- `config/config.php` (genel ayarlar) ve `config/config.local.php` (gizli DB bilgileri) yazıldı.
- `src/Database.php` (PDO, `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`, `utf8mb4`) yazıldı.
- `src/helpers.php` (`config()`, `e()`, `url()`, `tarih_tr()`) yazıldı.
- Hassas klasörlere `.htaccess` ile `Require all denied` kondu:
  `src/`, `config/`, `database/`, `cron/`, `templates/`, `uploads/`.
- `.gitignore` eklendi (`config.local.php` ve `uploads/` hariç tutuluyor).
- Kurulum test ekranı hazır: `http://localhost/sanalokul/`

### Doğrulama

- Tarayıcıda "Veritabanına bağlandı" mesajı görünüyor.
- PHP 8.3.36, MariaDB 10.11.16, `utf8mb4_turkish_ci`, zaman dilimi `Europe/Istanbul`.
- Zorunlu PHP eklentileri açık: `pdo_mysql`, `mbstring`, `zip`, `openssl`, `fileinfo`.
- `curl` eklentisi Apache altında yüklenmiyor (XAMPP'ta `php_curl.dll` bağımlılık sorunu);
  CLI'de çalışıyor. Dış veri çekmede `file_get_contents` + stream kullanılacak, yani Faz 6 etkilenmiyor.

### Demo kullanıcı

- DB kullanıcısı: `sanalokul_app` / şifre `config/config.local.php` içinde (repoya girmiyor).

### Notlar

- `uploads/` klasöründe PHP çalıştırılması ayrıca engellendi.
- Faz 1'de oluşturulacak tablolar için `database/` klasörü hazır.

## Faz 1 — Veritabanı ✅

**Tarih:** 6 Kasım 2026

### Yapılanlar

- `database/01_schema.sql` yazıldı: **20 tablo**, InnoDB, `utf8mb4_turkish_ci`.
  Tablolar: `roller`, `bolumler`, `donemler`, `kullanicilar`, `ogrenci_profilleri`,
  `ogretim_elemani_profilleri`, `dersler`, `ders_acilislari`, `ders_kayitlari`,
  `ders_programi`, `ders_icerikleri`, `duyurular`, `odevler`, `teslimler`, `notlar`,
  `islem_loglari`, `giris_denemeleri`, `yemek_menuleri`, `dis_haberler`,
  `kaynak_senkron_durumu`.
- `database/02_views.sql`: 3 view — `v_ogrenci_not_ozeti`, `v_ders_basari_istatistik`,
  `v_bugunun_programi`.
- `database/03_triggers.sql`: 4 trigger — not ortalaması/harf notu hesaplama (INSERT + UPDATE),
  not değişikliği logu, derse kayıt öncesi kontenjan ve "öğrenci mi?" kontrolü.
- `database/04_procedures.sql`: 2 stored procedure — `sp_derse_kayit_ol`
  (kontenjan + program çakışması + mükerrer kayıt + transaction), `sp_donem_ortalamasi`
  (kredi ağırlıklı dönem ortalaması).
- `database/05_seed.sql` ile gerçek akademik veriler yüklendi:
  - 4 gerçek öğretim elemanı (Dr. Öğr. Üyesi Ayhan AYDIN, Öğr. Gör. Dr. Ufuk TANYERİ,
    Öğr. Gör. Salih ERDURUCAN, Öğr. Gör. Güney UĞURLU).
  - 16 gerçek Bilgisayar Programcılığı dersi, 16 ders açılışı (5 geçmiş + 11 aktif dönem).
  - 25 kullanıcı (1 yönetici, 4 hoca, 10 birinci sınıf, 10 ikinci sınıf).
  - 160 ders kaydı, 95 not, 14 ders programı satırı, 5 duyuru, 4 ödev, 5 teslim,
    11 ders içeriği, 3 günlük yemek menüsü.
- `docs/er_uret.py` ile ER diyagramı üretici yazıldı; çıktılar `docs/ER.svg` ve `docs/ER.png`.
- `index.php` Faz 1 durum ekranına çevrildi: tablo/view/trigger/prosedür sayıları canlı gösteriliyor.

### Doğrulama (çalıştırılan testler)

| Test | Sonuç |
|---|---|
| Tablo sayısı | 20 |
| View / trigger / prosedür | 3 / 4 / 2 |
| Türkçe karakterler (Aydın, Uğurlu, Kılıç) | Doğru |
| Trigger: 72.50 vize + 88.00 final | Ortalama 81.80 → **BB** |
| Trigger: not güncelleme logu | `islem_loglari` satırı yazıldı |
| `sp_derse_kayit_ol` — başarılı kayıt | Başarılı |
| `sp_derse_kayit_ol` — mükerrer kayıt | "Bu derse zaten kayıtlısınız." |
| `sp_derse_kayit_ol` — program çakışması | "…başka bir dersle çakışıyor." |
| `sp_derse_kayit_ol` — kontenjan dolu | "Ders kontenjanı doldu." |
| Kontenjan trigger'ı doğrudan INSERT'te | `ERROR 1644` |
| Öğrenci olmayan kullanıcıyla kayıt | `ERROR 1644` |
| `sp_donem_ortalamasi` | 69.15 (4 ders) |

### Bilinen notlar

- `notlar` tablosunda id aralığı 1..126 ama 96 satır var (30'luk boşluk). İşlevsel sorun değil,
  `AUTO_INCREMENT` devam ediyor.
- `teslimler.dosya_yolu` demo yollarıdır, gerçek dosya yok. Faz 5'te indirme ekranında
  "dosya bulunamadı" durumu ele alınacak.
- `curl` Apache altında yok; dış veri çekme `file_get_contents` + stream ile yapılacak.
- Şifreler demo amaçlı tek hash (`Sanal1234!`) ile yüklendi.

### Sonraki faz

Faz 2 — giriş sistemi, oturum yönetimi, rol bazlı yönlendirme, ortak şablon ve tema.

## Faz 2 — Giriş, şablon ve tema ✅

**Tarih:** 6 Kasım 2026

### Yapılanlar

- `src/bootstrap.php` eklendi: yardımcılar + `Database` + `Auth` + zaman dilimi tek noktadan yükleniyor.
- `src/Auth.php` yazıldı:
  - Güvenli oturum: `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, 2 saat hareketsizlik sonrası otomatik çıkış.
  - `password_verify` ile giriş, başarılı girişte `session_regenerate_id(true)`.
  - CSRF token üretimi/doğrulaması (`csrfAlani()`, `csrfDogrula()`).
  - Giriş denemesi kaydı ve **iki katmanlı kilit**: aynı hesap için 5, aynı ağ (IP) için 25 hatalı deneme.
  - Rol kontrolü (`rolGerekli`) ve role göre panel adresi (`panelYolu`).
  - Tema tercihi okuma/kaydetme.
- `giris.php`, `cikis.php`, `tema.php` eklendi. Çıkış yalnızca POST + CSRF ile çalışıyor.
- Ortak şablon: `templates/head.php` (tema başlatma), `header.php` (üst çubuk, menü, kullanıcı alanı), `footer.php`.
- Tema: koyu varsayılan, açık ve "sistem" seçenekleri. Tercih `localStorage` + giriş yapılmışsa `kullanicilar.tema_tercihi` alanında saklanıyor.
- Mobil menü, otomatik kapanan bildirimler ve çift gönderim koruması (`assets/js/app.js`).
- Ana sayfa yenilendi: hero, bugünün ders programı, son duyurular, günün yemek menüsü, özellik kartları.
- Üç panel iskeleti: `ogrenci/`, `ogretmen/`, `admin/`.
  - Öğrenci: aktif dersler, not tablosu, kredi ağırlıklı ortalama, harf notu, bugünün programı.
  - Öğretmen: verdiği dersler, öğrenci sayıları, not bekleyen öğrenci sayısı, ilgili duyurular.
  - Yönetici: genel sayılar, rol dağılımı, ders başarı istatistiği, son işlemler.
- `403.php` / `404.php` ve Apache `ErrorDocument` tanımları.
- Okul sitesinden indirilen görseller `assets/img/` altına eklendi: `logo.png`, `kampus.jpg` (1920×1080'e kırpıldı), `mezuniyet.jpg`.
- `.htaccess`'e güvenlik başlıkları ve yardımcı dosya koruması eklendi.

### Doğrulama

| Test | Sonuç |
|---|---|
| Burak ile giriş | `/ogrenci/` paneline yönlendi |
| Ayhan Aydın ile giriş | `/ogretmen/` paneline yönlendi |
| Yönetici ile giriş | `/admin/` paneline yönlendi |
| Öğretmen hesabıyla `/admin/` | 403 |
| Giriş yapmadan `/ogrenci/` | `giris.php`'ye yönlendirme |
| 5 hatalı şifre sonrası | Hesap kilidi mesajı (15 dakika) |
| Yanlış CSRF ile çıkış | Reddedildi, oturum açık kaldı |
| Doğru CSRF ile çıkış | Oturum kapandı, `giris.php`'ye dönüş |
| `tema.php` GET | 405 |
| Geçersiz tema değeri | 422 |
| 404 / 403 / `kurulum.php` koruması | Beklenen kodlar |
| Mobil görünüm (390 px) | Yatay taşma yok, menü ve tema düğmeleri çalışıyor |
| Açık tema | Renkler ve kontrast doğru |

### Bilinen notlar

- Demo hesaplar giriş ekranında listeleniyor; sunumda kaldırılabilir.
- Giriş denemeleri `giris_denemeleri`, oturum hareketleri `islem_loglari` tablosuna yazılıyor.
- `kurulum.php` artık `.htaccess` ile korunuyor; geliştirme sırasında gerekirse kural kaldırılabilir.
- Paneller Faz 2 itibarıyla özet ekran; ders/not/ödev yönetimi sonraki fazlarda eklenecek.

### Sonraki faz (Faz 2 sonunda)

Faz 3 — öğrenci panelinin detaylandırılması (ders detayı, not geçmişi, ödevler, ders içerikleri).

## Faz 3 — Öğrenci paneli ✅

**Tarih:** 7 Kasım 2026

### Yapılanlar

- **Ortak panel düzeni:** `templates/panel_bas.php` / `panel_son.php`. Sol sabit menü (248 px),
  kullanıcı kartı, 7 menü öğesi ve mobilde yatay kaydırmalı "chip" menü. `.panel-duzen`,
  `.panel-yan`, `.panel-menu` sınıfları `assets/css/app.css` bölüm 21'de.
- **`src/Ogrenci.php`:** öğrenci tarafının tüm veri erişimi tek sınıfta. Bütün sorgular
  `ogrenci_id` ile sınırlandırılıyor (IDOR koruması). Dersler, ders ayrıntısı, haftalık program,
  ders içerikleri, ders ödevleri, tüm ödevler, not geçmişi, transkript, duyurular,
  harf notu ↔ 4'lük puan dönüşümü, kalan süre hesabı.
- **8 öğrenci sayfası:**
  - `ogrenci/index.php` — 4 özet kart (ders, notu girilen, dönem ortalaması, bekleyen ödev),
    bugünün programı, yaklaşan ödevler, ders listesi, son duyurular.
  - `ogrenci/dersler.php` — ders kartları (hoca, kredi/AKTS, içerik ve ödev sayacı, ortalama)
    + geçmiş dönemler tablosu.
  - `ogrenci/ders.php` — 4 not kartı, haftalık program, 8 haftalık içerik zaman çizelgesi,
    ders bilgileri, ödevler ve derse ait duyurular. Kayıt başkasına aitse **404**.
  - `ogrenci/program.php` — 5 günlük ızgara, bugünün sütunu vurgulu.
  - `ogrenci/notlar.php` — dönem dönem not dökümü, kredi ağırlıklı ortalama, harf anahtarı.
  - `ogrenci/transkript.php` — tüm notlu dersler, GANO (4'lük), yazdırma düzeni.
  - `ogrenci/odevler.php` — durum sekmeleri (Tümü / Bekleyen / Teslim / Puanlanan / Süresi geçen),
    dosya yükleme formu, yeniden teslim, geri bildirim gösterimi.
  - `ogrenci/duyurular.php` — önemli duyurular açık, kapsam ve yazar bilgisiyle.
- **Dosya teslimi güvenliği:** CSRF zorunlu, 10 MB sınırı, uzantı listesi, `finfo` ile gerçek MIME
  kontrolü, rastgele dosya adı, `uploads/.htaccess` ile doğrudan erişim kapalı,
  puanlanmış ödevde yeniden teslim engeli.
- **Seed zenginleştirme:** `database/06_seed_faz3.sql` (idempotent, tekrar import edilebilir):
  19 yemek menüsü (9–30 Kasım 2026 hafta içi), 8 yeni duyuru (toplam 13), 8 yeni ödev (toplam 12),
  9 ders × 8 hafta = 72 ders içeriği, 3 yeni teslim (toplam 8).
- **Ana sayfa:** bugünün menüsü yoksa en yakın ileri tarihli menü "Sonraki Yemek Menüsü" olarak
  gösteriliyor (7 Kasım Cumartesi olduğu için bugün menü yok).
- **Yazdırma:** transkript ve notlar için `@media print` düzeninde menü/butonlar gizleniyor.

### Doğrulama

| Test | Sonuç |
|---|---|
| 30 PHP dosyası `php -l` | Sözdizimi temiz |
| Öğrenci ile giriş → `/ogrenci/` | 200, "Merhaba, Burak" |
| 8 öğrenci sayfası | 200, beklenen içerik, PHP uyarısı yok |
| Başka öğrencinin dersi (`ders.php?id=104`) | 404 (IDOR engellendi) |
| Hoca hesabıyla `/ogrenci/notlar.php` | 403 |
| Giriş yapmadan `/ogrenci/dersler.php` | `giris.php`'ye yönlendirme |
| PDF teslimi | 302 → kayıt `teslimler` tablosuna ve dosya diske yazıldı |
| `.txt` dosyası | Reddedildi: "Bu dosya türü kabul edilmiyor" |
| Sahte MIME (metin içerik `.pdf` adıyla) | Reddedildi: "Dosya içeriği ile uzantısı uyuşmuyor" |
| Puanlanmış ödeve yeniden teslim | Reddedildi: "Puanlanmış bir ödevi yeniden teslim edemezsiniz" |
| Masaüstü görünümü (1280 px) | Panel, ders, ödev, notlar, program ekranları doğru |
| Mobil görünüm (390 px) | Yatay taşma yok; menü chip'lere dönüşüyor |
| 2. sınıf öğrencisi (Deniz) notlar + transkript | Dönem ortalaması ve GANO doğru hesaplanıyor |

### Bulunan ve düzeltilen hatalar

- `ogrenci/odevler.php` içinde sayaç anahtarı `bekleyen` yazılması gerekirken `bekliyor`
  kullanılmıştı; sekme sayacı boş kalıyordu. Düzeltildi.
- Mobilde ızgara sütunları `1fr` yüzünden taşıyordu; tüm panel ızgaraları `minmax(0, 1fr)` yapıldı.
- PowerShell'in `Set-Content -Encoding UTF8` komutu PHP dosyası başına BOM ekleyip
  "strict_types must be the first statement" hatası veriyordu; dosya BOM'suz UTF-8 ile yeniden yazıldı.

### Bilinen notlar

- Yüklenen ödev dosyaları `uploads/teslimler/` altında; indirme ucu Faz 4'te (öğretmen paneli)
  eklenecek. Şimdilik dosyalar yalnızca sunucuda duruyor.
- Test artıkları temizlendi: yüklenen deneme dosyası silindi, `06_seed_faz3.sql` yeniden import
  edildi, `islem_loglari` ve `giris_denemeleri` boşaltıldı.
- Yemek menüsü şu an 30 Kasım 2026'ya kadar dolu. Faz 8'de otomatik senkron eklenecek.

### Sonraki faz (Faz 3 sonunda)

Faz 4 — öğretmen paneli: not girişi, ödev değerlendirme (puan + geri bildirim + dosya indirme),
ders içeriği ve duyuru ekleme.

## Faz 4 — Öğretim elemanı paneli ✅

**Tarih:** 7 Kasım 2026

### Yapılanlar

- **`src/Ogretmen.php`:** öğretim elemanı tarafının tüm veri erişimi tek sınıfta. Her sorgu
  `da.ogretim_elemani_id = :hoca` ile sınırlandırılıyor; hoca başka dersin kaydına erişemiyor.
  Dersler, ders ayrıntısı, öğrenci listesi, not kaydetme, ödevler, ödev ekleme/silme,
  teslim listesi, değerlendirme, dosya indirme kaydı, içerik ve duyuru yönetimi.
- **Ortak panel düzeni:** `templates/ogretmen_bas.php` / `ogretmen_son.php`. Sol menü:
  Panelim, Derslerim, Ödevler, Duyurular, Ders Programı. Mobilde aynı yatay chip menü.
- **7 sayfa:**
  - `ogretmen/index.php` — 4 özet kart (aktif ders, öğrenci, not bekleyen, değerlendirme bekleyen),
    ders listesi, bekleyen teslimler, son duyurular, hızlı işlemler.
  - `ogretmen/dersler.php` — aktif/geçmiş ders kartları ve özet tablo.
  - `ogretmen/ders.php` — 4 sekmeli ders yönetimi:
    - **Öğrenciler & Notlar:** satır içi vize/final girişi, ortalama ve harf notu anında görünür.
    - **Ödevler:** ödev ekleme (başlık, açıklama, son teslim, puan), teslim istatistikleri, silme.
    - **İçerikler:** hafta bazlı içerik ekleme/güncelleme ve silme (1–16. hafta).
    - **Duyurular:** ders duyurusu ekleme, önemli işaretleme, geçerlilik tarihi, silme.
  - `ogretmen/odevler.php` — tüm derslerin ödevleri, teslim/bekleyen sayaçları.
  - `ogretmen/odev.php` — ödev değerlendirme: teslim eden/etmeyen liste, dosya indirme,
    puan + geri bildirim, ortalama puan özeti.
  - `ogretmen/duyurular.php` — ders seçerek duyuru yayınlama ve yayındaki duyuruları yönetme.
  - `ogretmen/program.php` — 5 günlük haftalık program, bugün vurgulu.
  - `ogretmen/indir.php` — teslim dosyasını yalnızca dersin hocasına indirtir.
- **Güvenlik:** tüm formlarda CSRF; not ve puan aralığı doğrulaması (0–100); başka hocanın
  dersi/ödevi/teslimi için **404**; öğrenci rolü için **403**; dosya indirmede `realpath`
  kontrolü (uploads dışına çıkma engeli); dosya adı güvenli biçimde üretiliyor.
- **Trigger entegrasyonu:** not kaydında `@oturum_kullanici_id` ve `@oturum_ip` set edilerek
  `trg_notlar_log` trigger'ının işlem loguna kullanıcı bilgisiyle yazması sağlandı.
  Ortalama/harf notu hâlâ veritabanı trigger'ı tarafından hesaplanıyor (uygulama hesaplamıyor).
- **Silme tutarlılığı:** ödev silinince `teslimler` FK ile siliniyor, diskteki teslim dosyaları
  da `uploads/` içinden temizleniyor.

### Doğrulama

| Test | Sonuç |
|---|---|
| 41 PHP dosyası `php -l` | Sözdizimi temiz |
| Hoca ile giriş → `/ogretmen/` | 200, panel açıldı |
| 10 panel sayfası (4 sekme dahil) | 200, beklenen içerik, PHP uyarısı yok |
| Başka hocanın dersi `ders.php?id=7` | 404 |
| Başka hocanın ödevi `odev.php?id=8` | 404 |
| Öğrenci → `/ogretmen/dersler.php` | 403 |
| Not girişi (kayıt 88: 70/80) | Ortalama **76,00**, harf **CB** (trigger) |
| Geçersiz not (101) | Reddedildi, eski değer korundu |
| Ödev değerlendirme (teslim 2 → 88) | DB'ye puan + geri bildirim + zaman yazıldı |
| Geçersiz puan (120) | Reddedildi (0–100), puan değişmedi |
| Ödev ekle → sil | 13 numaralı ödev eklendi, silindi; teslim/dosya temizlendi |
| İçerik ekle → sil | Hafta 12 eklendi, silindi |
| Duyuru ekle → sil | 14 numaralı duyuru eklendi, silindi |
| Dosya indirme (mevcut dosya) | 200, `Content-Disposition: attachment`, içerik doğru |
| Dosya yok / başka hocanın teslimi | 404 |
| Öğrenci → `indir.php` | 403 |
| Masaüstü (1280 px) | Panel, ders, ödev, ödevler, duyuru ekranları doğru |
| Mobil (390 px) | Sayfa düzeyinde yatay taşma yok; tablolar kendi içinde kayıyor, menü chip |

### Bulunan ve düzeltilen hatalar

- `ogretmen/odev.php` içindeki yönlendirme closure'ı `$odevId` değişkenini `use` ile
  yakalamıyordu; başarılı kayıttan sonra `odev.php?id=0` adresine gidiyordu. `use ($odevId)`
  eklendi.
- `ogretmen/indir.php` dosya yolunu `uploads/uploads/...` olarak birleştiriyordu; `uploads/`
  öneki ayıklanarak düzeltildi. Ayrıca olmayan `templates/404.php` yerine kökteki `404.php`
  kullanıldı.

### Bilinen notlar

- Ders açılışı/dönem/kullanıcı oluşturma işlemleri yönetici panelinde (Faz 5) olacak.
- Hoca yalnızca kendi derslerine duyuru ekleyebilir; genel duyuru yöneticiye ait.
- Ödev silme geri alınamaz; tarayıcıda `confirm()` onayı var.
- Test artıkları temizlendi; `notlar`, `teslimler`, `odevler`, `ders_icerikleri`, `duyurular`
  sayıları seed ile aynı (sırasıyla 95, 8, 12, 72, 13). Log tabloları boşaltıldı.

### Arayüz sadeleştirmesi (7 Kasım 2026)

- Tüm ekranlardan renkli emoji ikonlar kaldırıldı (23 dosyada 128 kullanım). Menü, kart
  başlıkları, boş durumlar ve meta satırları artık yalnızca metin.
- Yalnızca işlevsel glifler korundu: `☰` mobil menü, `✕` kapat/sil, `←` / `→`, `＋`.
- Boş kalan ikon kapsayıcıları `app.css` bölüm 24 ile gizlendi.
- Uzun öğretim elemanı unvanı yan menüde 2 satıra kadar gösteriliyor; "Dr. Öğr. Üyesi
  Ayhan A…" şeklindeki kesilme giderildi.

## Faz 5 — Yönetici paneli ✅

**Tarih:** 7 Kasım 2026

### Yapılanlar

- **`src/Yonetici.php`:** yönetici tarafının tüm veri erişimi tek sınıfta. CRUD işlemleri (kullanıcı, bölüm, dönem, ders, ders açılışı), derse atama (`sp_derse_kayit_ol` prosedürü aracılığıyla), genel duyuru, işlem logu ve CSV öğrenci içe aktarma. İşlemler transaction ile korunuyor.
- **Ortak panel düzeni:** `templates/admin_bas.php` / `admin_son.php`. Sol menü: 10 öğeli (Panelim, Kullanıcılar, Bölümler, Dönemler, Dersler, Ders Açılışları, Derse Atama, Duyurular, İşlem Logları, CSV Aktarma).
- **10 sayfa:**
  - `admin/index.php` — genel bakış (kullanıcı/ders/ders açılışı/kayıt/duyuru sayıları, rol dağılımı, aktif dönem başarı tablosu, son işlemler, hızlı işlemler).
  - `admin/kullanicilar.php` — kullanıcı listesi (rol arama, metin arama), aktif/pasif değiştirme, silme.
  - `admin/kullanici.php` — kullanıcı ekle/düzenle formu; rol bazlı profil alanları (öğrenci: bölüm/sınıf/giriş yılı; öğretim elemanı: bölüm/unvan) JS ile dinamik gösterim/gizleme.
  - `admin/bolumler.php` — bölüm CRUD (kod, ad) ve ders/öğrenci/hoca sayacı.
  - `admin/donemler.php` — dönem CRUD (ad, tarih aralığı, aktif bayrak); aynı anda tek aktif dönem.
  - `admin/dersler.php` — ders CRUD (bölüm, kod, ad, kredi, AKTS, zorunlu, sınıf) + bölüm filtresi.
  - `admin/acilislar.php` — ders açılış CRUD (ders, dönem, hoca, şube, kontenjan) + dönem filtresi ve doluluk çubuğu.
  - `admin/atama.php` — belirli açılış için kayıtlı öğrenci listesi + aday listesi (kayıt olmayan öğrenciler); `sp_derse_kayit_ol` ile kayıt, kayıt silme.
  - `admin/duyurular.php` — genel duyuru CRUD (başlık, içerik, önemli, bitiş tarihi); genel/ders kapsamı filtresi.
  - `admin/loglar.php` — işlem logu (işlem, kişi, tablo, kayıt ID, IP, zaman) + arama ve tablo filtresi + sayfalama.
  - `admin/csv.php` — CSV öğrenci içe aktarma (numara, ad, soyad, eposta, bolum_kod, sinif, giris_yili sütunları); BOM temizleme, varsayılan şifre, başarı/hata özeti.
- **CSS:** `assets/css/app.css` bölüm 25 eklendi (`.filtre-cubuk`, `.filtre-alan`, `.filtre-eylem`, `.satir-eylem`, `.form-eylem`, `.satir-liste`, `.doluluk`, `.stat-kart`, `.sayfa-basligi`, `.hizli-islem`, mobil düzenlemeler).
- **Güvenlik:** tüm formlarda CSRF; rol kontrolü (`yonetici`); IDOR koruması (geçersiz id → 404); silme işlemlerinde `confirm()` onayı; bölüm/dönem silmede bağlı kayıt varsa hata mesajı.

### Doğrulama

| Test | Sonuç |
|---|---|
| 53 PHP dosyası `php -l` | Sözdizimi temiz |
| Yönetici ile giriş → `/admin/` | 200, panel açıldı |
| 10 admin sayfası | 200, beklenen içerik, hata yok |
| Rol kontrolü (öğrenci/hoca → admin) | 403 |
| Bölüm ekle → düzenle → sil | CRUD çalışıyor, FK koruması aktif |
| Dönem ekle → aktif yap → sil | Tek aktif dönem kısıtlaması çalışıyor |
| Ders ekle → düzenle → sil | CRUD çalışıyor, bağlı açılışlar CASCADE siliniyor |
| Ders açılışı ekle → doluluk çubuğu | Kontenjan ve kayıt sayısı doğru gösteriliyor |
| Derse öğrenci atama → sil | `sp_derse_kayit_ol` ile kayıt, kaldırma çalışıyor |
| Genel duyuru ekle → sil | CRUD çalışıyor |
| İşlem logu → arama/sayfalama | Loglar listeleniyor, filtreleme çalışıyor |
| CSV yükle (örnek dosya) | Öğrenci ekleniyor, hatalı satırlar raporlanıyor |
| Masaüstü (1366 px) | Tüm admin ekranları doğru görünüyor |
| Mobil (390 px) | Yatay taşma yok, filtre çubuğu dikey doğru |

### Bilinen notlar

- Duyuru düzenleme şu anda "yeni olarak ekle" mantığıyla çalışıyor (ayrı `duzenle` işlemi yok; mevcut kaydı silip yeni eklemek veya ilerleyen fazda tam düzenleme eklenebilir).
- CSV içe aktarmada aynı numara/e-posta varsa `uq_kullanici_*` kısıtlamasıyla hata veriyor (kullanıcıya anlaşılır mesaj veriliyor).
- `islem_logları` tablo sayısı arttıkça loglar sayfası sayfalama ile sınırlı tutuluyor (25 kayıt/sayfa).

## Faz 8 — Veri Senkronizasyonu ✅

**Tarih:** 7 Kasım 2026

### Yapılanlar

- **`src/Senkron.php`:** okul kaynaklarından gerçek veri çeken senkron katmanı. Kaynaklar:
  - **Yemek h:** Ankara Üniversitesi SKS aylık menü dosyası (`sksbasvuru.ankara.edu.tr/kayit/moduller/yemeklistesi/aylikmenu.php`, `application/vnd...spreadsheetml.sheet`). `ZipArchive` ile açılır; `xl/sharedStrings.xml` + `xl/worksheets/sheet1.xml` okunur. Excel seri tarihi → `Y-m-d` (`1899-12-30` tabanı, `UTC`). Her menü hücresi `(123 kkal)` kalıbına ayrılır; yalnız `toplam_kcal` `1364 KKAL` sütunundan okunur.
  - **Haber h:** Nallıhan MYO WordPress REST API (`nallihanmyo.ankara.edu.tr/wp-json/wp/v2/posts?per_page=10&_fields=...`). `title.rendered` içindeki HTML varlıklıkları çözülür; `excerpt` `strip_tags + whitespace normalize + 500 karakter` yapılır.
- **İdempotent yazma:** `yemek_menuleri` `tarih` üzerine `ON DUPLICATE KEY UPDATE`; `dis_haberler` `url` üzerine aynı işlem. `uq_yemek_tarih` ve `uq_haber_url(191)` kısıtları aynı kaydı ikinci kez eklemeyi engeller.
- **`kaynak_senkron_durumu`** tablosu `bekliyor | calisiyor | basarili | hata` durum makinesiyle güncellenir (`son_deneme`, `son_basari`, `hata_mesaji`). Senkron her işlemde `calisiyor`'a çekilir, başarıda `son_basari = NOW()`, hatada `hata_mesaji` yazılır.
- **`admin/senkron.php`:** "Veri Senkronizasyonu" ekranı — her kaynağın durumu, son deneme/başarı zamanı, hata mesajı,
  "Hepsini Senkronize Et" ve kaynak bazında "Senkronize Et" düğmeleri (POST + CSRF), yaklaşan yemek menüsü ve son haberler listesi.
- **Ana sayfa:** `dis_haberler`'den 4 haber kartı (`Son Kampüs Haberleri`) eklendi; "Kampüs Haberleri" özelliği `Yakında` rozetini kaybedip `#haberler` bağlantısı oldu.
- **Test yardımcıları:** `oturum_test.php` ve CLI senkron deneme betiği Faz 8 doğrulamasında kullanıldı, sonunda silindi.

### Doğrulama

| Test | Sonuç |
|---|---|
| 55 PHP dosyası `php -l` | Sözdizimi temiz |
| CLI: `tumunuGuncelle()` | `yemek: 21 menü günü eklendi (2026-10-30..2026-11-27)`, `haber: 10 haber eklendi` |
| Yemek tablosu | 22 kayıt, hepsinde çorba/Kcal dolu |
| `kaynak_senkron_durumu` | `yemek` ve `haber` satırları `basarili`, `son_basari` dolu |
| Admin → Veri Senkronu | Her iki kaynak "Başarılı" görünüyor; yaklaşan menü ve haberler listeleniyor |
| Ana sayfa | 09.11.2026, 1516 kcal, "Son Kampüs Haberleri" 4 kart görünüyor |
| XLSX parse (`xlsx_test.php`) | 21 tarihli satır doğru sırayla okundu |
| UTF-8 Türkçe karakterler (SPAGETTİ, ÇÖKERTME KEBAP) | Doğru |

### Bilinen notlar

- Kaynaklar internetten gelir; sınav/hafta sonu günlerinde SKS menü dosyası değişmezse `ON DUPLICATE KEY UPDATE` aynı kaydı günceller.
- `php.ini` içinde `allow_url_fopen` açık değilse `file_get_contents` boş döner; senkron bu durumu "hata" olarak raporlar.
- Aylık menü Excel'inde hafta sonları boş bırakılabilir; `tarih` yoksayılır, `Continue` eder.
- Haber içeriği HTML'i olduğu için yalnızca başlık/özet alanları kullanılır; `content.rendered` çekilmedi (bu PHP'nin aynı SQL sorgusunda doğrudan kullanımı için).

### Sonraki faz

Faz 7 - (planı kullanıcıyla onaylanacak; güvenlik sertleştirme, performans, yedekleme ve teslim dokümanı öneriliyor).

## Faz 6 - Raporlama ve İstatistik Paneli ✅

**Tarih:** 8 Kasım 2026

### Yapılanlar

- **`database/07_rapor.sql`:** 5 yeni görünüm + 2 prosedür.
  - `v_ders_doluluk` — ders açılışı bazında kontenjan, kayıtlı öğrenci, doluluk yüzdesi.
  - `v_ogretim_elemani_yuku` — hoca bazında dönem yükü (açılan ders, kayıtlı öğrenci, not girilen, ortalama).
  - `v_donem_ozet` — dönem toplu göstergeleri (ders açılışı, kayıtlı öğrenci, not girme, geçen/kalan, başarı yüzdesi).
  - `v_harf_notu_dagilimi` — dönem bazında harf notu dağılımı.
  - `v_bolum_ozet` — bölüm bazında öğrenci, ders ve başarı özeti.
  - `sp_donem_raporu(p_donem_id)` — ders bazlı rapor; `v_ders_doluluk` + `v_ders_basari_istatistik` birleşimi.
  - `sp_riskli_ogrenciler(p_donem_id, p_esik)` — ortalaması eşiğin altında **veya** en az bir başarısız dersi olan öğrenciler (LIMIT 20).
- **`src/Rapor.php`:** rapor veri katmanı — `donemSec`, `ozet` (not girme oranı türetimi), `dersRaporu` (CALL + `closeCursor`), `hocaYuku` (ders programından haftalık saat), `harfDagilimi`, `bolumOzeti`, `riskliOgrenciler`, `csvDersRaporu`, `ondalik`, `yuzdeSinifi`. Risk eşiği `RISK_ESIGI = 70`.
- **`admin/raporlar.php`:** dönem seçici, 4 özet kartı (kayıtlı öğrenci, ders açılışı, not girme oranı, genel başarı), ders bazlı tablo (doluluk çubuğu + başarı rozeti), harf notu dağılım çubukları, bölüm özeti, öğretim elemanı yükü, akademik risk listesi. Yarım kartlarda tablolar sığmadığı için Bölüm/Hoca/Risk bölümleri `.satir-liste` + `.satir` düzenine çevrildi.
- **`admin/rapor_csv.php`:** dönem raporunu CSV indirir — UTF-8 BOM, `;` ayırıcı, Türkçe ondalık virgül (Excel uyumlu), indirme `Auth::log` ile kaydedilir.
- **Menü ve panel:** admin menüsüne "Raporlar", yönetici panelindeki hızlı işlemlere "Raporlar" kutusu eklendi.
- **CSS (bölüm 26):** `.yazdir-baslik`, `.sayfa-eylem`, `.izgara > * { min-width: 0 }`, `.rapor-doluluk`, `.rapor-bar*`, `@media print` kuralları. Mobilde `.filtre-cubuk .filtre-alan { flex: 0 0 auto }` ile select kutusunun 180px yüksekliğe uzaması engellendi.

### Doğrulama

| Test | Sonuç |
|---|---|
| 58 PHP dosyası `php -l` | Sözdizimi temiz |
| Veritabanı nesneleri | 8 view, 4 prosedür |
| `sp_donem_raporu(3)` | 11 ders açılışı; NBP101 %25 doluluk, ort. 68,60, başarı %66,7 |
| `sp_riskli_ogrenciler(3, 70)` | 9 öğrenci (ortalama < 70 veya başarısız ders) |
| Yönetici → Raporlar (1600 px) | Tüm kartlar ve listeler düzgün, taşma yok |
| Mobil ölçüm (390 px iframe) | `scrollWidth = clientWidth = 390`, yatay taşma yok |
| CSV indirme | HTTP 200, `ders-raporu-2026-2027-guz.csv`, BOM `EF BB BF`, Türkçe karakterler doğru |
| CSV log kaydı | `islem_loglari` tablosuna "Dönem raporu CSV indirildi" düştü |
| Boş dönem (donem=1) | "Ders açılışı bulunmuyor" / "Not yok" boş durumları görünüyor |

### Bilinen notlar

- Raporlar anlık veriden üretilir; ayrı bir özet tablo tutulmaz. Veri büyüdükçe view'ler yavaşlayabilir (Faz 7'de indeks/performans bakılabilir).
- Risk listesi `LIMIT 20`; daha uzun liste gerekirse prosedür parametresi eklenebilir.
- CSV yalnızca ders bazlı raporu indirir; harf/bölüm/hoca raporları ekranda kalır.
- `donem=1` gibi veri olmayan dönemlerde yüzde alanları `—` gösterilir.

## Faz 7 - Güvenlik, Performans, Yedekleme ve Teslim ✅

**Tarih:** 8 Kasım 2026

### Yapılanlar

- **`src/Guvenlik.php`:** istek başına CSP nonce üretir ve güvenlik başlıklarını gönderir.
  - `Content-Security-Policy`: `default-src 'self'`, `script-src 'self' 'nonce-...'`
    (satır içi `onclick/onsubmit` yasak), `frame-ancestors 'none'`, `object-src 'none'`.
  - `Cross-Origin-Opener-Policy`, `Cross-Origin-Resource-Policy`, HTTPS'te HSTS.
  - `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`
    kök `.htaccess` içinde tanımlı (statik dosyalar için de geçerli).
- **Satır içi olay işleyicileri kaldırıldı:** 11 `onsubmit="return confirm(...)"` →
  `data-onay`, 2 `onclick="window.print()"` → `data-yazdir`. Davranış `assets/js/app.js`
  içinde merkezîleştirildi.
- **CSP nonce:** `templates/head.php` ve `admin/kullanici.php` içindeki satır içi
  betikler nonce ile işaretlendi.
- **Oturum sertleştirme:** girişli oturum kimliği 30 dakikada bir yenilenir
  (`Auth::kimlikYenile`); girişte `son_yenileme` yazılır.
- **CSV yükleme sınırı:** en fazla 2 MB (uzantı kontrolüne ek).
- **`database/00_kurulum.sql`:** veritabanı + `sanalokul_app` kullanıcısı + izinler.
  Yedek için `TRIGGER, EVENT, SHOW VIEW` ve `mysql.proc` SELECT izni eklendi.
- **`database/08_performans.sql`:** 6 bileşik indeks + `ANALYZE TABLE`.
- **`scripts/yedekle.php`:** `mysqldump` ile `.sql.gz` yedeği; şifre geçici
  `--defaults-extra-file` üzerinden verilir, son 10 yedek tutulur. `yedekler/`
  web erişimine kapalı ve `.gitignore`'da.
- **Doküman:** `docs/TESLIM.md` (kurulum, özellikler, ER özeti, güvenlik, yedekleme,
  test listesi, ekran görüntüleri) ve kök `README.md`.
- **Ekran görüntüleri:** `docs/ekranlar/` altında 5 görsel (ana sayfa, öğrenci paneli,
  öğretmen not girişi, yönetici paneli, yönetici raporları).

### Doğrulama

| Test | Sonuç |
|---|---|
| PHP `php -l` | 60 dosya temiz |
| Güvenlik başlıkları | CSP + `X-Frame-Options: DENY` + `nosniff` + COOP/CORP tekilleştirildi |
| CSP nonce | Ana sayfa, giriş, 3 panel ve rapor ekranı hatasız render edildi |
| Oturum yenileme | Girişte `son_yenileme` yazılıyor; 30 dk sonrası kimlik yenileniyor |
| CSV boyut sınırı | 2 MB üstü dosya reddediliyor |
| `08_performans.sql` | 6 indeks oluştu; `ANALYZE` OK |
| Yedek betiği | `sanalokul-20261108-041746.sql.gz` (20,6 KB); açıldığında 20 tablo, 4 trigger, 4 prosedür, 8 view |
| Mobil 390 px | Yatay taşma yok (Faz 6 doğrulaması korunuyor) |
| Ekran görüntüleri | 5 görsel `docs/ekranlar/` altında |

### Bilinen notlar

- Yedek betiği yerel makine içindir; uzak sunucuya kopyalama yapmaz.
- HSTS yalnızca HTTPS'te gönderilir; XAMPP geliştirme ortamı HTTP olduğu için görünmez.
- CSP'de `style-src 'unsafe-inline'` korunuyor (tablo genişlikleri ve tema için satır içi stil var).
- `mysql.proc` SELECT izni yalnızca prosedür tanımlarını okumak içindir; uygulama bu izni kullanmaz.

### Sonraki faz

Proje fazları tamamlandı. Teslim öncesi isteğe bağlı işler: gerçek sunucuda HTTPS +
şifre değişimi, demo hesapların kaldırılması, uzak yedek kopyası.
