<?php

declare(strict_types=1);

/**
 * Tek PDO bağlantısı. Bütün sorgular prepared statement ile yazılır.
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $dosya = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.local.php';

            if (!is_file($dosya)) {
                throw new RuntimeException(
                    'Veritabanı ayar dosyası bulunamadı: config/config.local.php'
                );
            }

            /** @var array{host:string,port?:int,name:string,user:string,pass:string} $ayar */
            $ayar = require $dosya;

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $ayar['host'],
                $ayar['port'] ?? 3306,
                $ayar['name']
            );

            self::$pdo = new PDO($dsn, $ayar['user'], $ayar['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        }

        return self::$pdo;
    }

    /**
     * Bağlantı ve sunucu bilgilerini döndürür (kurulum/test ekranı için).
     *
     * @return array<string, mixed>
     */
    public static function bilgi(): array
    {
        $pdo = self::get();

        return [
            'surum'   => (string) $pdo->query('SELECT VERSION()')->fetchColumn(),
            'veritabani' => (string) $pdo->query('SELECT DATABASE()')->fetchColumn(),
            'charset' => (string) $pdo->query('SELECT @@character_set_database')->fetchColumn(),
            'collation' => (string) $pdo->query('SELECT @@collation_database')->fetchColumn(),
            'tablo_sayisi' => (int) $pdo->query(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchColumn(),
        ];
    }

    public static function tabloListesi(): array
    {
        $sorgu = self::get()->query(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );

        return $sorgu->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Şemadaki nesne sayıları (kurulum ekranındaki özet kartı için).
     *
     * @return array{tablo:int, view:int, trigger:int, prosedur:int}
     */
    public static function nesneSayilari(): array
    {
        $pdo = self::get();

        $tablo = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
        )->fetchColumn();

        $view = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.views
             WHERE table_schema = DATABASE()"
        )->fetchColumn();

        $trigger = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.triggers
             WHERE trigger_schema = DATABASE()"
        )->fetchColumn();

        $prosedur = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.routines
             WHERE routine_schema = DATABASE() AND routine_type = 'PROCEDURE'"
        )->fetchColumn();

        return [
            'tablo'    => $tablo,
            'view'     => $view,
            'trigger'  => $trigger,
            'prosedur' => $prosedur,
        ];
    }
}
