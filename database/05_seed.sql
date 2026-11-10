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

    -- Demo öğrenciler (sunum için iki kişi yeterli)
    (6, 1, '2026101001', 'Burak', 'Yiğit Solmaz', 'burak.solmaz@ornek.local', @hash, '05001110001'),
    (7, 1, '2026101002', 'Kaan',  'Tokatlı',      'kaan.tokatli@ornek.local', @hash, '05001110002');

-- ---------------------------------------------------------------------
-- Profiller
-- ---------------------------------------------------------------------
INSERT INTO ogretim_elemani_profilleri (kullanici_id, bolum_id, unvan) VALUES
    (2, 1, 'Dr. Öğr. Üyesi'),
    (3, 1, 'Öğr. Gör. Dr.'),
    (4, 1, 'Öğr. Gör.'),
    (5, 4, 'Öğr. Gör.');   -- Elektrik ve Enerji Bölümü kadrosu, Bilgisayar Programcılığı'nda derse giriyor

INSERT INTO ogrenci_profilleri (kullanici_id, bolum_id, sinif, giris_yili) VALUES
    (6, 1, 2, 2026),
    (7, 1, 2, 2026);

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
    (10, 1, 'NMS265', 'Yapay Zeka',                   4, 5, 1, 2),
    (11, 1, 'NMS239', 'Veri Tabanı Uygulamaları',     4, 5, 1, 2),
    (12, 1, 'NMS269', 'Sunucu Tarafı Web Programlama',3, 4, 1, 2),
    (13, 1, 'NMS227', 'Mikrobilgisayar ve Assembler', 3, 4, 1, 2),
    (14, 1, 'NMS233', 'Görüntü İşlemenin Temelleri',  3, 4, 0, 2),
    (15, 1, 'NSS203', 'İşletme Yönetimi',             2, 3, 1, 2),
    (16, 1, 'NBP253', 'Dönem Projesi',                2, 4, 1, 2);

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
    (10, 10, 3, 5, 'A', 35),   -- Yapay Zeka - Güney Uğurlu
    (11, 11, 3, 3, 'A', 35),   -- Veri Tabanı Uygulamaları - Ufuk Tanyeri
    (12, 12, 3, 3, 'A', 35),   -- Sunucu Tarafı Web Programlama - Ufuk Tanyeri
    (13, 13, 3, 5, 'A', 35),   -- Mikrobilgisayar ve Assembler - Güney Uğurlu
    (14, 14, 3, 3, 'A', 30),   -- Görüntü İşlemenin Temelleri - Ufuk Tanyeri
    (15, 15, 3, 4, 'A', 40),   -- İşletme Yönetimi - Salih Erdurucan
    (16, 16, 3, 4, 'A', 30);   -- Dönem Projesi - Salih Erdurucan

