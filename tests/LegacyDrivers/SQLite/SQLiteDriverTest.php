<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
declare(strict_types=1);
namespace Destructr\LegacyDrivers\SQLite;

use PHPUnit\Framework\TestCase;
use Destructr\Drivers\AbstractDriverTest;
use Destructr\LegacyDrivers\SQLiteDriver;

class SQLiteDriverTest extends AbstractDriverTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_DSN = 'sqlite:'.__DIR__.'/driver.test.sqlite';
    const DRIVER_USERNAME = null;
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__.'/driver.test.sqlite');
    }
}
