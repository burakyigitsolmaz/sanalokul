<?php

declare(strict_types=1);

/**
 * Oturum, kimlik doğrulama ve rol yönetimi.
 *
 * Akış:
 *   Auth::baslat()            -> oturumu güvenli ayarlarla açar
 *   Auth::girisYap($e, $s)    -> bilgileri doğrular, deneme kaydı tutar
 *   Auth::kullanici()         -> oturumdaki kullanıcı dizisi (yoksa null)
 *   Auth::rolGerekli([...])   -> yetkisizse 403 sayfasına yönlendirir
 */
final class Auth
{
    /** Giriş yapmış kullanıcının oturumdaki kopyası. */
    private static ?array $kullanici = null;

    /** Oturum başlatıldı mı? */
    private static bool $basladi = false;

    private function __construct()
    {
    }

    // ------------------------------------------------------------------
    // Oturum
    // ------------------------------------------------------------------

    public static function baslat(): void
    {
        if (self::$basladi) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$basladi = true;
            self::sureKontrol();

            return;
        }

        $omur = (int) config('oturum.omur', 7200);

        session_name((string) config('oturum.ad', 'sanalokul_oturum'));

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $omur);

        session_start();
        self::$basladi = true;

        self::sureKontrol();
    }

    /**
     * Hareketsizlik süresi dolduysa oturumu kapatır.
     */
    private static function sureKontrol(): void
    {
        $omur = (int) config('oturum.omur', 7200);
        $son  = (int) ($_SESSION['son_aktivite'] ?? 0);

        if ($son > 0 && (time() - $son) > $omur) {
            self::cikisYap(true);

            return;
        }

        $_SESSION['son_aktivite'] = time();
    }

    public static function girisYapmisMi(): bool
    {
        return isset($_SESSION['kullanici_id']);
    }

    /**
     * Oturumdaki kullanıcının bilgileri. Giriş yoksa null.
     *
     * @return array<string, mixed>|null
     */
    public static function kullanici(): ?array
    {
        if (!self::girisYapmisMi()) {
            return null;
        }

        if (self::$kullanici === null) {
            $_SESSION['kullanici'] ??= self::kullaniciyiYukle((int) $_SESSION['kullanici_id']);
            self::$kullanici = is_array($_SESSION['kullanici']) ? $_SESSION['kullanici'] : null;
        }

        return self::$kullanici;
    }

    /**
     * Kullanıcıyı veritabanından tazeler (profil değişikliğinden sonra).
     *
     * @return array<string, mixed>|null
     */
    public static function yenile(): ?array
    {
        if (!self::girisYapmisMi()) {
            return null;
        }

        $_SESSION['kullanici'] = self::kullaniciyiYukle((int) $_SESSION['kullanici_id']);
        self::$kullanici = is_array($_SESSION['kullanici']) ? $_SESSION['kullanici'] : null;

        if (self::$kullanici === null) {
            self::cikisYap(true);
        }

        return self::$kullanici;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function kullaniciyiYukle(int $id): ?array
    {
        $sorgu = Database::get()->prepare(
            'SELECT k.id, k.numara, k.ad, k.soyad, k.eposta, k.telefon, k.tema_tercihi,
                    k.son_giris, r.ad AS rol,
                    b.ad AS bolum,
                    op.sinif,
                    oep.unvan
             FROM kullanicilar k
                 JOIN roller r ON r.id = k.rol_id
                 LEFT JOIN ogrenci_profilleri op ON op.kullanici_id = k.id
                 LEFT JOIN ogretim_elemani_profilleri oep ON oep.kullanici_id = k.id
                 LEFT JOIN bolumler b ON b.id = COALESCE(op.bolum_id, oep.bolum_id)
             WHERE k.id = :id AND k.aktif = 1'
        );
        $sorgu->execute(['id' => $id]);
        $satir = $sorgu->fetch();

        if ($satir === false) {
            return null;
        }

        $satir['ad_soyad'] = trim($satir['ad'] . ' ' . $satir['soyad']);
        $satir['bas_harf'] = mb_strtoupper(mb_substr((string) $satir['ad'], 0, 1, 'UTF-8'), 'UTF-8')
            . mb_strtoupper(mb_substr((string) $satir['soyad'], 0, 1, 'UTF-8'), 'UTF-8');

        return $satir;
    }

    public static function rol(): ?string
    {
        return self::kullanici()['rol'] ?? null;
    }

    /**
     * Sayfaya yalnızca belirtilen roller erişebilir.
     *
     * @param list<string> $roller
     */
    public static function rolGerekli(array $roller): void
    {
        if (!self::girisYapmisMi()) {
            self::flash('uyari', 'Bu sayfayı görmek için giriş yapmalısınız.');
            header('Location: ' . url('giris.php'));
            exit;
        }

        if (!in_array(self::rol(), $roller, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/templates/403.php';
            exit;
        }
    }

    // ------------------------------------------------------------------
    // Giriş / çıkış
    // ------------------------------------------------------------------

    /**
     * @return array{ok:bool, mesaj:string, kullanici:array<string,mixed>|null}
     */
    public static function girisYap(string $eposta, string $sifre): array
    {
        $eposta = mb_strtolower(trim($eposta), 'UTF-8');
        $ip     = self::ip();

        if ($eposta === '' || $sifre === '') {
            return ['ok' => false, 'mesaj' => 'E-posta ve şifre zorunludur.', 'kullanici' => null];
        }

        if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'mesaj' => 'Geçerli bir e-posta adresi girin.', 'kullanici' => null];
        }

        $kilit = self::kilitDurumu($eposta, $ip);

        if ($kilit !== null) {
            return ['ok' => false, 'mesaj' => $kilit, 'kullanici' => null];
        }

        $sorgu = Database::get()->prepare(
            'SELECT k.id, k.sifre_hash, k.aktif
             FROM kullanicilar k
             WHERE k.eposta = :eposta
             LIMIT 1'
        );
        $sorgu->execute(['eposta' => $eposta]);
        $kayit = $sorgu->fetch();

        $dogru = $kayit !== false
            && (int) $kayit['aktif'] === 1
            && password_verify($sifre, (string) $kayit['sifre_hash']);

        self::denemeKaydet($eposta, $ip, $dogru);

        if (!$dogru) {
            return [
                'ok'        => false,
                'mesaj'     => 'E-posta veya şifre hatalı.',
                'kullanici' => null,
            ];
        }

        $kullanici = self::kullaniciyiYukle((int) $kayit['id']);

        if ($kullanici === null) {
            return ['ok' => false, 'mesaj' => 'Hesabınız aktif değil.', 'kullanici' => null];
        }

        session_regenerate_id(true);

        $_SESSION['kullanici_id'] = (int) $kullanici['id'];
        $_SESSION['kullanici']    = $kullanici;
        $_SESSION['son_aktivite'] = time();
        $_SESSION['csrf']         = bin2hex(random_bytes(32));

        self::$kullanici = $kullanici;

        $guncelle = Database::get()->prepare('UPDATE kullanicilar SET son_giris = NOW() WHERE id = :id');
        $guncelle->execute(['id' => $kullanici['id']]);

        self::log('Giriş yapıldı', 'kullanicilar', (int) $kullanici['id']);

        return [
            'ok'        => true,
            'mesaj'     => 'Giriş başarılı.',
            'kullanici' => $kullanici,
        ];
    }

    /**
     * Başarısız deneme sınırı aşıldıysa kalan süreyi söyleyen mesaj döner.
     * Hesap bazlı limit düşük, ağ (IP) bazlı limit daha yüksektir; böylece
     * aynı ağdaki bir kullanıcının hatalı denemeleri diğerlerini kilitlemez.
     */
    private static function kilitDurumu(string $eposta, string $ip): ?string
    {
        $limit    = (int) config('guvenlik.giris_deneme_limiti', 5);
        $ipLimit  = (int) config('guvenlik.ip_deneme_limiti', 25);
        $sure     = (int) config('guvenlik.kilit_suresi', 900);

        $hesap = Database::get()->prepare(
            "SELECT COUNT(*) AS adet, MIN(zaman) AS ilk
             FROM giris_denemeleri
             WHERE basarili = 0
               AND zaman > DATE_SUB(NOW(), INTERVAL {$sure} SECOND)
               AND eposta = :eposta"
        );
        $hesap->execute(['eposta' => $eposta]);
        $satir = $hesap->fetch();

        if ($satir !== false && (int) $satir['adet'] >= $limit) {
            return self::kilitMesaji((string) $satir['ilk'], $sure, 'Bu hesap için');
        }

        $ag = Database::get()->prepare(
            "SELECT COUNT(*) AS adet, MIN(zaman) AS ilk
             FROM giris_denemeleri
             WHERE basarili = 0
               AND zaman > DATE_SUB(NOW(), INTERVAL {$sure} SECOND)
               AND ip = :ip"
        );
        $ag->execute(['ip' => $ip]);
        $satir = $ag->fetch();

        if ($satir !== false && (int) $satir['adet'] >= $ipLimit) {
            return self::kilitMesaji((string) $satir['ilk'], $sure, 'Bu ağdan');
        }

        return null;
    }

    private static function kilitMesaji(string $ilkZaman, int $sure, string $kim): string
    {
        $ilk    = strtotime($ilkZaman);
        $kalan  = max(0, ($ilk + $sure) - time());
        $dakika = (int) ceil($kalan / 60);

        return sprintf(
            '%s çok fazla hatalı deneme yapıldı. Lütfen yaklaşık %d dakika sonra tekrar deneyin.',
            $kim,
            max(1, $dakika)
        );
    }

    private static function denemeKaydet(string $eposta, string $ip, bool $basarili): void
    {
        $sorgu = Database::get()->prepare(
            'INSERT INTO giris_denemeleri (eposta, ip, basarili) VALUES (:eposta, :ip, :basarili)'
        );
        $sorgu->execute([
            'eposta'   => $eposta,
            'ip'       => $ip,
            'basarili' => $basarili ? 1 : 0,
        ]);
    }

    public static function cikisYap(bool $sessiz = false): void
    {
        if (!$sessiz && self::girisYapmisMi()) {
            self::log('Çıkış yapıldı', 'kullanicilar', (int) $_SESSION['kullanici_id']);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => (bool) $p['secure'],
                'httponly' => (bool) $p['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();

        self::$kullanici = null;
    }

    // ------------------------------------------------------------------
    // CSRF
    // ------------------------------------------------------------------

    public static function csrfToken(): string
    {
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));

        return (string) $_SESSION['csrf'];
    }

    public static function csrfAlani(): string
    {
        return '<input type="hidden" name="csrf" value="' . e(self::csrfToken()) . '">';
    }

    public static function csrfDogrula(?string $token): bool
    {
        $beklenen = (string) ($_SESSION['csrf'] ?? '');

        return $beklenen !== '' && is_string($token) && hash_equals($beklenen, $token);
    }

    // ------------------------------------------------------------------
    // Flash mesaj
    // ------------------------------------------------------------------

    public static function flash(string $tip, string $mesaj): void
    {
        $_SESSION['flash'] = ['tip' => $tip, 'mesaj' => $mesaj];
    }

    /**
     * @return array{tip:string, mesaj:string}|null
     */
    public static function flashOku(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return is_array($flash) ? $flash : null;
    }

    // ------------------------------------------------------------------
    // Tema
    // ------------------------------------------------------------------

    /**
     * Kullanıcının kayıtlı tema tercihi: sistem | acik | koyu
     */
    public static function tema(): string
    {
        $tema = self::kullanici()['tema_tercihi'] ?? null;

        return in_array($tema, ['sistem', 'acik', 'koyu'], true) ? (string) $tema : 'sistem';
    }

    public static function temaKaydet(string $tema): bool
    {
        if (!in_array($tema, ['sistem', 'acik', 'koyu'], true)) {
            return false;
        }

        $_SESSION['kullanici']['tema_tercihi'] = $tema;
        self::$kullanici['tema_tercihi'] = $tema;

        if (!self::girisYapmisMi()) {
            return true;
        }

        $sorgu = Database::get()->prepare('UPDATE kullanicilar SET tema_tercihi = :tema WHERE id = :id');
        $sorgu->execute(['tema' => $tema, 'id' => $_SESSION['kullanici_id']]);

        return true;
    }

    // ------------------------------------------------------------------
    // Yardımcılar
    // ------------------------------------------------------------------

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function log(string $islem, ?string $tablo = null, ?int $kayitId = null): void
    {
        try {
            $sorgu = Database::get()->prepare(
                'INSERT INTO islem_loglari (kullanici_id, islem, tablo_adi, kayit_id, ip)
                 VALUES (:kullanici, :islem, :tablo, :kayit, :ip)'
            );
            $sorgu->execute([
                'kullanici' => self::girisYapmisMi() ? (int) $_SESSION['kullanici_id'] : null,
                'islem'     => $islem,
                'tablo'     => $tablo,
                'kayit'     => $kayitId,
                'ip'        => self::ip(),
            ]);
        } catch (Throwable) {
            // Log yazılamaması kullanıcı akışını bozmasın.
        }
    }

    /**
     * Role göre panel adresi.
     */
    public static function panelYolu(?string $rol = null): string
    {
        return match ($rol ?? self::rol()) {
            'ogrenci'          => url('ogrenci/'),
            'ogretim_elemani'  => url('ogretmen/'),
            'yonetici'         => url('admin/'),
            default            => url('giris.php'),
        };
    }

    public static function rolEtiketi(?string $rol = null): string
    {
        return match ($rol ?? self::rol()) {
            'ogrenci'         => 'Öğrenci',
            'ogretim_elemani' => 'Öğretim Elemanı',
            'yonetici'        => 'Yönetici',
            default           => 'Misafir',
        };
    }
}