-- ---------------------------------------------------------------------
-- Haftalık ders programı (aktif dönem) - gerçek programdan alınmıştır
-- gun: 1 = Pazartesi ... 5 = Cuma
-- ---------------------------------------------------------------------
INSERT INTO ders_programi (ders_acilis_id, gun, baslangic_saati, bitis_saati, derslik) VALUES
    -- 1. sınıf
    (6,  1, '15:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (6,  3, '15:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (7,  1, '09:00:00', '11:00:00', 'Bilgisayar Laboratuvarı 1'),
    (7,  1, '11:00:00', '13:00:00', 'Bilgisayar Laboratuvarı 1'),
    (8,  3, '10:30:00', '12:30:00', 'Bilgisayar Laboratuvarı 2'),
    (9,  4, '08:30:00', '12:30:00', 'Bilgisayar Laboratuvarı 2'),
    -- 2. sınıf
    (11, 2, '13:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (14, 3, '09:00:00', '12:30:00', 'Bilgisayar Laboratuvarı 1'),
    (12, 3, '13:30:00', '17:30:00', 'Bilgisayar Laboratuvarı 1'),
    (10, 4, '09:00:00', '11:00:00', 'Bilgisayar Laboratuvarı 1'),
    (10, 4, '11:00:00', '13:00:00', 'Bilgisayar Laboratuvarı 1'),
    (15, 4, '14:30:00', '16:30:00', 'Derslik 6'),
    (13, 5, '11:00:00', '13:00:00', 'Bilgisayar Laboratuvarı 2'),
    (13, 5, '13:30:00', '15:30:00', 'Bilgisayar Laboratuvarı 2'),
    (16, 5, '15:30:00', '17:30:00', 'Derslik 2');

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
     'NMS239 Veri Tabanı Uygulamaları vize sınavı 12 Kasım 2026 Perşembe günü 13:30''da Bilgisayar Laboratuvarı 1''de yapılacaktır. Sınav uygulamalıdır.',
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
    (3, 6, 'uploads/demo/burak_logo.png', 'Logo tasarımı ve kaynak dosya.', '2026-11-09 18:20:00', 85, 'Renk paleti iyi, tipografi biraz zayıf.', '2026-11-10 11:00:00');

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

-- =====================================================================
-- Ek demo içerik: ders detayları, ödevler, duyurular, yemek menüsü
-- =====================================================================

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

    (11, 10, 5, 'NMS265 Veri Kümesi Seçimi Başladı',
     'Yapay Zeka dersi dönem projesi için kullanılacak veri kümeleri ders sayfasına eklenmiştir. Küme seçimini 14 Kasım''a kadar yapmanız gerekmektedir.',
     0, '2026-11-05 11:30:00'),

    (12, NULL, 1, 'Yemekhane Menüsü Güncellendi',
     'Kasım ayı yemekhane menüsü güncellenmiştir. Günlük menü ana sayfada ve Yemekhane sayfasında görüntülenebilir.',
     0, '2026-11-06 08:45:00'),

    (13, 11, 3, 'NMS239 Telafi Dersi Yapılacaktır',
     '12 Kasım Perşembe günü yapılacak vize sınavı nedeniyle iptal edilen dersin telafisi 21 Kasım 2026 Cumartesi 10:00''da Bilgisayar Laboratuvarı 1''de yapılacaktır.',
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

    (8, 10, 'Yapay Zeka ile Sınıflandırma',
     'Verilen veri kümesi üzerinde bir sınıflandırma modeli eğitin; doğruluk, kesinlik ve duyarlılık metriklerini raporlayın.',
     '2026-12-05 23:59:00', 100),

    (9, 11, 'JOIN Sorguları Uygulaması',
     'Verilen şema üzerinde 5 farklı JOIN sorgusu yazın; her sorgunun amacını birer cümleyle açıklayın.',
     '2026-11-19 23:59:00', 100),

    (10, 12, 'PHP ile CRUD Uygulaması',
     'PDO kullanarak ekleme, listeleme, güncelleme ve silme işlemleri yapan bir öğrenci kayıt uygulaması hazırlayın.',
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
    -- NMS265 Yapay Zeka
    (10, 1, 'Yapay zekaya giriş',          'Temel kavramlar, tarihçe ve uygulama alanları.'),
    (10, 2, 'Veri ve ön işleme',           'Veri kümeleri, eksik veri, ölçekleme ve normalizasyon.'),
    (10, 3, 'Makine öğrenmesi temelleri',  'Gözetimli ve gözetimsiz öğrenme yaklaşımları.'),
    (10, 4, 'Sınıflandırma',               'KNN, karar ağacı ve doğruluk ölçütleri.'),
    (10, 5, 'Regresyon',                   'Doğrusal regresyon ve hata metrikleri.'),
    (10, 6, 'Sinir ağlarına giriş',        'Perceptron, katmanlar ve aktivasyon fonksiyonları.'),
    (10, 7, 'Görüntü işleme uygulamaları', 'Evrişimli ağlara giriş ve örnek uygulama.'),
    (10, 8, 'Dönem projesi',               'Model seçimi, değerlendirme ve sunum.'),

    -- NMS239 Veri Tabanı Uygulamaları
    (11, 5, 'İndeksler',                   'İndeks türleri, performansa etkisi, EXPLAIN.'),
    (11, 6, 'Görünümler ve prosedürler',   'VIEW, STORED PROCEDURE, parametreler.'),
    (11, 7, 'Tetikleyiciler ve işlemler',  'TRIGGER, TRANSACTION, COMMIT / ROLLBACK.'),
    (11, 8, 'Performans ve yedekleme',     'Sorgu iyileştirme, mysqldump, geri yükleme.'),

    -- NMS269 Sunucu Tarafı Web Programlama
    (12, 1, 'Web sunucu mimarisi',         'İstemci-sunucu modeli, HTTP ve portlar.'),
    (12, 2, 'PHP temelleri',               'Değişkenler, form verisi ve yönlendirme.'),
    (12, 3, 'Oturum ve çerezler',          'Session, cookie ve giriş akışı.'),
    (12, 4, 'Veritabanı bağlantısı',       'PDO, prepared statement ve CRUD.'),
    (12, 5, 'Dosya yükleme',               'Uzantı/MIME kontrolü ve güvenli yükleme.'),
    (12, 6, 'REST API',                    'JSON, HTTP metotları ve durum kodları.'),
    (12, 7, 'Kimlik doğrulama',            'Rol bazlı erişim ve token kullanımı.'),
    (12, 8, 'Güvenlik ve yayınlama',       'XSS, CSRF, SQL enjeksiyonu ve dağıtım.'),

    -- NMS227 Mikrobilgisayar ve Assembler
    (13, 1, 'Mikroişlemci mimarisi',       'ALU, kontrol birimi, veri yolu.'),
    (13, 2, 'Sayı sistemleri',             'İkilik, onaltılık, dönüşümler.'),
    (13, 3, 'Yazmaçlar',                   'Genel amaçlı ve özel yazmaçlar.'),
    (13, 4, 'Komut seti',                  'Veri taşıma ve aritmetik komutlar.'),
    (13, 5, 'Adresleme modları',           'Doğrudan, dolaylı, indeksli adresleme.'),
    (13, 6, 'Döngü ve dallanma',           'Karşılaştırma, koşullu atlama.'),
    (13, 7, 'Kesmeler',                    'Donanım ve yazılım kesmeleri.'),
    (13, 8, 'Laboratuvar',                 'Simülatör üzerinde program çalıştırma.'),

    -- NMS233 Görüntü İşlemenin Temelleri
    (14, 1, 'Görüntü işlemeye giriş',      'Piksel, renk uzayları ve uygulama alanları.'),
    (14, 2, 'Görüntü okuma ve yazma',      'OpenCV ile görüntü yükleme, formatlar ve boyutlandırma.'),
    (14, 3, 'Temel filtreler',             'Bulanıklaştırma, keskinleştirme ve gürültü azaltma.'),
    (14, 4, 'Histogram ve kontrast',       'Histogram eşitleme ve gamma düzeltmesi.'),
    (14, 5, 'Kenar ve köşe algılama',      'Sobel, Canny ve Harris yöntemleri.'),
    (14, 6, 'Morfolojik işlemler',         'Aşındırma, genişletme, açma ve kapama.'),
    (14, 7, 'Segmentasyon',                'Eşikleme, kontur çıkarma ve bölütleme.'),
    (14, 8, 'Mini proje',                  'Nesne sayma veya tanıma uygulaması.'),

    -- NSS203 İşletme Yönetimi
    (15, 1, 'İşletme ve yönetim',          'Temel kavramlar, amaçlar ve yönetim düzeyleri.'),
    (15, 2, 'Yönetim fonksiyonları',       'Planlama, örgütleme, yöneltme ve denetim.'),
    (15, 3, 'İşletmenin çevresi',          'İç ve dış çevre, paydaşlar ve belirsizlik.'),
    (15, 4, 'Kuruluş yeri ve türleri',     'Şirket türleri ve kuruluş süreci.'),
    (15, 5, 'Üretim yönetimi',             'Üretim planlama, kapasite ve verimlilik.'),
    (15, 6, 'Pazarlama yönetimi',          'Pazar araştırması ve pazarlama karması.'),
    (15, 7, 'İnsan kaynakları',            'İşe alım, performans ve motivasyon.'),
    (15, 8, 'Finans ve maliyet',           'Bütçe, maliyet analizi ve kârlılık.'),

    -- NBP253 Dönem Projesi
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
    (5, 6, 'uploads/demo/burak_carpim.pdf', 'İç içe döngü kullandım.', '2026-11-07 10:15:00', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    dosya_yolu = VALUES(dosya_yolu),
    aciklama = VALUES(aciklama),
    teslim_zamani = VALUES(teslim_zamani),
    puan = VALUES(puan),
    geri_bildirim = VALUES(geri_bildirim),
    degerlendirme_zamani = VALUES(degerlendirme_zamani);
