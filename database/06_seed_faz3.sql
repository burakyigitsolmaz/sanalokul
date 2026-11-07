-- =====================================================================
-- Nallıhan MYO Sanal Okul - Faz 3 Ek Seed
-- Dosya : 06_seed_faz3.sql
--
-- Amaç: Faz 3 sayfalarının (ders detay, not geçmişi, transkript, ödevler)
--       boş görünmemesi için içerik derinliği eklemek.
--
-- Not: Tekrar çalıştırılabilir. Var olan kayıtlar güncellenir, yenisi eklenmez.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Yemek menüsü: 9 - 30 Kasım 2026 hafta içi günler
--    (7 Kasım Cumartesi olduğu için bugün menü yok; ana sayfa en yakın
--     menüyü "sonraki menü" olarak gösteriyor.)
-- ---------------------------------------------------------------------
INSERT INTO yemek_menuleri
    (tarih, corba, ana_yemek, yardimci_yemek, tatli_salata,
     corba_kcal, ana_kcal, yardimci_kcal, ek_kcal, toplam_kcal, kaynak_url) VALUES
    ('2026-11-09', 'Mercimek Çorba',        'Fırın Tavuk Baget',      'Bulgur Pilavı',        'Sütlaç',        190, 480, 210, 220, 1100, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-10', 'Ezogelin Çorba',        'Karnıyarık',             'Pirinç Pilavı',        'Cacık',         210, 430, 200, 70,  910,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-11', 'Domates Çorba',         'Etli Nohut',             'Bulgur Pilavı',        'Ayran',         150, 460, 210, 60,  880,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-12', 'Tarhana Çorba',         'İzmir Köfte',            'Pirinç Pilavı',        'Mevsim Meyvesi', 180, 500, 200, 90, 970,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-13', 'Yayla Çorba',           'Sebzeli Tavuk Sote',     'Bulgur Pilavı',        'Kemalpaşa',     168, 400, 210, 260, 1038, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-16', 'Mercimek Çorba',        'Etli Türlü',             'Pirinç Pilavı',        'Yoğurt',        190, 470, 200, 55,  915,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-17', 'Düğürcük Çorba',        'Fırında Somon',          'Bulgur Pilavı',        'Mevsim Salata', 196, 520, 210, 80,  1006, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-18', 'Ezogelin Çorba',        'Kuru Fasulye',           'Pirinç Pilavı',        'Turşu',         210, 440, 200, 40,  890,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-19', 'Brokoli Çorba',         'Tavuk Şiş',              'Bulgur Pilavı',        'Sütlaç',        160, 450, 210, 220, 1040, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-20', 'Tarhana Çorba',         'Etli Bezelye',           'Pirinç Pilavı',        'Ayran',         180, 430, 200, 60,  870,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-23', 'Mercimek Çorba',        'İskender Kebap',         'Pirinç Pilavı',        'Red Velvet',    190, 567, 180, 250, 1187, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-24', 'Yayla Çorba',           'Fırın Tavuk But',        'Bulgur Pilavı',        'Mevsim Meyvesi', 168, 470, 210, 90, 938,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-25', 'Domates Çorba',         'Kıymalı Makarna',        'Mevsim Salata',        'Cacık',         150, 520, 80,  70,  820,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-26', 'Ezogelin Çorba',        'Etli Bamya',             'Pirinç Pilavı',        'Ayran',         210, 420, 200, 60,  890,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-27', 'Tarhana Çorba',         'Çupra Izgara',           'Bulgur Pilavı',        'Helva',         180, 500, 210, 240, 1130, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),
    ('2026-11-30', 'Mercimek Çorba',        'Etli Kuru Fasulye',      'Pirinç Pilavı',        'Turşu',         190, 460, 200, 40,  890,  'https://sks.ankara.edu.tr/yemek-hizmetleri-2/')
