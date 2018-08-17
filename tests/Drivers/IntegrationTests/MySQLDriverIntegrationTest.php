<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
declare(strict_types=1);
namespace Digraph\Destructr\Drivers\IntegrationTests;

use PHPUnit\Framework\TestCase;

class MySQLDriverTest extends AbstractDriverIntegrationTest
{
    const DRIVER_CLASS = \Digraph\Destructr\Drivers\MySQLDriver::class;
    const DRIVER_DSN = 'mysql:host=127.0.0.1;dbname=phpunit';
    const DRIVER_USERNAME = 'travis';
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;
    const TEST_TABLE = 'mysqlintegrationtest';
}
