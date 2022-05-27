<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\SQLite;

use Destructr\Drivers\AbstractSQLDriverIntegrationTest;
use Destructr\Drivers\SQLiteDriver;

class SQLiteDriverIntegrationTest extends AbstractSQLDriverIntegrationTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_OPTIONS = null;

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__ . '/integration.test.sqlite');
    }

    public static function DRIVER_DSN()
    {
        return 'sqlite:' . __DIR__ . '/integration.test.sqlite';
    }
}