ON DUPLICATE KEY UPDATE
    corba = VALUES(corba),
    ana_yemek = VALUES(ana_yemek),
    yardimci_yemek = VALUES(yardimci_yemek),
    tatli_salata = VALUES(tatli_salata),
    corba_kcal = VALUES(corba_kcal),
    ana_kcal = VALUES(ana_kcal),
    yardimci_kcal = VALUES(yardimci_kcal),
    ek_kcal = VALUES(ek_kcal),
    toplam_kcal = VALUES(toplam_kcal),
    kaynak_url = VALUES(kaynak_url);

-- ---------------------------------------------------------------------
-- 2. Duyurular (6 - 13)
-- ---------------------------------------------------------------------
INSERT INTO duyurular (id, ders_acilis_id, yazar_id, baslik, icerik, onemli, yayin_tarihi) VALUES
    (6, NULL, 1, 'Vize Sınav Takvimi Yayınlandı',
     '2026-2027 Güz dönemi vize sınav takvimi yayınlanmıştır. Sınavlar 10-20 Kasım 2026 tarihleri arasında yapılacaktır. Sınav salonu bilgileri danışmanınız tarafından duyurulacaktır.',
     1, '2026-11-05 09:15:00'),

    (7, NULL, 1, 'Kariyer Günü Konukları Belli Oldu',
     '20 Kasım 2026 Cuma günü düzenlenecek Kariyer Günü''ne yazılım, siber güvenlik ve veri analizi alanlarından konuklar katılacaktır. Katılım sertifikası verilecektir.',
     0, '2026-11-04 13:40:00'),

    (8, 6, 2, 'NBP101 Ödev Teslim Tarihi Güncellendi',
     'Değişkenler ve karar yapıları ödevinin teslim tarihi 15 Kasım 2026 olarak güncellenmiştir. Teslimler sanal okul panelindeki Ödevler sayfasından yapılacaktır.',
     1, '2026-11-03 10:20:00'),

    (9, 7, 3, 'NBP103 Laboratuvar Saati Değişikliği',
     '2 Kasım haftası itibarıyla Web Tasarım Temelleri dersi Çarşamba 08:30 oturumu Bilgisayar Laboratuvarı 1''de yapılacaktır.',
     0, '2026-11-02 08:05:00'),

    (10, 9, 4, 'NBP107 Donanım Dersi Kısa Sınavı',
     'Bilgisayar Donanımı ve Ağ dersinde 13 Kasım 2026 Cuma günü ilk 30 dakikada kısa sınav yapılacaktır. Konular: anakart, işlemci ve bellek türleri.',
     1, '2026-11-06 15:00:00'),

    (11, 10, 4, 'NBP201 Proje Konuları Askıya Çıktı',
     'Nesne Tabanlı Programlama dersi dönem projesi konuları panoya ve ders sayfasına eklenmiştir. Konu seçimini 14 Kasım''a kadar yapmanız gerekmektedir.',
     0, '2026-11-05 11:30:00'),

    (12, NULL, 1, 'Yemekhane Menüsü Güncellendi',
     'Kasım ayı yemekhane menüsü güncellenmiştir. Günlük menü ana sayfada ve Yemekhane sayfasında görüntülenebilir.',
     0, '2026-11-06 08:45:00'),

    (13, 11, 3, 'NBP203 Telafi Dersi Yapılacaktır',
     '12 Kasım Perşembe günü yapılacak vize sınavı nedeniyle iptal edilen dersin telafisi 21 Kasım 2026 Cumartesi 10:00''da Bilgisayar Laboratuvarı 2''de yapılacaktır.',
     0, '2026-11-06 16:10:00')
ON DUPLICATE KEY UPDATE
    ders_acilis_id = VALUES(ders_acilis_id),
    yazar_id = VALUES(yazar_id),
    baslik = VALUES(baslik),
    icerik = VALUES(icerik),
    onemli = VALUES(onemli),
    yayin_tarihi = VALUES(yayin_tarihi);

