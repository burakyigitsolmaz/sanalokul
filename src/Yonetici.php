<?php

declare(strict_types=1);

/**
 * Yönetici paneli veri erişimi.
 *
 * Kullanıcı, bölüm, dönem, ders, ders açılışı, derse atama, genel duyuru,
 * işlem logu ve CSV içe aktarma işlemlerinin tamamı bu sınıfta toplanır.
 * Sayfalar bu sınıf üzerinden veritabanına erişir.
 */
final class Yonetici
{
    private function __construct()
    {
    }

    // ------------------------------------------------------------------
    // Genel
    // ------------------------------------------------------------------

    /**
     * Panel üst kartları için genel sayılar.
     *
     * @return array<string, mixed>
     */
    public static function sayilar(): array
    {
        return Database::get()->query(
            'SELECT
                (SELECT COUNT(*) FROM kullanicilar WHERE aktif = 1) AS kullanici,
                (SELECT COUNT(*) FROM ogrenci_profilleri) AS ogrenci,
                (SELECT COUNT(*) FROM ogretim_elemani_profilleri) AS hoca,
                (SELECT COUNT(*) FROM dersler) AS ders,
                (SELECT COUNT(*) FROM ders_acilislari) AS acilis,
                (SELECT COUNT(*) FROM ders_kayitlari WHERE durum = "aktif") AS kayit,
                (SELECT COUNT(*) FROM duyurular) AS duyuru'
        )->fetch();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function roller(): array
    {
        return Database::get()->query('SELECT id, ad FROM roller ORDER BY id')->fetchAll();
    }

    // ------------------------------------------------------------------
    // Kullanıcılar
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public static function kullanicilar(?string $rol = null, string $arama = '', int $limit = 300): array
    {
        $sql = 'SELECT k.id, k.numara, k.ad, k.soyad, k.eposta, k.telefon, k.aktif,
                       k.son_giris, k.olusturma_zamani,
                       r.ad AS rol, r.id AS rol_id,
                       COALESCE(op.bolum_id, oep.bolum_id) AS bolum_id,
                       b.ad AS bolum,
                       op.sinif, op.giris_yili,
                       oep.unvan
                FROM kullanicilar k
                    JOIN roller r ON r.id = k.rol_id
                    LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
                    LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                    LEFT JOIN bolumler b ON b.id = COALESCE(op.bolum_id, oep.bolum_id)
                WHERE 1 = 1';

        $parametreler = [];

        if ($rol !== null && $rol !== '') {
            $sql .= ' AND r.ad = :rol';
            $parametreler['rol'] = $rol;
        }

        if ($arama !== '') {
            $sql .= ' AND (k.ad LIKE :ara OR k.soyad LIKE :ara OR k.numara LIKE :ara OR k.eposta LIKE :ara)';
            $parametreler['ara'] = '%' . $arama . '%';
        }

        $sql .= ' ORDER BY r.id, k.soyad, k.ad LIMIT ' . max(1, min(1000, $limit));

        $sorgu = Database::get()->prepare($sql);
        $sorgu->execute($parametreler);

        return $sorgu->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function kullanici(int $id): ?array
    {
        $sorgu = Database::get()->prepare(
            'SELECT k.id, k.rol_id, k.numara, k.ad, k.soyad, k.eposta, k.telefon, k.aktif,
                    r.ad AS rol,
                    COALESCE(op.bolum_id, oep.bolum_id) AS bolum_id,
                    op.sinif, op.giris_yili,
                    oep.unvan
             FROM kullanicilar k
                 JOIN roller r ON r.id = k.rol_id
                 LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
                 LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
             WHERE k.id = :id
             LIMIT 1'
        );
        $sorgu->execute(['id' => $id]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Yeni kullanıcı + rolüne göre profil kaydı oluşturur.
     *
     * @param array<string, mixed> $v
     * @return array{0: bool, 1: int, 2: string} [basarili, id, mesaj]
     */
    public static function kullaniciEkle(array $v): array
    {
        $pdo = Database::get();

        try {
            $pdo->beginTransaction();

            $sorgu = $pdo->prepare(
                'INSERT INTO kullanicilar (rol_id, numara, ad, soyad, eposta, sifre_hash, telefon, aktif)
                 VALUES (:rol_id, :numara, :ad, :soyad, :eposta, :hash, :telefon, :aktif)'
            );
            $sorgu->execute([
                'rol_id'  => self::rolId((string) $v['rol']),
                'numara'  => (string) $v['numara'],
                'ad'      => (string) $v['ad'],
                'soyad'   => (string) $v['soyad'],
                'eposta'  => (string) $v['eposta'],
                'hash'    => password_hash((string) $v['sifre'], PASSWORD_DEFAULT),
                'telefon' => self::bosuNull((string) ($v['telefon'] ?? '')),
                'aktif'   => !empty($v['aktif']) ? 1 : 0,
            ]);

            $id = (int) $pdo->lastInsertId();
            self::profilYaz($id, $v);

            $pdo->commit();

            return [true, $id, 'Kullanıcı oluşturuldu.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [false, 0, self::hataMesaji($e)];
        }
    }

    /**
     * @param array<string, mixed> $v
     * @return array{0: bool, 1: string}
     */
    public static function kullaniciGuncelle(int $id, array $v): array
    {
        $pdo = Database::get();
        $mevcut = self::kullanici($id);

        if ($mevcut === null) {
            return [false, 'Kullanıcı bulunamadı.'];
        }

        try {
            $pdo->beginTransaction();

            $sql = 'UPDATE kullanicilar
                    SET rol_id = :rol_id, numara = :numara, ad = :ad, soyad = :soyad,
                        eposta = :eposta, telefon = :telefon, aktif = :aktif';
            $param = [
                'rol_id'  => self::rolId((string) $v['rol']),
                'numara'  => (string) $v['numara'],
                'ad'      => (string) $v['ad'],
                'soyad'   => (string) $v['soyad'],
                'eposta'  => (string) $v['eposta'],
                'telefon' => self::bosuNull((string) ($v['telefon'] ?? '')),
                'aktif'   => !empty($v['aktif']) ? 1 : 0,
                'id'      => $id,
            ];

            if (!empty($v['sifre'])) {
                $sql .= ', sifre_hash = :hash';
                $param['hash'] = password_hash((string) $v['sifre'], PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id = :id';

            $pdo->prepare($sql)->execute($param);
            self::profilYaz($id, $v);

            $pdo->commit();

            return [true, 'Kullanıcı güncellendi.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function kullaniciSil(int $id): array
    {
        if (self::kullanici($id) === null) {
            return [false, 'Kullanıcı bulunamadı.'];
        }

        try {
            $sorgu = Database::get()->prepare('DELETE FROM kullanicilar WHERE id = :id');
            $sorgu->execute(['id' => $id]);

            return [true, 'Kullanıcı silindi.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function sifreSifirla(int $id, string $yeniSifre): array
    {
        if (mb_strlen($yeniSifre, 'UTF-8') < 8) {
            return [false, 'Şifre en az 8 karakter olmalıdır.'];
        }

        $sorgu = Database::get()->prepare('UPDATE kullanicilar SET sifre_hash = :hash WHERE id = :id');
        $sorgu->execute([
            'hash' => password_hash($yeniSifre, PASSWORD_DEFAULT),
            'id'   => $id,
        ]);

        return $sorgu->rowCount() > 0
            ? [true, 'Şifre güncellendi.']
            : [false, 'Kullanıcı bulunamadı.'];
    }

    // ------------------------------------------------------------------
    // Bölümler
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public static function bolumler(): array
    {
        return Database::get()->query(
            'SELECT b.id, b.kod, b.ad,
                    (SELECT COUNT(*) FROM dersler d WHERE d.bolum_id = b.id) AS ders_sayisi,
                    (SELECT COUNT(*) FROM ogrenci_profilleri op WHERE op.bolum_id = b.id) AS ogrenci_sayisi,
                    (SELECT COUNT(*) FROM ogretim_elemani_profilleri oep WHERE oep.bolum_id = b.id) AS hoca_sayisi
             FROM bolumler b
             ORDER BY b.kod'
        )->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function bolumEkle(string $kod, string $ad): array
    {
        try {
            Database::get()->prepare('INSERT INTO bolumler (kod, ad) VALUES (:kod, :ad)')
                ->execute(['kod' => $kod, 'ad' => $ad]);

            return [true, 'Bölüm eklendi.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function bolumGuncelle(int $id, string $kod, string $ad): array
    {
        try {
            $sorgu = Database::get()->prepare('UPDATE bolumler SET kod = :kod, ad = :ad WHERE id = :id');
            $sorgu->execute(['kod' => $kod, 'ad' => $ad, 'id' => $id]);

            return $sorgu->rowCount() > 0
                ? [true, 'Bölüm güncellendi.']
                : [false, 'Bölüm bulunamadı ya da değişiklik yok.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function bolumSil(int $id): array
    {
        try {
            $sorgu = Database::get()->prepare('DELETE FROM bolumler WHERE id = :id');
            $sorgu->execute(['id' => $id]);

            return $sorgu->rowCount() > 0
                ? [true, 'Bölüm silindi.']
                : [false, 'Bölüm bulunamadı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    // ------------------------------------------------------------------
    // Dönemler
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public static function donemler(): array
    {
        return Database::get()->query(
            'SELECT dn.id, dn.ad, dn.baslangic, dn.bitis, dn.aktif,
                    (SELECT COUNT(*) FROM ders_acilislari da WHERE da.donem_id = dn.id) AS acilis_sayisi,
                    (SELECT COUNT(*) FROM ders_kayitlari dk
                        JOIN ders_acilislari da2 ON da2.id = dk.ders_acilis_id
                     WHERE da2.donem_id = dn.id AND dk.durum = "aktif") AS kayit_sayisi
             FROM donemler dn
             ORDER BY dn.baslangic DESC'
        )->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function donemEkle(string $ad, string $baslangic, string $bitis, bool $aktif): array
    {
        if (strtotime($bitis) <= strtotime($baslangic)) {
            return [false, 'Bitiş tarihi başlangıçtan sonra olmalıdır.'];
        }

        $pdo = Database::get();

        try {
            $pdo->beginTransaction();

            if ($aktif) {
                $pdo->exec('UPDATE donemler SET aktif = 0');
            }

            $pdo->prepare(
                'INSERT INTO donemler (ad, baslangic, bitis, aktif) VALUES (:ad, :bas, :bit, :aktif)'
            )->execute([
                'ad'    => $ad,
                'bas'   => $baslangic,
                'bit'   => $bitis,
                'aktif' => $aktif ? 1 : 0,
            ]);

            $pdo->commit();

            return [true, 'Dönem eklendi.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function donemGuncelle(int $id, string $ad, string $baslangic, string $bitis, bool $aktif): array
    {
        if (strtotime($bitis) <= strtotime($baslangic)) {
            return [false, 'Bitiş tarihi başlangıçtan sonra olmalıdır.'];
        }

        $pdo = Database::get();

        try {
            $pdo->beginTransaction();

            if ($aktif) {
                $pdo->exec('UPDATE donemler SET aktif = 0');
            }

            $pdo->prepare(
                'UPDATE donemler SET ad = :ad, baslangic = :bas, bitis = :bit, aktif = :aktif WHERE id = :id'
            )->execute([
                'ad'    => $ad,
                'bas'   => $baslangic,
                'bit'   => $bitis,
                'aktif' => $aktif ? 1 : 0,
                'id'    => $id,
            ]);

            $pdo->commit();

            return [true, 'Dönem güncellendi.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function donemAktifYap(int $id): array
    {
        $pdo = Database::get();

        try {
            $pdo->beginTransaction();
            $pdo->exec('UPDATE donemler SET aktif = 0');
            $pdo->prepare('UPDATE donemler SET aktif = 1 WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();

            return [true, 'Aktif dönem değiştirildi.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function donemSil(int $id): array
    {
        try {
            $sorgu = Database::get()->prepare('DELETE FROM donemler WHERE id = :id');
            $sorgu->execute(['id' => $id]);

            return $sorgu->rowCount() > 0
                ? [true, 'Dönem silindi.']
                : [false, 'Dönem bulunamadı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    // ------------------------------------------------------------------
    // Dersler
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public static function dersler(?int $bolumId = null): array
    {
        $sql = 'SELECT d.id, d.bolum_id, d.kod, d.ad, d.kredi, d.akts, d.zorunlu_mu, d.sinif,
                       b.kod AS bolum_kod, b.ad AS bolum_ad,
                       (SELECT COUNT(*) FROM ders_acilislari da WHERE da.ders_id = d.id) AS acilis_sayisi
                FROM dersler d
                    JOIN bolumler b ON b.id = d.bolum_id
                WHERE 1 = 1';

        $param = [];

        if ($bolumId !== null && $bolumId > 0) {
            $sql .= ' AND d.bolum_id = :bolum';
            $param['bolum'] = $bolumId;
        }

        $sql .= ' ORDER BY d.sinif, d.kod';

        $sorgu = Database::get()->prepare($sql);
        $sorgu->execute($param);

        return $sorgu->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function ders(int $id): ?array
    {
        $sorgu = Database::get()->prepare(
            'SELECT id, bolum_id, kod, ad, kredi, akts, zorunlu_mu, sinif FROM dersler WHERE id = :id LIMIT 1'
        );
        $sorgu->execute(['id' => $id]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function dersEkle(array $v): array
    {
        try {
            Database::get()->prepare(
                'INSERT INTO dersler (bolum_id, kod, ad, kredi, akts, zorunlu_mu, sinif)
                 VALUES (:bolum, :kod, :ad, :kredi, :akts, :zorunlu, :sinif)'
            )->execute([
                'bolum'   => (int) $v['bolum_id'],
                'kod'     => (string) $v['kod'],
                'ad'      => (string) $v['ad'],
                'kredi'   => (int) $v['kredi'],
                'akts'    => (int) $v['akts'],
                'zorunlu' => !empty($v['zorunlu_mu']) ? 1 : 0,
                'sinif'   => (int) $v['sinif'],
            ]);

            return [true, 'Ders eklendi.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function dersGuncelle(int $id, array $v): array
    {
        try {
            $sorgu = Database::get()->prepare(
                'UPDATE dersler
                 SET bolum_id = :bolum, kod = :kod, ad = :ad, kredi = :kredi,
                     akts = :akts, zorunlu_mu = :zorunlu, sinif = :sinif
                 WHERE id = :id'
            );
            $sorgu->execute([
                'bolum'   => (int) $v['bolum_id'],
                'kod'     => (string) $v['kod'],
                'ad'      => (string) $v['ad'],
                'kredi'   => (int) $v['kredi'],
                'akts'    => (int) $v['akts'],
                'zorunlu' => !empty($v['zorunlu_mu']) ? 1 : 0,
                'sinif'   => (int) $v['sinif'],
                'id'      => $id,
            ]);

            return [true, 'Ders güncellendi.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function dersSil(int $id): array
    {
        try {
            $sorgu = Database::get()->prepare('DELETE FROM dersler WHERE id = :id');
            $sorgu->execute(['id' => $id]);

            return $sorgu->rowCount() > 0
                ? [true, 'Ders ve bağlı açılış/kayıtları silindi.']
                : [false, 'Ders bulunamadı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    // ------------------------------------------------------------------
    // Ders açılışları
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public static function acilislar(?int $donemId = null): array
    {
        $sql = 'SELECT da.id, da.ders_id, da.donem_id, da.ogretim_elemani_id, da.sube, da.kontenjan,
                       d.kod, d.ad AS ders_adi, d.sinif,
                       dn.ad AS donem, dn.aktif AS donem_aktif,
                       CONCAT(COALESCE(oep.unvan, ""), " ", k.ad, " ", k.soyad) AS hoca,
                       (SELECT COUNT(*) FROM ders_kayitlari dk
                         WHERE dk.ders_acilis_id = da.id AND dk.durum = "aktif") AS kayit_sayisi,
                       (SELECT COUNT(*) FROM ders_programi dp WHERE dp.ders_acilis_id = da.id) AS oturum_sayisi
                FROM ders_acilislari da
                    JOIN dersler d ON d.id = da.ders_id
                    JOIN donemler dn ON dn.id = da.donem_id
                    JOIN kullanicilar k ON k.id = da.ogretim_elemani_id
                    LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                WHERE 1 = 1';

        $param = [];

        if ($donemId !== null && $donemId > 0) {
            $sql .= ' AND da.donem_id = :donem';
            $param['donem'] = $donemId;
        }

        $sql .= ' ORDER BY dn.baslangic DESC, d.kod, da.sube';

        $sorgu = Database::get()->prepare($sql);
        $sorgu->execute($param);

        return $sorgu->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function acilis(int $id): ?array
    {
        $sorgu = Database::get()->prepare(
            'SELECT da.id, da.ders_id, da.donem_id, da.ogretim_elemani_id, da.sube, da.kontenjan
             FROM ders_acilislari da
             WHERE da.id = :id
             LIMIT 1'
        );
        $sorgu->execute(['id' => $id]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Ders açılışı ekler.
     *
     * @return array{0: bool, 1: string}
     */
    public static function acilisEkle(array $v): array
    {
        if (!self::hocaMi((int) $v['ogretim_elemani_id'])) {
            return [false, 'Seçilen kullanıcı öğretim elemanı değil.'];
        }

        try {
            Database::get()->prepare(
                'INSERT INTO ders_acilislari (ders_id, donem_id, ogretim_elemani_id, sube, kontenjan)
                 VALUES (:ders, :donem, :hoca, :sube, :kontenjan)'
            )->execute([
                'ders'      => (int) $v['ders_id'],
                'donem'     => (int) $v['donem_id'],
                'hoca'      => (int) $v['ogretim_elemani_id'],
                'sube'      => (string) $v['sube'],
                'kontenjan' => self::bosuNull((string) ($v['kontenjan'] ?? '')) === null ? null : (int) $v['kontenjan'],
            ]);

            return [true, 'Ders açılışı eklendi.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function acilisGuncelle(int $id, array $v): array
    {
        if (!self::hocaMi((int) $v['ogretim_elemani_id'])) {
            return [false, 'Seçilen kullanıcı öğretim elemanı değil.'];
        }

        try {
            $sorgu = Database::get()->prepare(
                'UPDATE ders_acilislari
                 SET ders_id = :ders, donem_id = :donem, ogretim_elemani_id = :hoca,
                     sube = :sube, kontenjan = :kontenjan
                 WHERE id = :id'
            );
            $sorgu->execute([
                'ders'      => (int) $v['ders_id'],
                'donem'     => (int) $v['donem_id'],
                'hoca'      => (int) $v['ogretim_elemani_id'],
                'sube'      => (string) $v['sube'],
                'kontenjan' => self::bosuNull((string) ($v['kontenjan'] ?? '')) === null ? null : (int) $v['kontenjan'],
                'id'        => $id,
            ]);

            return [true, 'Ders açılışı güncellendi.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function acilisSil(int $id): array
    {
        try {
            $sorgu = Database::get()->prepare('DELETE FROM ders_acilislari WHERE id = :id');
            $sorgu->execute(['id' => $id]);

            return $sorgu->rowCount() > 0
                ? [true, 'Ders açılışı ve bağlı kayıtlar silindi.']
                : [false, 'Ders açılışı bulunamadı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function hocalar(): array
    {
        return Database::get()->query(
            'SELECT k.id, k.numara, k.ad, k.soyad,
                    COALESCE(oep.unvan, "") AS unvan,
                    b.ad AS bolum
             FROM kullanicilar k
                 JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                 LEFT JOIN bolumler b ON b.id = oep.bolum_id
             WHERE k.aktif = 1
             ORDER BY k.soyad, k.ad'
        )->fetchAll();
    }

    // ------------------------------------------------------------------
    // Derse atama
    // ------------------------------------------------------------------

    /**
     * Atama ekranı için açılış listesi + doluluk.
     *
     * @return list<array<string, mixed>>
     */
    public static function atamaAcilislari(): array
    {
        return Database::get()->query(
            'SELECT da.id, da.sube, da.kontenjan,
                    d.kod, d.ad AS ders_adi,
                    dn.ad AS donem, dn.aktif AS donem_aktif,
                    CONCAT(k.ad, " ", k.soyad) AS hoca,
                    (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = da.id AND dk.durum = "aktif") AS kayit
             FROM ders_acilislari da
                 JOIN dersler d ON d.id = da.ders_id
                 JOIN donemler dn ON dn.id = da.donem_id
                 JOIN kullanicilar k ON k.id = da.ogretim_elemani_id
             ORDER BY dn.baslangic DESC, d.kod, da.sube'
        )->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function atamaAcilisi(int $id): ?array
    {
        $sorgu = Database::get()->prepare(
            'SELECT da.id, da.sube, da.kontenjan,
                    d.kod, d.ad AS ders_adi,
                    dn.ad AS donem,
                    CONCAT(k.ad, " ", k.soyad) AS hoca,
                    (SELECT COUNT(*) FROM ders_kayitlari dk
                     WHERE dk.ders_acilis_id = da.id AND dk.durum = "aktif") AS kayit
             FROM ders_acilislari da
                 JOIN dersler d ON d.id = da.ders_id
                 JOIN donemler dn ON dn.id = da.donem_id
                 JOIN kullanicilar k ON k.id = da.ogretim_elemani_id
             WHERE da.id = :id
             LIMIT 1'
        );
        $sorgu->execute(['id' => $id]);
        $satir = $sorgu->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function atamaKayitlari(int $acilisId): array
    {
        $sorgu = Database::get()->prepare(
            'SELECT dk.id AS kayit_id, dk.durum, dk.kayit_zamani,
                    k.id AS ogrenci_id, k.numara, k.ad, k.soyad,
                    op.sinif
             FROM ders_kayitlari dk
                 JOIN kullanicilar k ON k.id = dk.ogrenci_id
                 LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
             WHERE dk.ders_acilis_id = :acilis
             ORDER BY k.soyad, k.ad'
        );
        $sorgu->execute(['acilis' => $acilisId]);

        return $sorgu->fetchAll();
    }

    /**
     * Derse kayıtlı olmayan öğrenciler.
     *
     * @return list<array<string, mixed>>
     */
    public static function atamaAdaylari(int $acilisId, string $arama = ''): array
    {
        $sql = 'SELECT k.id, k.numara, k.ad, k.soyad, op.sinif
                FROM kullanicilar k
                    JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
                WHERE k.aktif = 1
                  AND k.id NOT IN (
                      SELECT dk.ogrenci_id FROM ders_kayitlari dk WHERE dk.ders_acilis_id = :acilis
                  )';

        $param = ['acilis' => $acilisId];

        if ($arama !== '') {
            $sql .= ' AND (k.ad LIKE :ara OR k.soyad LIKE :ara OR k.numara LIKE :ara)';
            $param['ara'] = '%' . $arama . '%';
        }

        $sql .= ' ORDER BY k.soyad, k.ad LIMIT 100';

        $sorgu = Database::get()->prepare($sql);
        $sorgu->execute($param);

        return $sorgu->fetchAll();
    }

    /**
     * Kontenjan + çakışma + mükerrer kontrolünü veritabanı prosedürü yapar.
     *
     * @return array{0: bool, 1: string}
     */
    public static function derseKaydet(int $ogrenciId, int $acilisId): array
    {
        try {
            $pdo = Database::get();

            $cagri = $pdo->prepare('CALL sp_derse_kayit_ol(:ogrenci, :acilis, @ok, @mesaj)');
            $cagri->execute(['ogrenci' => $ogrenciId, 'acilis' => $acilisId]);
            $cagri->closeCursor();

            $sonuc = $pdo->query('SELECT @ok AS ok, @mesaj AS mesaj')->fetch();

            if ($sonuc === false) {
                return [false, 'Kayıt sonucu okunamadı.'];
            }

            return [(int) $sonuc['ok'] === 1, (string) $sonuc['mesaj']];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function kayitSil(int $kayitId): array
    {
        try {
            $sorgu = Database::get()->prepare('DELETE FROM ders_kayitlari WHERE id = :id');
            $sorgu->execute(['id' => $kayitId]);

            return $sorgu->rowCount() > 0
                ? [true, 'Ders kaydı silindi.']
                : [false, 'Kayıt bulunamadı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    // ------------------------------------------------------------------
    // Duyurular
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public static function duyurular(?string $kapsam = null): array
    {
        $sql = 'SELECT d.id, d.ders_acilis_id, d.baslik, d.icerik, d.onemli,
                       d.yayin_tarihi, d.bitis_tarihi,
                       dr.kod AS ders_kod, dr.ad AS ders_adi,
                       CONCAT(k.ad, " ", k.soyad) AS yazar
                FROM duyurular d
                    LEFT JOIN ders_acilislari da ON da.id = d.ders_acilis_id
                    LEFT JOIN dersler dr ON dr.id = da.ders_id
                    JOIN kullanicilar k ON k.id = d.yazar_id
                WHERE 1 = 1';

        if ($kapsam === 'genel') {
            $sql .= ' AND d.ders_acilis_id IS NULL';
        } elseif ($kapsam === 'ders') {
            $sql .= ' AND d.ders_acilis_id IS NOT NULL';
        }

        $sql .= ' ORDER BY d.yayin_tarihi DESC, d.id DESC';

        return Database::get()->query($sql)->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function duyuruEkle(int $yazarId, string $baslik, string $icerik, bool $onemli, ?string $bitis): array
    {
        try {
            Database::get()->prepare(
                'INSERT INTO duyurular (ders_acilis_id, yazar_id, baslik, icerik, onemli, bitis_tarihi)
                 VALUES (NULL, :yazar, :baslik, :icerik, :onemli, :bitis)'
            )->execute([
                'yazar'  => $yazarId,
                'baslik' => $baslik,
                'icerik' => $icerik,
                'onemli' => $onemli ? 1 : 0,
                'bitis'  => $bitis,
            ]);

            return [true, 'Genel duyuru yayınlandı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function duyuruSil(int $id): array
    {
        try {
            $sorgu = Database::get()->prepare('DELETE FROM duyurular WHERE id = :id');
            $sorgu->execute(['id' => $id]);

            return $sorgu->rowCount() > 0
                ? [true, 'Duyuru silindi.']
                : [false, 'Duyuru bulunamadı.'];
        } catch (Throwable $e) {
            return [false, self::hataMesaji($e)];
        }
    }

    // ------------------------------------------------------------------
    // İşlem logları
    // ------------------------------------------------------------------

    /**
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    public static function loglar(string $arama = '', string $tablo = '', int $limit = 50, int $offset = 0): array
    {
        $nerede = ' WHERE 1 = 1';
        $param = [];

        if ($arama !== '') {
            $nerede .= ' AND (l.islem LIKE :ara OR k.ad LIKE :ara OR k.soyad LIKE :ara OR l.ip LIKE :ara)';
            $param['ara'] = '%' . $arama . '%';
        }

        if ($tablo !== '') {
            $nerede .= ' AND l.tablo_adi = :tablo';
            $param['tablo'] = $tablo;
        }

        $sayi = Database::get()->prepare(
            'SELECT COUNT(*) FROM islem_loglari l LEFT JOIN kullanicilar k ON k.id = l.kullanici_id' . $nerede
        );
        $sayi->execute($param);
        $toplam = (int) $sayi->fetchColumn();

        $sql = 'SELECT l.id, l.islem, l.tablo_adi, l.kayit_id, l.ip, l.zaman,
                       CONCAT(COALESCE(k.ad, "Sistem"), " ", COALESCE(k.soyad, "")) AS kim,
                       k.numara
                FROM islem_loglari l
                    LEFT JOIN kullanicilar k ON k.id = l.kullanici_id'
            . $nerede
            . ' ORDER BY l.id DESC LIMIT ' . max(1, min(200, $limit))
            . ' OFFSET ' . max(0, $offset);

        $sorgu = Database::get()->prepare($sql);
        $sorgu->execute($param);

        return [$sorgu->fetchAll(), $toplam];
    }

    /**
     * @return list<string>
     */
    public static function logTablolari(): array
    {
        return Database::get()->query(
            'SELECT DISTINCT tablo_adi FROM islem_loglari WHERE tablo_adi IS NOT NULL ORDER BY tablo_adi'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    // ------------------------------------------------------------------
    // CSV içe aktarma
    // ------------------------------------------------------------------

    /**
     * Öğrenci CSV satırlarını içe aktarır.
     *
     * Beklenen sütunlar: numara, ad, soyad, eposta, bolum_kod, sinif, giris_yili
     *
     * @param list<array<string, string>> $satirlar
     * @return array{eklenen: int, hatalar: list<string>}
     */
    public static function csvOgrenciAktar(array $satirlar, string $varsayilanSifre): array
    {
        $pdo = Database::get();
        $bolumler = [];

        foreach (self::bolumler() as $bolum) {
            $bolumler[mb_strtoupper((string) $bolum['kod'], 'UTF-8')] = (int) $bolum['id'];
        }

        $rolId = self::rolId('ogrenci');
        $eklenen = 0;
        $hatalar = [];

        foreach ($satirlar as $sira => $satir) {
            $numara = trim((string) ($satir['numara'] ?? ''));
            $ad     = trim((string) ($satir['ad'] ?? ''));
            $soyad  = trim((string) ($satir['soyad'] ?? ''));
            $eposta = trim((string) ($satir['eposta'] ?? ''));
            $bolumK = mb_strtoupper(trim((string) ($satir['bolum_kod'] ?? '')), 'UTF-8');
            $sinif  = (int) ($satir['sinif'] ?? 1);
            $yil    = (int) ($satir['giris_yili'] ?? date('Y'));

            if ($numara === '' || $ad === '' || $soyad === '' || $eposta === '') {
                $hatalar[] = 'Satır ' . ($sira + 2) . ': numara, ad, soyad ve e-posta zorunlu.';
                continue;
            }

            if (!isset($bolumler[$bolumK])) {
                $hatalar[] = 'Satır ' . ($sira + 2) . ': bölüm kodu bulunamadı (' . $bolumK . ').';
                continue;
            }

            if ($sinif < 1 || $sinif > 2) {
                $hatalar[] = 'Satır ' . ($sira + 2) . ': sınıf 1 veya 2 olmalı.';
                continue;
            }

            try {
                $pdo->beginTransaction();

                $pdo->prepare(
                    'INSERT INTO kullanicilar (rol_id, numara, ad, soyad, eposta, sifre_hash, aktif)
                     VALUES (:rol, :numara, :ad, :soyad, :eposta, :hash, 1)'
                )->execute([
                    'rol'    => $rolId,
                    'numara' => $numara,
                    'ad'     => $ad,
                    'soyad'  => $soyad,
                    'eposta' => $eposta,
                    'hash'   => password_hash($varsayilanSifre, PASSWORD_DEFAULT),
                ]);

                $id = (int) $pdo->lastInsertId();

                $pdo->prepare(
                    'INSERT INTO ogrenci_profilleri (kullanici_id, bolum_id, sinif, giris_yili)
                     VALUES (:id, :bolum, :sinif, :yil)'
                )->execute([
                    'id'    => $id,
                    'bolum' => $bolumler[$bolumK],
                    'sinif' => $sinif,
                    'yil'   => $yil,
                ]);

                $pdo->commit();
                $eklenen++;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $hatalar[] = 'Satır ' . ($sira + 2) . ': ' . self::hataMesaji($e);
            }
        }

        return ['eklenen' => $eklenen, 'hatalar' => $hatalar];
    }

    // ------------------------------------------------------------------
    // Yardımcılar
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $v
     */
    private static function profilYaz(int $id, array $v): void
    {
        $pdo = Database::get();
        $pdo->prepare('DELETE FROM ogrenci_profilleri WHERE kullanici_id = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM ogretim_elemani_profilleri WHERE kullanici_id = :id')->execute(['id' => $id]);

        $rol = (string) ($v['rol'] ?? '');

        if ($rol === 'ogrenci') {
            $pdo->prepare(
                'INSERT INTO ogrenci_profilleri (kullanici_id, bolum_id, sinif, giris_yili)
                 VALUES (:id, :bolum, :sinif, :yil)'
            )->execute([
                'id'    => $id,
                'bolum' => (int) ($v['bolum_id'] ?? 0),
                'sinif' => (int) ($v['sinif'] ?? 1),
                'yil'   => (int) ($v['giris_yili'] ?? date('Y')),
            ]);
        } elseif ($rol === 'ogretim_elemani') {
            $pdo->prepare(
                'INSERT INTO ogretim_elemani_profilleri (kullanici_id, bolum_id, unvan)
                 VALUES (:id, :bolum, :unvan)'
            )->execute([
                'id'    => $id,
                'bolum' => (int) ($v['bolum_id'] ?? 0),
                'unvan' => trim((string) ($v['unvan'] ?? 'Öğretim Görevlisi')),
            ]);
        }
    }

    private static function rolId(string $rol): int
    {
        $sorgu = Database::get()->prepare('SELECT id FROM roller WHERE ad = :ad LIMIT 1');
        $sorgu->execute(['ad' => $rol]);
        $id = $sorgu->fetchColumn();

        return $id === false ? 1 : (int) $id;
    }

    private static function hocaMi(int $kullaniciId): bool
    {
        $sorgu = Database::get()->prepare(
            'SELECT 1 FROM ogretim_elemani_profilleri WHERE kullanici_id = :id LIMIT 1'
        );
        $sorgu->execute(['id' => $kullaniciId]);

        return $sorgu->fetchColumn() !== false;
    }

    private static function bosuNull(string $deger): ?string
    {
        $deger = trim($deger);

        return $deger === '' ? null : $deger;
    }

    private static function hataMesaji(Throwable $e): string
    {
        $mesaj = $e->getMessage();

        if ($e instanceof PDOException) {
            if (str_contains($mesaj, 'uq_kullanici_eposta')) {
                return 'Bu e-posta adresi zaten kayıtlı.';
            }

            if (str_contains($mesaj, 'uq_kullanici_numara')) {
                return 'Bu numara zaten kayıtlı.';
            }

            if (str_contains($mesaj, 'uq_bolumler_kod')) {
                return 'Bu bölüm kodu zaten var.';
            }

            if (str_contains($mesaj, 'uq_donemler_ad')) {
                return 'Bu dönem adı zaten var.';
            }

            if (str_contains($mesaj, 'uq_dersler_kod')) {
                return 'Bu ders kodu zaten var.';
            }

            if (str_contains($mesaj, 'uq_ders_acilisi')) {
                return 'Bu ders, bu dönemde aynı şubeyle zaten açılmış.';
            }

            if (str_contains($mesaj, 'uq_dk_ogrenci_ders')) {
                return 'Öğrenci bu derse zaten kayıtlı.';
            }

            if (str_contains($mesaj, 'FOREIGN KEY')) {
                return 'Bu kayda bağlı başka kayıtlar var; önce onları silin veya devredin.';
            }

            if (str_contains($mesaj, 'chk_')) {
                return 'Girilen değerlerden biri geçersiz (tarih, sınıf veya kredi aralığı).';
            }
        }

        return 'İşlem tamamlanamadı: ' . $mesaj;
    }
}
