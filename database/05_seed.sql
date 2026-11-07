-- =====================================================================
-- Nallıhan MYO Sanal Okul - Örnek (Seed) Veriler
-- Dosya : 05_seed.sql
--
-- Kaynaklar:
--   * Öğretim elemanları : nallihanmyo.ankara.edu.tr akademik kadro sayfası
--   * Dersler / program  : Nallıhan MYO 2025-2026 Güz ve Bahar ders programları
--
-- Not: E-posta, telefon ve şifreler DEMO amaçlıdır. Gerçek kişisel veri değildir.
--      Tüm demo hesapların şifresi: Sanal1234!
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Roller
-- ---------------------------------------------------------------------
INSERT INTO roller (id, ad) VALUES
    (1, 'ogrenci'),
    (2, 'ogretim_elemani'),
    (3, 'yonetici');

-- ---------------------------------------------------------------------
-- Bölümler (programlar)
-- ---------------------------------------------------------------------
INSERT INTO bolumler (id, kod, ad) VALUES
    (1, 'BP',     'Bilgisayar Programcılığı'),
    (2, 'ET',     'Elektronik Teknolojisi'),
    (3, 'YET',    'Yenilenebilir Enerji Teknikerliği'),
    (4, 'EEUID',  'Elektrik Enerjisi Üretim, İletim ve Dağıtımı');

-- ---------------------------------------------------------------------
-- Dönemler
-- ---------------------------------------------------------------------
INSERT INTO donemler (id, ad, baslangic, bitis, aktif) VALUES
    (1, '2025-2026 Güz',   '2025-09-15', '2026-01-16', 0),
    (2, '2025-2026 Bahar', '2026-02-09', '2026-06-12', 0),
    (3, '2026-2027 Güz',   '2026-09-21', '2027-01-15', 1);

-- ---------------------------------------------------------------------
-- Kullanıcılar
-- Şifre: Sanal1234!  (password_hash ile üretilmiş tek hash, demo amaçlı)
-- ---------------------------------------------------------------------
SET @hash = '$2y$10$eM1E9aOWelBGGU/IOy.zEuTCynvZ5FnNofFIWbnBRT.fkOhaMT5ai';