-- ---------------------------------------------------------------------
-- 3. Ödevler (5 - 12)
-- ---------------------------------------------------------------------
INSERT INTO odevler (id, ders_acilis_id, baslik, aciklama, son_teslim, maks_puan) VALUES
    (5, 6, 'Döngülerle Çarpım Tablosu',
     '1''den 10''a kadar çarpım tablosunu iç içe döngü ile ekrana yazdıran programı hazırlayın. Çıktının hizalanmasına dikkat edin.',
     '2026-11-25 23:59:00', 100),

    (6, 7, 'Responsive Blog Sayfası',
     'Mobil öncelikli yaklaşımla en az 3 karttan oluşan bir blog ana sayfası tasarlayın. Flexbox veya Grid kullanın, 390 px genişlikte taşma olmamalı.',
     '2026-11-28 23:59:00', 100),

    (7, 9, 'Ağ Topolojisi Çizimi',
     '10 bilgisayarlı bir laboratuvar için yıldız topolojisi çizin; IP adres planını ve kullanılan cihazları tablo halinde verin.',
     '2026-11-22 23:59:00', 100),

    (8, 10, 'OOP ile Kütüphane Sistemi',
     'Kitap, Uye ve Odunc sınıflarını içeren konsol uygulaması yazın. Kalıtım ve kapsülleme kullanılmalıdır.',
     '2026-12-05 23:59:00', 100),

    (9, 11, 'JOIN Sorguları Uygulaması',
     'Verilen şema üzerinde 5 farklı JOIN sorgusu yazın; her sorgunun amacını birer cümleyle açıklayın.',
     '2026-11-19 23:59:00', 100),

    (10, 12, 'Linux Kullanıcı Yönetimi',
     'Sanal makinede 3 kullanıcı ve 2 grup oluşturun; dizin izinlerini ayarlayıp ekran görüntüleriyle raporlayın.',
     '2026-11-26 23:59:00', 100),

    (11, 13, 'Assembler Döngü Örneği',
     '1''den 100''e kadar sayıları toplayan assembler programını yazın ve yazmaç değerlerini adım adım açıklayın.',
     '2026-12-02 23:59:00', 100),

    (12, 16, 'Dönem Projesi Ara Raporu',
     'Proje konusu, gereksinim listesi, veritabanı taslağı ve iş planını içeren ara raporu teslim edin (en az 5 sayfa).',
     '2026-12-10 23:59:00', 100)
ON DUPLICATE KEY UPDATE
    ders_acilis_id = VALUES(ders_acilis_id),
    baslik = VALUES(baslik),
    aciklama = VALUES(aciklama),
    son_teslim = VALUES(son_teslim),
    maks_puan = VALUES(maks_puan);

