<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
declare(strict_types=1);
namespace Destructr\LegacyDrivers\SQLite;

use PHPUnit\Framework\TestCase;
use Destructr\Drivers\AbstractDriverIntegrationTest;
use Destructr\LegacyDrivers\SQLiteDriver;

class MySQLDriverTest extends AbstractDriverIntegrationTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_DSN = 'sqlite:'.__DIR__.'/integration.test.sqlite';
    const DRIVER_USERNAME = null;
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;
    const TEST_TABLE = 'sqliteintegrationtest';

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__.'/integration.test.sqlite');
    }
}