INSERT INTO kullanicilar (id, rol_id, numara, ad, soyad, eposta, sifre_hash, telefon) VALUES
    -- Yönetici
    (1, 3, 'Y001', 'Sistem', 'Yönetici', 'yonetici@ornek.local', @hash, '05000000001'),

    -- Öğretim elemanları (gerçek isim ve unvanlar)
    (2, 2, 'H001', 'Ayhan', 'Aydın',     'ayhan.aydin@ornek.local',     @hash, '05000000002'),
    (3, 2, 'H002', 'Ufuk',  'Tanyeri',   'ufuk.tanyeri@ornek.local',    @hash, '05000000003'),
    (4, 2, 'H003', 'Salih', 'Erdurucan', 'salih.erdurucan@ornek.local', @hash, '05000000004'),
    (5, 2, 'H004', 'Güney', 'Uğurlu',    'guney.ugurlu@ornek.local',    @hash, '05000000005'),

    -- 1. sınıf öğrencileri
    (6,  1, '2026101001', 'Burak', 'Yiğit Solmaz', 'burak.solmaz@ornek.local',  @hash, '05001110001'),
    (7,  1, '2026101002', 'Kaan',  'Tokatlı',      'kaan.tokatli@ornek.local',  @hash, '05001110002'),
    (8,  1, '2026101003', 'Elif',  'Yılmaz',       'elif.yilmaz@ornek.local',   @hash, '05001110003'),
    (9,  1, '2026101004', 'Ahmet', 'Kaya',         'ahmet.kaya@ornek.local',    @hash, '05001110004'),
    (10, 1, '2026101005', 'Zeynep','Demir',        'zeynep.demir@ornek.local',  @hash, '05001110005'),
    (11, 1, '2026101006', 'Mert',  'Çelik',        'mert.celik@ornek.local',    @hash, '05001110006'),
    (12, 1, '2026101007', 'Ayşe',  'Şahin',        'ayse.sahin@ornek.local',    @hash, '05001110007'),
    (13, 1, '2026101008', 'Emir',  'Aydın',        'emir.aydin@ornek.local',    @hash, '05001110008'),
    (14, 1, '2026101009', 'Selin', 'Arslan',       'selin.arslan@ornek.local',  @hash, '05001110009'),
    (15, 1, '2026101010', 'Hakan', 'Doğan',        'hakan.dogan@ornek.local',   @hash, '05001110010'),

    -- 2. sınıf öğrencileri
    (16, 1, '2025101001', 'Deniz', 'Yıldız',  'deniz.yildiz@ornek.local',  @hash, '05002220001'),
    (17, 1, '2025101002', 'Fatma', 'Öztürk',  'fatma.ozturk@ornek.local',  @hash, '05002220002'),
    (18, 1, '2025101003', 'Yusuf', 'Arslan',  'yusuf.arslan@ornek.local',  @hash, '05002220003'),
    (19, 1, '2025101004', 'İrem',  'Kılıç',   'irem.kilic@ornek.local',    @hash, '05002220004'),
    (20, 1, '2025101005', 'Onur',  'Bektaş',  'onur.bektas@ornek.local',   @hash, '05002220005'),
    (21, 1, '2025101006', 'Melisa','Koç',     'melisa.koc@ornek.local',    @hash, '05002220006'),
    (22, 1, '2025101007', 'Serkan','Yalçın',  'serkan.yalcin@ornek.local', @hash, '05002220007'),
    (23, 1, '2025101008', 'Büşra', 'Tunç',    'busra.tunc@ornek.local',    @hash, '05002220008'),
    (24, 1, '2025101009', 'Cem',   'Polat',   'cem.polat@ornek.local',     @hash, '05002220009'),
    (25, 1, '2025101010', 'Nazlı', 'Erdem',   'nazli.erdem@ornek.local',   @hash, '05002220010');

-- ---------------------------------------------------------------------
-- Profiller
-- ---------------------------------------------------------------------
INSERT INTO ogretim_elemani_profilleri (kullanici_id, bolum_id, unvan) VALUES
    (2, 1, 'Dr. Öğr. Üyesi'),
    (3, 1, 'Öğr. Gör. Dr.'),
    (4, 1, 'Öğr. Gör.'),
    (5, 4, 'Öğr. Gör.');   -- Elektrik ve Enerji Bölümü kadrosu, Bilgisayar Programcılığı'nda derse giriyor

INSERT INTO ogrenci_profilleri (kullanici_id, bolum_id, sinif, giris_yili) VALUES
    (6, 1, 1, 2026), (7, 1, 1, 2026), (8, 1, 1, 2026), (9, 1, 1, 2026), (10, 1, 1, 2026),
    (11, 1, 1, 2026), (12, 1, 1, 2026), (13, 1, 1, 2026), (14, 1, 1, 2026), (15, 1, 1, 2026),
    (16, 1, 2, 2025), (17, 1, 2, 2025), (18, 1, 2, 2025), (19, 1, 2, 2025), (20, 1, 2, 2025),
    (21, 1, 2, 2025), (22, 1, 2, 2025), (23, 1, 2, 2025), (24, 1, 2, 2025), (25, 1, 2, 2025);