-- ---------------------------------------------------------------------
-- 4. Ders içerikleri (hafta 1 - 8)
-- ---------------------------------------------------------------------
INSERT INTO ders_icerikleri (ders_acilis_id, hafta, baslik, aciklama) VALUES
    -- NBP101 Programlama Temelleri
    (6, 5,  'Diziler',                     'Tek ve çok boyutlu diziler, eleman erişimi, arama ve sıralama.'),
    (6, 6,  'Fonksiyonlar',                'Parametre, dönüş değeri, kapsam ve özyineleme.'),
    (6, 7,  'Dosya işlemleri',             'Metin dosyasına yazma, okuma ve hata kontrolü.'),
    (6, 8,  'Ara sınav değerlendirmesi',   'Vize sorularının çözümü ve eksik konuların tekrarı.'),

    -- NBP103 Web Tasarım Temelleri
    (7, 4,  'CSS Grid',                    'Grid şablonları, alan adlandırma, hizalama.'),
    (7, 5,  'Responsive tasarım',          'Media query, akışkan birimler, mobil öncelikli yaklaşım.'),
    (7, 6,  'Formlar ve doğrulama',        'Input türleri, etiketler, HTML5 doğrulama.'),
    (7, 7,  'Erişilebilirlik',             'Anlamsal etiketler, kontrast, klavye ile gezinme.'),
    (7, 8,  'Mini proje',                  'Tek sayfalık tanıtım sitesinin birlikte kodlanması.'),

    -- NBP105 Grafik ve Animasyon
    (8, 1,  'Grafik tasarım temelleri',    'Tasarım ilkeleri, denge, hiyerarşi.'),
    (8, 2,  'Renk teorisi',                'Renk çemberi, sıcak-soğuk renkler, palet oluşturma.'),
    (8, 3,  'Tipografi',                   'Yazı karakteri seçimi, satır aralığı, okunabilirlik.'),
    (8, 4,  'Vektör araçları',             'Kalem aracı, düğüm düzenleme, şekil birleştirme.'),
    (8, 5,  'Katman ve maskeleme',         'Katman mantığı, maske, kırpma.'),
    (8, 6,  'Animasyon ilkeleri',          'Hareket eğrileri, zamanlama, ara kareler.'),
    (8, 7,  'Zaman çizelgesi',             'Anahtar kare, geçiş, dışa aktarma.'),
    (8, 8,  'Ara proje',                   'Kurumsal logo çalışması ve sunumu.'),

    -- NBP107 Bilgisayar Donanımı ve Ağ
    (9, 1,  'Donanıma giriş',              'Bilgisayar bileşenleri ve görevleri.'),
    (9, 2,  'Anakart ve işlemci',          'Yonga seti, soket tipleri, çekirdek sayısı.'),
    (9, 3,  'Bellek türleri',              'RAM, önbellek, ECC, hız ve gecikme.'),
    (9, 4,  'Depolama birimleri',          'HDD, SSD, NVMe, RAID seviyeleri.'),
    (9, 5,  'Ağ temelleri',                'LAN, WAN, topolojiler, OSI modeli.'),
    (9, 6,  'IP adresleme',                'IPv4 sınıfları, alt ağ maskesi, özel adresler.'),
    (9, 7,  'Yönlendirme',                 'Router, switch, VLAN kavramları.'),
    (9, 8,  'Laboratuvar uygulaması',      'Kablo çakma, switch yapılandırma, test.'),
    -- NBP201 Nesne Tabanlı Programlama
    (10, 1, 'OOP''ye giriş',               'Nesne, sınıf, mesajlaşma kavramları.'),
    (10, 2, 'Sınıf ve nesne',              'Yapıcı metot, alanlar, özellikler.'),
    (10, 3, 'Kapsülleme',                  'Erişim belirleyiciler, get/set mantığı.'),
    (10, 4, 'Kalıtım',                     'Temel sınıf, türetilmiş sınıf, override.'),
    (10, 5, 'Çok biçimlilik',              'Sanal metotlar, arayüz kullanımı.'),
    (10, 6, 'Arayüz ve soyut sınıf',       'Soyutlama, sözleşme tasarımı.'),
    (10, 7, 'Koleksiyonlar',               'Liste, sözlük, küme ve yineleyiciler.'),
    (10, 8, 'Proje planı',                 'Dönem projesi konularının belirlenmesi.'),

    -- NBP203 Veri Tabanı Uygulamaları
    (11, 5, 'İndeksler',                   'İndeks türleri, performansa etkisi, EXPLAIN.'),
    (11, 6, 'Görünümler ve prosedürler',   'VIEW, STORED PROCEDURE, parametreler.'),
    (11, 7, 'Tetikleyiciler ve işlemler',  'TRIGGER, TRANSACTION, COMMIT / ROLLBACK.'),
    (11, 8, 'Performans ve yedekleme',     'Sorgu iyileştirme, mysqldump, geri yükleme.'),

    -- NBP205 Sunucu İşletim Sistemleri
    (12, 1, 'Sunucu işletim sistemleri',   'Windows Server ve Linux dağıtımları.'),
    (12, 2, 'Linux kurulumu',              'Disk bölümleme, kullanıcı oluşturma, paket yönetimi.'),
    (12, 3, 'Dosya sistemi',               'Kök dizin yapısı, bağlama noktaları.'),
    (12, 4, 'Kullanıcı ve grup yönetimi',  'useradd, usermod, groupadd komutları.'),
    (12, 5, 'Dosya izinleri',              'chmod, chown, umask, özel izinler.'),
    (12, 6, 'Servis yönetimi',             'systemctl, log takibi, zamanlanmış görevler.'),
    (12, 7, 'Ağ yapılandırması',           'Statik IP, DNS, hostname ayarları.'),
    (12, 8, 'Güvenlik duvarı',             'ufw / firewalld kuralları ve testleri.'),

    -- NBP207 Mikrobilgisayar ve Assembler
    (13, 1, 'Mikroişlemci mimarisi',       'ALU, kontrol birimi, veri yolu.'),
    (13, 2, 'Sayı sistemleri',             'İkilik, onaltılık, dönüşümler.'),
    (13, 3, 'Yazmaçlar',                   'Genel amaçlı ve özel yazmaçlar.'),
    (13, 4, 'Komut seti',                  'Veri taşıma ve aritmetik komutlar.'),
    (13, 5, 'Adresleme modları',           'Doğrudan, dolaylı, indeksli adresleme.'),
    (13, 6, 'Döngü ve dallanma',           'Karşılaştırma, koşullu atlama.'),
    (13, 7, 'Kesmeler',                    'Donanım ve yazılım kesmeleri.'),
    (13, 8, 'Laboratuvar',                 'Simülatör üzerinde program çalıştırma.'),

    -- NBP213 Dönem Projesi
    (16, 1, 'Proje konuları',              'Konu havuzu ve seçim kriterleri.'),
    (16, 2, 'Gereksinim analizi',          'Kullanıcı hikâyeleri, kapsam belirleme.'),
    (16, 3, 'Tasarım',                     'Arayüz taslağı, akış diyagramı.'),
    (16, 4, 'Veritabanı tasarımı',         'ER modeli, normalizasyon, tablo yapısı.'),
    (16, 5, 'Geliştirme I',                'Temel modüllerin kodlanması.'),
    (16, 6, 'Geliştirme II',               'İkincil modüller ve entegrasyon.'),
    (16, 7, 'Test',                        'Birim testi, kullanıcı kabul testi.'),
    (16, 8, 'Ara rapor',                   'Rapor yazımı ve teslim.')
