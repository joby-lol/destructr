<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
namespace Digraph\Destructr;

class DriverFactory
{
    public static $map = [
        'mysql' => Drivers\MySQLDriver::class,
        'mariadb' => Drivers\MySQLDriver::class,
        'pgsql' => Driver\MySQLDriver::class
    ];

    public static function factory(string $dsn, string $username=null, string $password=null, array $options=null, string $type = null) : ?Drivers\DSODriverInterface
    {
        if (!$type) {
            $type = array_shift(explode(':', $dsn, 2));
        }
        $type = strtolower($type);
        if ($class = @static::$map[$type]) {
            return new $class($dsn, $username, $password, $options);
        } else {
            return null;
        }
    }
}
