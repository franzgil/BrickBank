<?php
namespace App\Core;

use PDO;

/**
 * PDO-Verbindungen. Zwei getrennte Verbindungen:
 *  - app(): eigene BrickBank-Datenbank (lesend + schreibend)
 *  - wsc(): WoltLab-Datenbank (ausschließlich lesend verwenden!)
 */
class Database
{
    /** @var PDO|null */
    private static $app;
    /** @var PDO|null */
    private static $wsc;

    public static function app(): PDO
    {
        if (self::$app === null) {
            self::$app = self::connect(Config::get('db', []));
        }
        return self::$app;
    }

    public static function wsc(): PDO
    {
        if (self::$wsc === null) {
            self::$wsc = self::connect(Config::get('wsc.db', []));
        }
        return self::$wsc;
    }

    private static function connect(array $cfg): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $cfg['host'] ?? '127.0.0.1',
            $cfg['name'] ?? '',
            $cfg['charset'] ?? 'utf8mb4'
        );
        return new PDO($dsn, $cfg['user'] ?? '', $cfg['password'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