ON DUPLICATE KEY UPDATE
    baslik = VALUES(baslik),
    aciklama = VALUES(aciklama);

-- ---------------------------------------------------------------------
-- 5. Teslimler (demo)
-- ---------------------------------------------------------------------
INSERT INTO teslimler (odev_id, ogrenci_id, dosya_yolu, aciklama, teslim_zamani, puan, geri_bildirim, degerlendirme_zamani) VALUES
    (4, 16, 'uploads/demo/deniz_normalizasyon.pdf', '3NF adımları rapor içinde.', '2026-11-05 20:10:00', 90, 'Adımlar doğru, gerekçeler biraz kısa.', '2026-11-06 09:00:00'),
    (4, 17, 'uploads/demo/fatma_normalizasyon.pdf', 'Örnek tablolar eklenmiştir.', '2026-11-06 22:30:00', NULL, NULL, NULL),
    (5, 6, 'uploads/demo/burak_carpim.pdf', 'İç içe döngü kullandım.', '2026-11-07 10:15:00', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    dosya_yolu = VALUES(dosya_yolu),
    aciklama = VALUES(aciklama),
    teslim_zamani = VALUES(teslim_zamani),
    puan = VALUES(puan),
    geri_bildirim = VALUES(geri_bildirim),
    degerlendirme_zamani = VALUES(degerlendirme_zamani);
