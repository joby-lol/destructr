<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\MariaDB;

use Destructr\Drivers\AbstractSQLDriverIntegrationTest;
use Destructr\Drivers\MariaDBDriver;

class MariaDBDriverIntegrationTest extends AbstractSQLDriverIntegrationTest
{
    const DRIVER_CLASS = MariaDBDriver::class;

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
        return @$_ENV['TEST_MARIADB_DBNAME'] ?? 'destructr_test';
    }

    protected static function DRIVER_USERNAME()
    {
        return @$_ENV['TEST_MARIADB_USER'] ?? 'root';
    }

    protected static function DRIVER_PASSWORD()
    {
        return @$_ENV['TEST_MARIADB_PASSWORD'] ?? 'root';
    }
}
