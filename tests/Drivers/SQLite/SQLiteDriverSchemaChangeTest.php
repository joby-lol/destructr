<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers\SQLite;

use Destructr\Drivers\AbstractSQLDriverSchemaChangeTest;
use Destructr\Drivers\SQLiteDriver;

class SQLiteDriverSchemaChangeTest extends AbstractSQLDriverSchemaChangeTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_DSN = 'sqlite:'.__DIR__.'/schema.test.sqlite';
    const DRIVER_USERNAME = 'root';
    const DRIVER_PASSWORD = '';
    const DRIVER_OPTIONS = null;

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__.'/schema.test.sqlite');
    }
}
