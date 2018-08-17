<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
declare(strict_types=1);
namespace Digraph\Destructr\LegacyDrivers\IntegrationTests;

use PHPUnit\Framework\TestCase;
use Digraph\Destructr\Drivers\IntegrationTests\AbstractDriverIntegrationTest;
use Digraph\Destructr\LegacyDrivers\SQLiteDriver;

class MySQLDriverTest extends AbstractDriverIntegrationTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_DSN = 'sqlite:'.__DIR__.'/test.sqlite';
    const DRIVER_USERNAME = null;
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;
    const TEST_TABLE = 'sqliteintegrationtest';

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__.'/test.sqlite');
    }
}
