<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
declare(strict_types=1);
namespace Digraph\Destructr\LegacyDrivers;

use PHPUnit\Framework\TestCase;
use Digraph\Destructr\Drivers\AbstractDriverTest;

class MySQL56DriverTest extends AbstractDriverTest
{
    const DRIVER_CLASS = \Digraph\Destructr\LegacyDrivers\MySQL56Driver::class;
    const DRIVER_DSN = 'mysql:host=127.0.0.1;dbname=phpunit';
    const DRIVER_USERNAME = 'travis';
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;

    public function createDriver()
    {
        $class = parent::createDriver();
        $class->createLegacyUDF();
        return $class;
    }
}
