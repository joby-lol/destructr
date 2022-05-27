<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\MySQL;

use Destructr\Drivers\AbstractSQLDriverTest;
use Destructr\Drivers\MySQLDriver;

class MySQLDriverTest extends AbstractSQLDriverTest
{
    const DRIVER_CLASS = MySQLDriver::class;

    protected static function DRIVER_DSN()
    {
        return sprintf(
            'mysql:host=%s:%s;dbname=%s',
            $_ENV['TEST_MYSQL_SERVER'],
            $_ENV['TEST_MYSQL_PORT'],
            static::DRIVER_DBNAME()
        );
    }

    protected static function DRIVER_DBNAME()
    {
        return @$_ENV['TEST_MYSQL_DBNAME'] ?? 'destructr_test';
    }

    protected static function DRIVER_USERNAME()
    {
        return @$_ENV['TEST_MYSQL_USER'] ?? 'root';
    }

    protected static function DRIVER_PASSWORD()
    {
        return @$_ENV['TEST_MYSQL_PASSWORD'] ?? 'root';
    }
}
