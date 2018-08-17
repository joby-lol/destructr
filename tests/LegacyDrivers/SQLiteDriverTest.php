<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
declare(strict_types=1);
namespace Digraph\Destructr\LegacyDrivers;

use PHPUnit\Framework\TestCase;
use Digraph\Destructr\Drivers\AbstractDriverTest;

class SQLiteDriverTest extends AbstractDriverTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_DSN = 'sqlite:'.__DIR__.'/test.sqlite';
    const DRIVER_USERNAME = null;
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;

    public static function setUpBeforeClass()
    {
        @unlink(__DIR__.'/test.sqlite');
    }
}