-- ---------------------------------------------------------------------
-- Dersler (Bilgisayar Programcılığı)
-- ---------------------------------------------------------------------
INSERT INTO dersler (id, bolum_id, kod, ad, kredi, akts, zorunlu_mu, sinif) VALUES
    -- 1. sınıf güz
    (1,  1, 'NBP101', 'Programlama Temelleri',              4, 5, 1, 1),
    (2,  1, 'NBP103', 'Web Tasarım Temelleri',              3, 4, 1, 1),
    (3,  1, 'NBP105', 'Grafik ve Animasyon',                3, 3, 1, 1),
    (4,  1, 'NBP107', 'Bilgisayar Donanımı ve Ağ',          3, 4, 1, 1),
    -- 1. sınıf bahar
    (5,  1, 'NBP102', 'Veri Tabanı Yönetim Sistemleri',     3, 5, 1, 1),
    (6,  1, 'NBP104', 'Web Arayüz Tasarımı ve Programlama', 4, 5, 1, 1),
    (7,  1, 'NBP106', 'Programlama',                        4, 5, 1, 1),
    (8,  1, 'NBP108', 'Siber Güvenliğe Giriş',              3, 4, 1, 1),
    (9,  1, 'NBP110', 'Açık Kaynak Yazılımlar',             2, 3, 0, 1),
    -- 2. sınıf güz
    (10, 1, 'NBP201', 'Nesne Tabanlı Programlama',          4, 5, 1, 2),
    (11, 1, 'NBP203', 'Veri Tabanı Uygulamaları',           4, 5, 1, 2),
    (12, 1, 'NBP205', 'Sunucu İşletim Sistemleri',          3, 4, 1, 2),
    (13, 1, 'NBP207', 'Mikrobilgisayar ve Assembler',       3, 4, 1, 2),
    (14, 1, 'NBP209', 'Görüntü İşlemenin Temelleri',        3, 4, 0, 2),
    (15, 1, 'NBP211', 'İşletme Yönetimi I',                 2, 3, 1, 2),
    (16, 1, 'NBP213', 'Dönem Projesi',                      2, 4, 1, 2);

-- ---------------------------------------------------------------------
-- Ders açılışları
--   1-5   : 2025-2026 Bahar (geçmiş dönem, notlar tamamlanmış)
--   6-16  : 2026-2027 Güz (aktif dönem)
-- ---------------------------------------------------------------------
INSERT INTO ders_acilislari (id, ders_id, donem_id, ogretim_elemani_id, sube, kontenjan) VALUES
    (1,  5,  2, 3, 'A', 45),   -- Veri Tabanı Yönetim Sistemleri - Ufuk Tanyeri
    (2,  6,  2, 3, 'A', 45),   -- Web Arayüz Tasarımı ve Programlama - Ufuk Tanyeri
    (3,  7,  2, 5, 'A', 45),   -- Programlama - Güney Uğurlu
    (4,  8,  2, 5, 'A', 45),   -- Siber Güvenliğe Giriş - Güney Uğurlu
    (5,  9,  2, 4, 'A', 45),   -- Açık Kaynak Yazılımlar - Salih Erdurucan

    (6,  1,  3, 2, 'A', 40),   -- Programlama Temelleri - Ayhan Aydın
    (7,  2,  3, 3, 'A', 40),   -- Web Tasarım Temelleri - Ufuk Tanyeri
    (8,  3,  3, 4, 'A', 40),   -- Grafik ve Animasyon - Salih Erdurucan
    (9,  4,  3, 4, 'A', 40),   -- Bilgisayar Donanımı ve Ağ - Salih Erdurucan
    (10, 10, 3, 4, 'A', 35),   -- Nesne Tabanlı Programlama - Salih Erdurucan
    (11, 11, 3, 3, 'A', 35),   -- Veri Tabanı Uygulamaları - Ufuk Tanyeri
    (12, 12, 3, 4, 'A', 35),   -- Sunucu İşletim Sistemleri - Salih Erdurucan
    (13, 13, 3, 3, 'A', 35),   -- Mikrobilgisayar ve Assembler - Ufuk Tanyeri
    (14, 14, 3, 3, 'A', 30),   -- Görüntü İşlemenin Temelleri - Ufuk Tanyeri
    (15, 15, 3, 4, 'A', 40),   -- İşletme Yönetimi I - Salih Erdurucan
    (16, 16, 3, 2, 'A', 30);   -- Dönem Projesi - Ayhan Aydın

