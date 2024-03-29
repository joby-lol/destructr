<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\SQLite;

use Destructr\Drivers\AbstractSQLDriverTest;
use Destructr\Drivers\SQLiteDriver;

class SQLiteDriverTest extends AbstractSQLDriverTest
{
    const DRIVER_CLASS = SQLiteDriver::class;

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__ . '/driver.test.sqlite');
    }

    public static function DRIVER_DSN()
    {
        return 'sqlite:' . __DIR__ . '/driver.test.sqlite';
    }

    protected static function DRIVER_DBNAME()
    {
        return null;
    }

    protected static function DRIVER_USERNAME()
    {
        return null;
    }
}
