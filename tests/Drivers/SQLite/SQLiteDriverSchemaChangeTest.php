<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\SQLite;

use Destructr\Drivers\AbstractSQLDriverSchemaChangeTest;
use Destructr\Drivers\SQLiteDriver;

class SQLiteDriverSchemaChangeTest extends AbstractSQLDriverSchemaChangeTest
{
    const DRIVER_CLASS = SQLiteDriver::class;

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__ . '/schema.test.sqlite');
    }

    public static function DRIVER_DSN()
    {
        return 'sqlite:' . __DIR__ . '/schema.test.sqlite';
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