-- ---------------------------------------------------------------------
-- Haftalık ders programı (aktif dönem) - gerçek programdan alınmıştır
-- gun: 1 = Pazartesi ... 5 = Cuma
-- ---------------------------------------------------------------------
INSERT INTO ders_programi (ders_acilis_id, gun, baslangic_saati, bitis_saati, derslik) VALUES
    -- 1. sınıf
    (6,  1, '15:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (6,  3, '15:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (7,  2, '15:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (7,  3, '08:30:00', '10:30:00', 'Bilgisayar Laboratuvarı 1'),
    (8,  3, '10:30:00', '12:30:00', 'Bilgisayar Laboratuvarı 2'),
    (9,  4, '08:30:00', '12:30:00', 'Bilgisayar Laboratuvarı 2'),
    -- 2. sınıf
    (10, 5, '08:30:00', '10:30:00', 'Bilgisayar Laboratuvarı 1'),
    (11, 3, '13:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 2'),
    (12, 4, '15:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (13, 3, '10:30:00', '12:30:00', 'Bilgisayar Laboratuvarı 1'),
    (13, 4, '13:30:00', '15:30:00', 'Bilgisayar Laboratuvarı 1'),
    (14, 4, '08:30:00', '12:30:00', 'Bilgisayar Laboratuvarı 1'),
    (15, 3, '08:30:00', '10:30:00', 'Konferans'),
    (16, 2, '08:30:00', '10:30:00', 'Danışman Odası');

-- ---------------------------------------------------------------------
-- Ders kayıtları
-- ---------------------------------------------------------------------

-- Geçmiş dönem (2025-2026 Bahar): 2. sınıfların 1. sınıf bahar dersleri
INSERT INTO ders_kayitlari (ogrenci_id, ders_acilis_id, durum, kayit_zamani)
SELECT op.kullanici_id, da.id, 'tamamlandi', '2026-02-10 10:00:00'
FROM ogrenci_profilleri op
    JOIN ders_acilislari da ON da.donem_id = 2
WHERE op.sinif = 2;

-- Aktif dönem: her öğrenci kendi sınıfının derslerine kayıtlı
INSERT INTO ders_kayitlari (ogrenci_id, ders_acilis_id, durum, kayit_zamani)
SELECT op.kullanici_id, da.id, 'aktif', '2026-09-22 09:00:00'
FROM ogrenci_profilleri op
    JOIN ders_acilislari da ON da.donem_id = 3
    JOIN dersler d          ON d.id = da.ders_id AND d.sinif = op.sinif;

-- ---------------------------------------------------------------------
-- Notlar (ortalama ve harf notu trigger tarafından hesaplanır)
-- ---------------------------------------------------------------------

-- Geçmiş dönem: tüm kayıtlara not girilmiş
INSERT INTO notlar (ders_kaydi_id, vize, `final`)
SELECT dk.id, 45 + (dk.id * 7) % 50, 50 + (dk.id * 11) % 45
FROM ders_kayitlari dk
    JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
WHERE da.donem_id = 2;

-- Aktif dönem: 1. sınıflarda ilk 6 öğrenciye not girildi (diğerleri boş kalsın)
INSERT INTO notlar (ders_kaydi_id, vize, `final`)
SELECT dk.id, 40 + (dk.id * 13) % 55, 45 + (dk.id * 17) % 50
FROM ders_kayitlari dk
    JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
    JOIN kullanicilar k     ON k.id = dk.ogrenci_id
WHERE da.donem_id = 3
  AND k.numara BETWEEN '2026101001' AND '2026101006';

-- Aktif dönem: 2. sınıflarda ilk 3 öğrenciye not girildi
INSERT INTO notlar (ders_kaydi_id, vize, `final`)
SELECT dk.id, 50 + (dk.id * 13) % 45, 55 + (dk.id * 17) % 40
FROM ders_kayitlari dk
    JOIN ders_acilislari da ON da.id = dk.ders_acilis_id
    JOIN kullanicilar k     ON k.id = dk.ogrenci_id
WHERE da.donem_id = 3
  AND k.numara BETWEEN '2025101001' AND '2025101003';

-- ---------------------------------------------------------------------
-- Duyurular
-- ---------------------------------------------------------------------
INSERT INTO duyurular (ders_acilis_id, yazar_id, baslik, icerik, onemli, yayin_tarihi) VALUES
    (NULL, 1, '2026-2027 Güz Dönemi Ders Kayıtları Başladı',
     'Ders kayıtları 22 Eylül 2026 tarihine kadar OBS üzerinden yapılacaktır. Kayıt sırasında danışman onayı gerekmektedir.',
     1, '2026-09-18 09:00:00'),

    (NULL, 1, 'Kütüphane Çalışma Saatleri Güncellendi',
     'Kütüphanemiz hafta içi 08:30-20:00, cumartesi 10:00-16:00 saatleri arasında hizmet verecektir.',
     0, '2026-10-02 11:30:00'),

    (NULL, 1, 'Kariyer Günü Etkinliği',
     'Bilişim sektöründen konukların katılacağı Kariyer Günü, 20 Kasım 2026 Cuma günü Konferans Salonu''nda yapılacaktır.',
     0, '2026-11-01 14:00:00'),

    (6, 2, 'Laboratuvar Kullanım Kuralları',
     'Bilgisayar Laboratuvarı 1''de ders süresince yiyecek-içecek bulundurulmaması, oturumların kendi kullanıcı hesabıyla açılması rica olunur.',
     0, '2026-09-28 10:00:00'),

    (11, 3, 'Vize Sınavı Hakkında',
     'NBP203 Veri Tabanı Uygulamaları vize sınavı 12 Kasım 2026 Perşembe günü 13:30''da Bilgisayar Laboratuvarı 2''de yapılacaktır. Sınav uygulamalıdır.',
     1, '2026-11-02 16:20:00');

-- ---------------------------------------------------------------------
-- Ödevler
-- ---------------------------------------------------------------------
INSERT INTO odevler (id, ders_acilis_id, baslik, aciklama, son_teslim, maks_puan) VALUES
    (1, 6,  'Değişkenler ve Karar Yapıları',
     'Kullanıcıdan alınan iki sayı ve bir işlem türüne göre sonucu hesaplayan konsol uygulamasını yazın. Akış diyagramını da ekleyin.',
     '2026-11-15 23:59:00', 100),

    (2, 7,  'Kişisel Tanıtım Sayfası',
     'HTML5 ve CSS3 kullanarak responsive bir kişisel tanıtım sayfası hazırlayın. En az 3 bölüm ve bir iletişim formu içermelidir.',
     '2026-11-20 23:59:00', 100),

    (3, 8,  'Logo Tasarımı',
     'Okulumuz için bir logo tasarlayıp katmanlı dosya (PNG + kaynak dosya) olarak teslim edin.',
     '2026-11-10 23:59:00', 100),

    (4, 11, 'Normalizasyon Uygulaması',
     'Verilen denormalize tabloyu 3. normal forma dönüştürün; adımları ve gerekçeleri raporlayın.',
     '2026-11-18 23:59:00', 100);

-- ---------------------------------------------------------------------
-- Teslimler (demo dosya yolları; gerçek dosya bulunmayabilir)
-- ---------------------------------------------------------------------
INSERT INTO teslimler (odev_id, ogrenci_id, dosya_yolu, aciklama, teslim_zamani, puan, geri_bildirim, degerlendirme_zamani) VALUES
    (1, 6, 'uploads/demo/burak_odev1.pdf', 'Akış diyagramını da ekledim.', '2026-11-12 21:40:00', 95, 'Temiz kod, değişken isimleri anlaşılır. Akış diyagramında bir karar kutusu eksik.', '2026-11-13 10:00:00'),
    (1, 7, 'uploads/demo/kaan_odev1.pdf', 'İşlem türleri menü ile seçiliyor.', '2026-11-14 23:10:00', NULL, NULL, NULL),
    (1, 8, 'uploads/demo/elif_odev1.pdf', 'Sıfıra bölme kontrolü ekledim.', '2026-11-15 22:05:00', 100, 'Eksiksiz teslim.', '2026-11-16 09:30:00'),
    (3, 6, 'uploads/demo/burak_logo.png', 'Logo tasarımı ve kaynak dosya.', '2026-11-09 18:20:00', 85, 'Renk paleti iyi, tipografi biraz zayıf.', '2026-11-10 11:00:00'),
    (3, 9, 'uploads/demo/ahmet_logo.png', 'İki farklı renk varyasyonu.', '2026-11-10 20:00:00', NULL, NULL, NULL);

-- ---------------------------------------------------------------------
-- Ders içerikleri
-- ---------------------------------------------------------------------
INSERT INTO ders_icerikleri (ders_acilis_id, hafta, baslik, aciklama) VALUES
    (6, 1, 'Ders tanıtımı ve geliştirme ortamı kurulumu', 'Editör kurulumu, ilk program, derleme ve çalıştırma.'),
    (6, 2, 'Değişkenler ve veri tipleri', 'int, double, string, bool; tür dönüşümleri.'),
    (6, 3, 'Karar yapıları', 'if / else if / else, switch-case, karşılaştırma operatörleri.'),
    (6, 4, 'Döngüler', 'for, while, do-while; iç içe döngüler.'),
    (7, 1, 'HTML temelleri', 'Etiket yapısı, başlıklar, listeler, bağlantılar.'),
    (7, 2, 'CSS temelleri', 'Seçiciler, kutu modeli, renkler ve tipografi.'),
    (7, 3, 'Flexbox ve responsive tasarım', 'Media query, mobil öncelikli yaklaşım.'),
    (11, 1, 'Veritabanı kavramları ve normalizasyon', '1NF, 2NF, 3NF örneklerle anlatım.'),
    (11, 2, 'SQL''e giriş', 'SELECT, WHERE, ORDER BY, LIMIT.'),
    (11, 3, 'Birleştirmeler (JOIN)', 'INNER, LEFT, RIGHT JOIN örnekleri.'),
    (11, 4, 'Gruplama ve alt sorgular', 'GROUP BY, HAVING, alt sorgular.');

-- ---------------------------------------------------------------------
-- Yemek menüsü (Faz 6 çalışmadan önce ekranın dolu görünmesi için)
-- Kaynak: Ankara Üniversitesi SKS Daire Başkanlığı aylık menüsü (yaklaşık kalori)
-- ---------------------------------------------------------------------
INSERT INTO yemek_menuleri
    (tarih, corba, ana_yemek, yardimci_yemek, tatli_salata, corba_kcal, ana_kcal, yardimci_kcal, ek_kcal, toplam_kcal, kaynak_url) VALUES
    ('2026-11-04', 'Ezogelin Çorba', 'İskender Kebap', 'Sade Pirinç Pilavı', 'Red Velvet',
     262, 567, 180, 250, 1259, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),

    ('2026-11-05', 'Düğürcük Çorba', 'Pilav Üstü Et Döner', 'Menekşe Salata', 'Ayran',
     196, 500, 84, 60, 840, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/'),

    ('2026-11-06', 'Yayla Çorba', 'İsveç Köfte (Sebze Garnitür)', 'Nohutlu Pirinç Pilavı', 'Meyve',
     168, 462, 200, 80, 910, 'https://sks.ankara.edu.tr/yemek-hizmetleri-2/');

-- ---------------------------------------------------------------------
-- Dış kaynak senkron durumu
-- ---------------------------------------------------------------------
INSERT INTO kaynak_senkron_durumu (kaynak, son_deneme, son_basari, durum, hata_mesaji) VALUES
    ('yemek',  NULL, NULL, 'bekliyor', NULL),
    ('haber',  NULL, NULL, 'bekliyor', NULL);
