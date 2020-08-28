<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr;

class DriverFactory
{
    public static $map = [
        'mariadb' => Drivers\MariaDBDriver::class,
        'mysql' => Drivers\MySQLDriver::class,
        'sqlite' => Drivers\SQLiteDriver::class,
    ];

    public static function factory(string $dsn, string $username = null, string $password = null, array $options = null, string $type = null): ?Drivers\AbstractDriver
    {
        if (!$type) {
            $type = @array_shift(explode(':', $dsn, 2));
        }
        $type = strtolower($type);
        if ($class = @static::$map[$type]) {
            return new $class($dsn, $username, $password, $options);
        } else {
            return null;
        }
    }

    public static function factoryFromPDO(\PDO $pdo, string $type = null): ?Drivers\AbstractDriver
    {
        if (!$type) {
            $type = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        $type = strtolower($type);
        if ($class = @static::$map[$type]) {
            $f = new $class();
            $f->pdo($pdo);
            return $f;
        } else {
            return null;
        }
    }
}
